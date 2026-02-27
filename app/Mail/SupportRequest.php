<?php

namespace App\Mail;

use App\Services\MicrosoftGraphMailService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;

class SupportRequest
{
    protected string $userName;
    protected string $userEmail;
    protected string $subject;
    protected string $category;
    protected string $description;
    protected array  $attachments;

    public function __construct(
        string $userName,
        string $userEmail,
        string $category,
        string $subject,
        string $description,
        array  $attachments = [],
    ) {
        $this->userName    = $userName;
        $this->userEmail   = $userEmail;
        $this->category    = $category;
        $this->subject     = $subject;
        $this->description = $description;
        $this->attachments = $attachments;
    }

    public function send(): bool
    {
        try {
            $screenshotCount = count($this->attachments);

            $html = View::make('emails.support-request', [
                'userName'        => $this->userName,
                'userEmail'       => $this->userEmail,
                'category'        => $this->category,
                'subject'         => $this->subject,
                'description'     => $this->description,
                'screenshotCount' => $screenshotCount,
            ])->render();

            // Logo inline (mismo patrón que WelcomeNewUser)
            $allAttachments = [];
            $logoPath = public_path('Gemini_Generated_Image_bmz5e9bmz5e9bmz5-removebg-preview.png');
            if (file_exists($logoPath)) {
                $allAttachments[] = [
                    '@odata.type'  => '#microsoft.graph.fileAttachment',
                    'name'         => 'logo.png',
                    'contentType'  => 'image/png',
                    'contentBytes' => base64_encode(file_get_contents($logoPath)),
                    'contentId'    => 'logo_file',
                    'isInline'     => true,
                ];
            }

            // Capturas de pantalla adjuntas
            foreach ($this->attachments as $attachment) {
                $allAttachments[] = $attachment;
            }

            $supportEmail = config('mail.support_address', 'soporte@tuempresa.com');
            $mailService  = app(MicrosoftGraphMailService::class);

            return $mailService->sendMail(
                to:          $supportEmail,
                subject:     "[Soporte MVE] {$this->category}: {$this->subject}",
                htmlBody:    $html,
                attachments: $allAttachments,
                replyTo:     $this->userEmail,
                replyToName: $this->userName,
            );
        } catch (\Exception $e) {
            Log::error('SupportRequest: Error al enviar ticket de soporte', [
                'userEmail' => $this->userEmail,
                'error'     => $e->getMessage(),
            ]);
            return false;
        }
    }
}
