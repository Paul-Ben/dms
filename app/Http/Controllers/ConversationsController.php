<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\MessageRead;
use App\Models\User;
use App\Models\UserDetails;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ConversationsController extends Controller
{
    public function index(Request $request)
    {
        $authUser = Auth::user();
        // Resolve tenant via UserDetails to avoid relying on non-existent tenants.user_id
        $tenantId = optional($authUser->userDetail)->tenant_id;
        $userTenant = optional($authUser->userDetail)->tenant;

        // Fetch members by tenant using UserDetails mapping
        $members = User::whereIn('id', UserDetails::where('tenant_id', $tenantId)->pluck('user_id'))
            ->where('id', '!=', $authUser->id)
            ->select('id', 'name')
            ->with('userDetail')
            ->get()
            ->map(function ($u) use ($authUser, $tenantId) {
                // Find direct conversation between auth user and member
                $conv = Conversation::where('tenant_id', $tenantId)
                    ->where('type', 'direct')
                    ->whereHas('participants', fn($q) => $q->where('user_id', $authUser->id))
                    ->whereHas('participants', fn($q) => $q->where('user_id', $u->id))
                    ->first();

                $unreadCount = 0;
                $lastUnreadAt = null;
                $lastMessageAt = null;
                $hasConversation = false;

                if ($conv) {
                    $hasConversation = true;
                    // Unread messages sent by the member
                    $unreadQuery = Message::where('conversation_id', $conv->id)
                        ->where('sender_id', $u->id)
                        ->whereDoesntHave('reads', function ($q) use ($authUser) {
                            $q->where('user_id', $authUser->id);
                        });
                    $unreadCount = (clone $unreadQuery)->count();
                    $lastUnreadAt = (clone $unreadQuery)->max('created_at');

                    // Last activity in conversation
                    $lastMessageAt = Message::where('conversation_id', $conv->id)->max('created_at');
                }

                $u->setAttribute('unread_count', $unreadCount);
                $u->setAttribute('last_unread_at', $lastUnreadAt);
                $u->setAttribute('last_message_at', $lastMessageAt);
                $u->setAttribute('has_conversation', $hasConversation);
                return $u;
            })
            ->sort(function ($a, $b) {
                // Priority 1: has unread (true first)
                $aUnread = ($a->unread_count ?? 0) > 0;
                $bUnread = ($b->unread_count ?? 0) > 0;
                if ($aUnread && !$bUnread) return -1;
                if ($bUnread && !$aUnread) return 1;

                // Priority 2: most recent unread timestamp desc
                $aLastUnread = $a->last_unread_at ? strtotime($a->last_unread_at) : 0;
                $bLastUnread = $b->last_unread_at ? strtotime($b->last_unread_at) : 0;
                if ($aLastUnread !== $bLastUnread) return $bLastUnread <=> $aLastUnread;

                // Priority 3: has conversation (true first)
                $aHasConv = $a->has_conversation ? 1 : 0;
                $bHasConv = $b->has_conversation ? 1 : 0;
                if ($aHasConv !== $bHasConv) return $bHasConv <=> $aHasConv;

                // Priority 4: latest message timestamp desc
                $aLastMsg = $a->last_message_at ? strtotime($a->last_message_at) : 0;
                $bLastMsg = $b->last_message_at ? strtotime($b->last_message_at) : 0;
                if ($aLastMsg !== $bLastMsg) return $bLastMsg <=> $aLastMsg;

                // Fallback: alphabetical by name
                return strcmp($a->name, $b->name);
            })
            ->values();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'data' => $members,
                'meta' => [
                    'tenant_id' => $tenantId,
                ],
            ]);
        }

        return view('conversations.index', compact('members', 'authUser', 'userTenant'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => ['required', 'exists:users,id'],
        ]);
        $authUser = Auth::user();
        $tenantId = optional($authUser->userDetail)->tenant_id;
        if (!$tenantId) {
            return response()->json(['message' => 'Your account is not linked to a tenant'], 422);
        }
        $otherUser = User::findOrFail($request->user_id);
        if (!optional($otherUser->userDetail)->tenant_id) {
            return response()->json(['message' => 'Selected user is not linked to a tenant'], 422);
        }
        // Ensure both users are in the same tenant via UserDetails
        if (optional($otherUser->userDetail)->tenant_id !== $tenantId) {
            return response()->json(['message' => 'Cross-tenant conversations are not allowed'], 403);
        }

        $conversation = DB::transaction(function () use ($authUser, $otherUser) {
            // Try to find existing direct conversation with exactly these two participants
            $existing = Conversation::where('tenant_id', optional($authUser->userDetail)->tenant_id)
                ->where('type', 'direct')
                ->whereHas('participants', function ($q) use ($authUser) {
                    $q->where('user_id', $authUser->id);
                })
                ->whereHas('participants', function ($q) use ($otherUser) {
                    $q->where('user_id', $otherUser->id);
                })
                ->first();

            if ($existing) {
                return $existing;
            }

            $conv = Conversation::create([
                'tenant_id' => optional($authUser->userDetail)->tenant_id,
                'type' => 'direct',
                'created_by' => $authUser->id,
            ]);

            ConversationParticipant::create([
                'conversation_id' => $conv->id,
                'user_id' => $authUser->id,
            ]);
            ConversationParticipant::create([
                'conversation_id' => $conv->id,
                'user_id' => $otherUser->id,
            ]);

            return $conv;
        });

        return response()->json(['conversation_id' => $conversation->id]);
    }

    public function searchMembers(Request $request)
    {
        $authUser = Auth::user();
        $tenantId = optional($authUser->userDetail)->tenant_id;
        if (!$tenantId) {
            return response()->json(['message' => 'Your account is not linked to a tenant'], 422);
        }

        $q = trim((string) $request->query('q', ''));

        $tenantUserIds = UserDetails::where('tenant_id', $tenantId)->pluck('user_id');
        $members = User::whereIn('id', $tenantUserIds)
            ->where('id', '!=', $authUser->id)
            ->when($q !== '', function ($query) use ($q) {
                $query->where('name', 'like', '%' . $q . '%');
            })
            ->with('userDetail')
            ->limit(50)
            ->get();

        $data = $members->map(function ($u) use ($authUser, $tenantId) {
            $avatar = optional($u->userDetail)->avatar;
            $avatarUrl = $avatar ? asset('uploads/avatars/' . $avatar) : asset('avatar.jpeg');
            // Compute unread for search results
            $conv = Conversation::where('tenant_id', $tenantId)
                ->where('type', 'direct')
                ->whereHas('participants', fn($q) => $q->where('user_id', $authUser->id))
                ->whereHas('participants', fn($q) => $q->where('user_id', $u->id))
                ->first();
            $unreadCount = 0;
            $lastUnreadAt = null;
            $lastMessageAt = null;
            $hasConversation = false;
            if ($conv) {
                $hasConversation = true;
                $unreadQuery = Message::where('conversation_id', $conv->id)
                    ->where('sender_id', $u->id)
                    ->whereDoesntHave('reads', function ($q) use ($authUser) {
                        $q->where('user_id', $authUser->id);
                    });
                $unreadCount = (clone $unreadQuery)->count();
                $lastUnreadAt = (clone $unreadQuery)->max('created_at');
                $lastMessageAt = Message::where('conversation_id', $conv->id)->max('created_at');
            }
            return [
                'id' => $u->id,
                'name' => $u->name,
                'avatar_url' => $avatarUrl,
                'unread_count' => $unreadCount,
                'last_unread_at' => $lastUnreadAt,
                'last_message_at' => $lastMessageAt,
                'has_conversation' => $hasConversation,
            ];
        })
        ->sort(function ($a, $b) {
            // Priority 1: has unread (true first)
            $aUnread = ($a['unread_count'] ?? 0) > 0;
            $bUnread = ($b['unread_count'] ?? 0) > 0;
            if ($aUnread && !$bUnread) return -1;
            if ($bUnread && !$aUnread) return 1;

            // Priority 2: most recent unread timestamp desc
            $aLastUnread = !empty($a['last_unread_at']) ? strtotime($a['last_unread_at']) : 0;
            $bLastUnread = !empty($b['last_unread_at']) ? strtotime($b['last_unread_at']) : 0;
            if ($aLastUnread !== $bLastUnread) return $bLastUnread <=> $aLastUnread;

            // Priority 3: has conversation (true first)
            $aHasConv = !empty($a['has_conversation']) ? 1 : 0;
            $bHasConv = !empty($b['has_conversation']) ? 1 : 0;
            if ($aHasConv !== $bHasConv) return $bHasConv <=> $aHasConv;

            // Priority 4: latest message timestamp desc
            $aLastMsg = !empty($a['last_message_at']) ? strtotime($a['last_message_at']) : 0;
            $bLastMsg = !empty($b['last_message_at']) ? strtotime($b['last_message_at']) : 0;
            if ($aLastMsg !== $bLastMsg) return $bLastMsg <=> $aLastMsg;

            return strcmp($a['name'], $b['name']);
        })
        ->values();

        return response()->json(['data' => $data]);
    }
}
