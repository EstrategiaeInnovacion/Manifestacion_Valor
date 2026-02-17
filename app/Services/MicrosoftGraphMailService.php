<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class MicrosoftGraphMailService
{
    protected string $tenantId;
    protected string $clientId;
    protected string $clientSecret;
    protected string $fromAddress;

    public function __construct()
    {
        $this->tenantId = config('services.microsoft_graph.tenant_id');
        $this->clientId = config('services.microsoft_graph.client_id');
        $this->clientSecret = config('services.microsoft_graph.client_secret');
        $this->fromAddress = config('services.microsoft_graph.from_address');
    }

    /**
     * Obtener un token de acceso mediante Client Credentials Flow.
     */
    protected function getAccessToken(): string
    {
        return Cache::remember('microsoft_graph_token', 3500, function () {
            $response = Http::asForm()->post(
                "https://login.microsoftonline.com/{$this->tenantId}/oauth2/v2.0/token",
                [
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'scope' => 'https://graph.microsoft.com/.default',
                    'grant_type' => 'client_credentials',
                ]
            );

            if (!$response->successful()) {
                Log::error('Microsoft Graph: Error al obtener token', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \Exception('No se pudo obtener el token de Microsoft Graph: ' . $response->body());
            }

            return $response->json('access_token');
        });
    }

    /**
     * Enviar un correo electrónico usando Microsoft Graph API.
     *
     * @param string $to        Dirección de correo del destinatario
     * @param string $subject   Asunto del correo
     * @param string $htmlBody  Contenido HTML del correo
     * @param array  $attachments Array de adjuntos [['name' => ..., 'contentType' => ..., 'contentBytes' => base64...]]
     */
    public function sendMail(string $to, string $subject, string $htmlBody, array $attachments = []): bool
    {
        try {
            $token = $this->getAccessToken();

            $message = [
                'message' => [
                    'subject' => $subject,
                    'body' => [
                        'contentType' => 'HTML',
                        'content' => $htmlBody,
                    ],
                    'toRecipients' => [
                        [
                            'emailAddress' => [
                                'address' => $to,
                            ],
                        ],
                    ],
                ],
                'saveToSentItems' => true,
            ];

            if (!empty($attachments)) {
                $message['message']['attachments'] = $attachments;
            }

            $response = Http::withToken($token)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post(
                    "https://graph.microsoft.com/v1.0/users/{$this->fromAddress}/sendMail",
                    $message
                );

            if ($response->successful()) {
                Log::info('Microsoft Graph: Correo enviado exitosamente', [
                    'to' => $to,
                    'subject' => $subject,
                ]);
                return true;
            }

            Log::error('Microsoft Graph: Error al enviar correo', [
                'to' => $to,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return false;

        } catch (\Exception $e) {
            Log::error('Microsoft Graph: Excepción al enviar correo', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
