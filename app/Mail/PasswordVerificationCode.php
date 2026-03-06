<?php

namespace App\Mail;

use App\Services\MicrosoftGraphMailService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;

class PasswordVerificationCode
{
    protected string $userName;
    protected string $code;
    protected string $to;

    public function __construct(string $to, string $userName, string $code)
    {
        $this->to       = $to;
        $this->userName = $userName;
        $this->code     = $code;
    }

    public function send(): bool
    {
        try {
            $html = View::make('emails.password-verification-code', [
                'userName' => $this->userName,
                'code'     => $this->code,
            ])->render();

            $mailService = app(MicrosoftGraphMailService::class);

            return $mailService->sendMail(
                $this->to,
                'Código de verificación — Recuperación de contraseña',
                $html
            );
        } catch (\Exception $e) {
            Log::error('PasswordVerificationCode: Error al enviar código', [
                'to'    => $this->to,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
