<?php

namespace App\Mail;

use App\Models\MvClientApplicant;
use App\Models\User;
use App\Services\MicrosoftGraphMailService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;

class ApplicantAdded
{
    protected User $user;
    protected MvClientApplicant $applicant;

    public function __construct(User $user, MvClientApplicant $applicant)
    {
        $this->user = $user;
        $this->applicant = $applicant;
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

            $html = View::make('emails.applicant-added', [
                'user' => $this->user,
                'applicant' => $this->applicant,
            ])->render();

            $mailService = app(MicrosoftGraphMailService::class);

            return $mailService->sendMail(
                $this->user->email,
                'Nuevo Solicitante Registrado en FILE',
                $html,
                $attachments
            );
        } catch (\Exception $e) {
            Log::error('ApplicantAdded: Error al enviar correo', [
                'user_id' => $this->user->id,
                'applicant_id' => $this->applicant->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
