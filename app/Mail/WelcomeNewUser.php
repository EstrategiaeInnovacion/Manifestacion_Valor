<?php

namespace App\Mail;

use App\Models\User;
use App\Services\MicrosoftGraphMailService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;

class WelcomeNewUser
{
    protected User $newUser;
    protected User $createdBy;
    protected string $plainPassword;
    protected ?string $licenseKey;

    public function __construct(User $newUser, User $createdBy, string $plainPassword)
    {
        $this->newUser = $newUser;
        $this->createdBy = $createdBy;
        $this->plainPassword = $plainPassword;

        // Obtener la licencia activa del admin que crea el usuario
        $license = $createdBy->getEffectiveLicense() ?? $createdBy->activeLicense;
        $this->licenseKey = $license?->license_key;
    }

    /**
     * Enviar el correo de bienvenida usando Microsoft Graph.
     */
    public function send(): bool
    {
        try {
            // Leer el logo para enviarlo como adjunto inline (CID)
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

            $html = View::make('emails.welcome-user', [
                'newUser' => $this->newUser,
                'createdBy' => $this->createdBy,
                'plainPassword' => $this->plainPassword,
                'licenseKey' => $this->licenseKey,
            ])->render();

            $mailService = app(MicrosoftGraphMailService::class);

            return $mailService->sendMail(
                $this->newUser->email,
                'Bienvenido al sistema FILE - Tus credenciales de acceso',
                $html,
                $attachments
            );
        } catch (\Exception $e) {
            Log::error('WelcomeNewUser: Error al enviar correo de bienvenida', [
                'user_id' => $this->newUser->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
