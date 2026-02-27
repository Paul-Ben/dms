<?php

namespace App\Http\Controllers;

use App\Helpers\CloudinaryHelper;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\MessageRead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    public function index(Request $request, Conversation $conversation)
    {
        $authUser = Auth::user();
        $tenantId = optional($authUser->userDetail)->tenant_id;
        if ($conversation->tenant_id !== $tenantId) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $isParticipant = $conversation->participants()->where('user_id', $authUser->id)->exists();
        if (!$isParticipant) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $messages = Message::where('conversation_id', $conversation->id)
            ->orderBy('created_at', 'asc')
            ->with(['sender:id,name', 'attachments'])
            ->paginate(30);

        // Determine the other participant (for direct chats) to compute read flags
        $otherUserId = $conversation->participants()
            ->where('user_id', '!=', $authUser->id)
            ->value('user_id');

        // Enrich each message with delivery/read status for UI ticks
        $messages->getCollection()->transform(function ($m) use ($authUser, $otherUserId) {
            // Delivered once persisted
            $m->setAttribute('delivered', true);
            // Read by recipient applies only to messages you sent
            if ($m->sender_id === $authUser->id && $otherUserId) {
                $read = MessageRead::where('message_id', $m->id)
                    ->where('user_id', $otherUserId)
                    ->exists();
                $m->setAttribute('read_by_recipient', $read);
            } else {
                $m->setAttribute('read_by_recipient', null);
            }
            return $m;
        });

        return response()->json($messages);
    }

    public function store(Request $request, Conversation $conversation)
    {
        $authUser = Auth::user();
        $tenantId = optional($authUser->userDetail)->tenant_id;
        if ($conversation->tenant_id !== $tenantId) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $isParticipant = $conversation->participants()->where('user_id', $authUser->id)->exists();
        if (!$isParticipant) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'body' => ['nullable', 'string', 'max:5000'],
            'attachments.*' => ['nullable', 'file', 'max:25600', 'mimetypes:image/*,video/*,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        ]);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $authUser->id,
            'body' => $validated['body'] ?? null,
            'has_attachments' => isset($validated['attachments']) && count($validated['attachments']) > 0,
        ]);

        // Handle Cloudinary uploads
        if ($request->hasFile('attachments')) {
            $helper = new CloudinaryHelper();
            foreach ($request->file('attachments') as $file) {
                $folder = 'chat/' . $tenantId . '/' . $conversation->id . '/' . $message->id;
                $upload = $helper->upload($file, $folder);

                MessageAttachment::create([
                    'message_id' => $message->id,
                    'provider' => 'cloudinary',
                    'public_id' => $upload['public_id'] ?? '',
                    'secure_url' => $upload['secure_url'] ?? ($upload['url'] ?? ''),
                    'url' => $upload['url'] ?? null,
                    'resource_type' => $upload['resource_type'] ?? null,
                    'format' => $upload['format'] ?? null,
                    'mime_type' => $file->getMimeType(),
                    'bytes' => $upload['bytes'] ?? null,
                    'original_name' => $file->getClientOriginalName(),
                ]);
            }
        }

        return response()->json(['message_id' => $message->id]);
    }

    public function markRead(Request $request, Conversation $conversation)
    {
        $authUser = Auth::user();
        $tenantId = optional($authUser->userDetail)->tenant_id;
        if ($conversation->tenant_id !== $tenantId) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $isParticipant = $conversation->participants()->where('user_id', $authUser->id)->exists();
        if (!$isParticipant) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $messages = Message::where('conversation_id', $conversation->id)
            ->where('sender_id', '!=', $authUser->id)
            ->whereDoesntHave('reads', function ($q) use ($authUser) {
                $q->where('user_id', $authUser->id);
            })
            ->get(['id']);

        foreach ($messages as $m) {
            MessageRead::create([
                'message_id' => $m->id,
                'user_id' => $authUser->id,
                'read_at' => now(),
            ]);
        }

        return response()->json(['marked' => $messages->count()]);
    }
}