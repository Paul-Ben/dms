<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $senderName;

    public $receiverName;

    public  $documentName;

    public $appName;

    public $documentId;

    public $userDepartment;

    public $userTenant;

    /**
     * Create a new message instance.
     */
    public function __construct($senderName, $receiverName, $documentName, $appName, $documentId = null, $userDepartment = null, $userTenant = null)
    {
        //
        $this->senderName = $senderName;
        $this->receiverName = $receiverName;
        $this->documentName = $documentName;
        $this->appName = $appName;
        $this->documentId = $documentId;
        $this->userDepartment = $userDepartment;
        $this->userTenant = $userTenant;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Document Sent Notification',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.send_notification',
            with: [
                'senderName' => $this->senderName,
                'receiverName' => $this->receiverName,
                'documentName' => $this->documentName,
                'documentId' => $this->documentId,
                'appName' => $this->appName,
                'userDepartment' => $this->userDepartment,
                'userTenant'=> $this->userTenant
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
