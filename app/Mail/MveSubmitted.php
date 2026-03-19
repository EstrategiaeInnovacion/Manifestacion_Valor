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

            // XML acuse: solo si es respuesta de CONSULTA firmada (tiene selloDigitalVentanilla),
            // NO el XML de registro (registroManifestacionResponse con solo numeroOperacion).
            $esAcuseReal = !empty($this->acuse->xml_respuesta)
                && str_contains($this->acuse->xml_respuesta, 'selloDigitalVentanilla');

            if ($esAcuseReal) {
                $folioAcuse = $this->folioReal ?? $this->acuse->numero_cove ?? $this->acuse->folio_manifestacion ?? 'acuse';
                $attachments[] = [
                    '@odata.type' => '#microsoft.graph.fileAttachment',
                    'name' => "acuse_mve_{$folioAcuse}.xml",
                    'contentType' => 'application/xml',
                    'contentBytes' => base64_encode($this->acuse->xml_respuesta),
                    'isInline' => false,
                ];
            }

            // XML declaración: solo si contiene datosManifestacionValor (consulta por MNVA).
            $esDeclaracionReal = !empty($this->acuse->xml_declaracion)
                && str_contains($this->acuse->xml_declaracion, 'datosManifestacionValor');

            if ($esDeclaracionReal) {
                $folioDecl = $this->folioReal ?? $this->acuse->numero_cove ?? $this->acuse->folio_manifestacion ?? 'declaracion';
                $attachments[] = [
                    '@odata.type' => '#microsoft.graph.fileAttachment',
                    'name' => "declaracion_mve_{$folioDecl}.xml",
                    'contentType' => 'application/xml',
                    'contentBytes' => base64_encode($this->acuse->xml_declaracion),
                    'isInline' => false,
                ];
            }

            // El folio que se mostrará en el correo: preferir el real de VUCEM
            $folioMostrar = $this->folioReal ?? $this->acuse->numero_cove ?? $this->acuse->folio_manifestacion;

            $tieneWsKey = $this->acuse->applicant?->hasWebserviceKey() ?? false;

            $html = View::make('emails.mve-submitted', [
                'user'                  => $this->submittedBy,
                'acuse'                 => $this->acuse,
                'folioMostrar'          => $folioMostrar,
                'tieneAcuseXml'         => $esAcuseReal,
                'tieneDeclaracionXml'   => $esDeclaracionReal,
                'tieneWsKey'            => $tieneWsKey,
                'urlConsultas'          => url('/mve/completadas'),
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
