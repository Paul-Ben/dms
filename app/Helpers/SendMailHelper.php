<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Mail;
use App\Mail\SendNotificationMail;
use App\Mail\ReceiveNotificationMail;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class SendMailHelper
{
    public static function sendNotificationMail($data, $request, $userDepartment = null, $userTenant = null)
    {
        $senderName = Auth::user()->name;

        // Resolve receiver name from first recipient
        $firstRecipientId = is_array($request->recipient_id) ? ($request->recipient_id[0] ?? null) : $request->recipient_id;
        $receiverName = $firstRecipientId ? User::find($firstRecipientId)?->name : null;

        // Resolve document details robustly from DB when possible
        $document = null;
        if (!empty($request->document_id)) {
            $document = \App\Models\Document::find($request->document_id);
        } elseif (is_object($data) && !empty($data->document_id)) {
            $document = \App\Models\Document::find($data->document_id);
        }

        $documentName = $document->title ?? ($request->title ?? null);
        // Column name in DB is 'docuent_number' (as defined in the model)
        $documentId = $document->docuent_number ?? ($request->document_number ?? null);

        $appName = config('app.name');

        // Sender notification (optional department/tenant info)
        Mail::to(Auth::user()->email)->send(new SendNotificationMail(
            $senderName,
            $receiverName ?? 'Recipient',
            $documentName ?? 'Document',
            $appName,
            $documentId ?? null,
            $userDepartment,
            $userTenant
        ));

        // Notify each recipient
        $recipientIds = is_array($request->recipient_id) ? $request->recipient_id : [$request->recipient_id];
        foreach ($recipientIds as $recipientId) {
            $receiver = User::find($recipientId);
            if ($receiver) {
                Mail::to($receiver->email)->send(new ReceiveNotificationMail(
                    $senderName,
                    $receiver->name,
                    $documentName ?? 'Document',
                    $documentId ?? '',
                    $appName
                ));
            }
        }
    }

    public static function sendReviewNotificationMail($data, $recipient, $userDepartment = null, $userTenant = null)
    {   
        $senderName = Auth::user()->name;
        $receiverName = User::find($recipient->id)?->name;
        // Prefer payload values, fall back to DB lookup
        $documentName = $data['document']['title'] ?? null;
        $documentId = $data['document']['docuent_number'] ?? ($data['document']['document_number'] ?? null);
        if (!$documentName || !$documentId) {
            $documentIdFromPayload = $data['document']['id'] ?? ($data['document_id'] ?? null);
            if ($documentIdFromPayload) {
                $doc = \App\Models\Document::find($documentIdFromPayload);
                if ($doc) {
                    $documentName = $documentName ?? $doc->title;
                    $documentId = $documentId ?? $doc->docuent_number;
                }
            }
        }
        $appName = config('app.name');
        Mail::to(Auth::user()->email)->send(new SendNotificationMail(
            $senderName,
            $receiverName,
            $documentName ?? 'Document',
            $appName,
            $documentId ?? null,
            $userDepartment,
            $userTenant
        ));

        // Notify each recipient
        $recipientId = $recipient->id;
            $receiver = User::find($recipientId);
            if ($receiver) {
                Mail::to($receiver->email)->send(new ReceiveNotificationMail(
                    $senderName,
                    $receiver->name,
                    $documentName ?? 'Document',
                    $documentId ?? '',
                    $appName
                ));
            }
        
    }
}
