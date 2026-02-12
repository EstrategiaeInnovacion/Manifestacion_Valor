<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SupportRequest extends Mailable
{
    use Queueable, SerializesModels;

    public string $userName;
    public string $userEmail;
    public string $subject;
    public string $category;
    public string $description;

    public function __construct(string $userName, string $userEmail, string $category, string $subject, string $description)
    {
        $this->userName = $userName;
        $this->userEmail = $userEmail;
        $this->category = $category;
        $this->subject = $subject;
        $this->description = $description;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[Soporte MVE] {$this->category}: {$this->subject}",
            replyTo: [$this->userEmail],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.support-request',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}