<?php

namespace App\Mail;

use App\Models\MvAcuse;
use App\Models\User;
use App\Services\MicrosoftGraphMailService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;

class MveSubmitted
{
    protected User $submittedBy;
    protected MvAcuse $acuse;
    protected ?string $ccEmail;
    protected ?string $folioReal;

    /**
     * @param User        $submittedBy  Usuario que firmó y envió
     * @param MvAcuse     $acuse        Registro del acuse generado
     * @param string|null $ccEmail      Correo del admin para copia
     * @param string|null $folioReal    Folio real de VUCEM obtenido de la consulta (MNVA...)
     */
    public function __construct(User $submittedBy, MvAcuse $acuse, ?string $ccEmail = null, ?string $folioReal = null)
    {
        $this->submittedBy = $submittedBy;
        $this->acuse = $acuse;
        $this->ccEmail = ($ccEmail && $ccEmail !== $submittedBy->email) ? $ccEmail : null;
        $this->folioReal = $folioReal;
    }

    public function send(): bool
    {
        try {
            $attachments = [];

            // Logo inline
            $logoPath = public_path('Gemini_Generated_Image_bmz5e9bmz5e9bmz5-removebg-preview.png');
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

            // XML acuse como adjunto
            if (!empty($this->acuse->xml_respuesta)) {
                $folio = $this->folioReal ?? $this->acuse->folio_manifestacion ?? 'acuse';
                $attachments[] = [
                    '@odata.type' => '#microsoft.graph.fileAttachment',
                    'name' => "acuse_mve_{$folio}.xml",
                    'contentType' => 'application/xml',
                    'contentBytes' => base64_encode($this->acuse->xml_respuesta),
                    'isInline' => false,
                ];
            }

            // El folio que se mostrará en el correo: preferir el real de VUCEM
            $folioMostrar = $this->folioReal ?? $this->acuse->numero_cove ?? $this->acuse->folio_manifestacion;

            $html = View::make('emails.mve-submitted', [
                'user'         => $this->submittedBy,
                'acuse'        => $this->acuse,
                'folioMostrar' => $folioMostrar,
            ])->render();

            $mailService = app(MicrosoftGraphMailService::class);

            return $mailService->sendMail(
                to: $this->submittedBy->email,
                subject: "MVE Enviada — Folio: {$folioMostrar}",
                htmlBody: $html,
                attachments: $attachments,
                cc: $this->ccEmail,
            );
        } catch (\Exception $e) {
            Log::error('MveSubmitted: Error al enviar correo de acuse', [
                'user_id'  => $this->submittedBy->id,
                'acuse_id' => $this->acuse->id,
                'error'    => $e->getMessage(),
            ]);
            return false;
        }
    }
}
