<?php

namespace App\Mail;

use App\Models\Announcement;
use App\Models\User;
use App\Services\MicrosoftGraphMailService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;

class AnnouncementMail
{
    public function __construct(
        protected Announcement $announcement,
        protected User $recipient
    ) {}

    public function send(): bool
    {
        try {
            $logoPath = public_path('Gemini_Generated_Image_bmz5e9bmz5e9bmz5-removebg-preview.png');
            $attachments = [];
            if (file_exists($logoPath)) {
                $attachments[] = [
                    '@odata.type'  => '#microsoft.graph.fileAttachment',
                    'name'         => 'logo.png',
                    'contentType'  => 'image/png',
                    'contentBytes' => base64_encode(file_get_contents($logoPath)),
                    'contentId'    => 'logo_file',
                    'isInline'     => true,
                ];
            }

            $html = View::make('emails.announcement', [
                'announcement' => $this->announcement,
                'recipient'    => $this->recipient,
            ])->render();

            $mailService = app(MicrosoftGraphMailService::class);

            return $mailService->sendMail(
                $this->recipient->email,
                '📢 Aviso General: ' . $this->announcement->title,
                $html,
                $attachments
            );
        } catch (\Exception $e) {
            Log::error('AnnouncementMail: Error al enviar aviso', [
                'announcement_id' => $this->announcement->id,
                'user_id'         => $this->recipient->id,
                'error'           => $e->getMessage(),
            ]);
            return false;
        }
    }
}
