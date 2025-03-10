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

    /**
     * Create a new message instance.
     */
    public function __construct($senderName, $receiverName, $documentName, $appName)
    {
        //
        $this->senderName = $senderName;
        $this->receiverName = $receiverName;
        $this->documentName = $documentName;
        $this->appName = $appName;
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
                'appName' => $this->appName
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
