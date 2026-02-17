<?php

namespace App\Mail;

use App\Models\License;
use App\Models\User;
use App\Services\MicrosoftGraphMailService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;

class LicenseAssigned
{
    protected User $admin;
    protected License $license;

    public function __construct(User $admin, License $license)
    {
        $this->admin = $admin;
        $this->license = $license;
    }

    public function send(): bool
    {
        try {
            $logoPath = public_path('Gemini_Generated_Image_bmz5e9bmz5e9bmz5-removebg-preview.png');
            $attachments = [];
            if (file_exists($logoPath)) {
                $attachments[] = [
                    '@odata.type' => '#microsoft.graph.fileAttachment',
                    'name' => 'logo.png',
                    'contentType' => 'image/png',
                    'contentBytes' => base64_encode(file_get_contents($logoPath)),
                    'contentId' => 'logo_file',
                    'isInline' => true,
                ];
            }

            $durationLabel = License::DURATIONS[$this->license->duration_type]['label'] ?? $this->license->duration_type;

            $html = View::make('emails.license-assigned', [
                'admin' => $this->admin,
                'license' => $this->license,
                'durationLabel' => $durationLabel,
            ])->render();

            $mailService = app(MicrosoftGraphMailService::class);

            return $mailService->sendMail(
                $this->admin->email,
                'Licencia FILE Activada — ' . $this->license->license_key,
                $html,
                $attachments
            );
        } catch (\Exception $e) {
            Log::error('LicenseAssigned: Error al enviar correo', [
                'admin_id' => $this->admin->id,
                'license_key' => $this->license->license_key,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
