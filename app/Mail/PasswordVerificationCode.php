<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordVerificationCode extends Mailable
{
    use Queueable, SerializesModels;

    public string $userName;
    public string $code;

    public function __construct(string $userName, string $code)
    {
        $this->userName = $userName;
        $this->code     = $code;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Código de verificación — Recuperación de contraseña',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.password-verification-code',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
