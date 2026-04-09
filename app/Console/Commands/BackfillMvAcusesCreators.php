<?php

namespace App\Console\Commands;

use App\Models\MvAcuse;
use App\Models\User;
use App\Services\MicrosoftGraphMailService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Recupera created_by_user_id y folio_interno en mv_acuses históricos
 * consultando los mensajes enviados en la carpeta sentItems del buzón Graph.
 *
 * Uso:
 *   php artisan mve:backfill-creators             → aplica los cambios
 *   php artisan mve:backfill-creators --dry-run   → solo muestra lo que haría
 */
class BackfillMvAcusesCreators extends Command
{
    protected $signature   = 'mve:backfill-creators {--dry-run : Solo muestra qué se actualizaría sin hacer cambios}';
    protected $description = 'Rellena created_by_user_id en mv_acuses históricos consultando sentItems de Microsoft Graph';

    private string $fromAddress;

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $this->fromAddress = config('services.microsoft_graph.from_address');
        if (empty($this->fromAddress)) {
            $this->error('MAIL_FROM_ADDRESS no está configurado en .env');
            return self::FAILURE;
        }

        $this->info($dryRun ? '=== DRY RUN — no se escribirá nada ===' : '=== Iniciando backfill de mv_acuses ===');

        try {
            $token = $this->getAccessToken();
        } catch (\Throwable $e) {
            $this->error('No se pudo obtener token de Microsoft Graph: ' . $e->getMessage());
            return self::FAILURE;
        }

        $mensajes   = $this->fetchSentMessages($token);
        $total      = count($mensajes);
        $this->info("Mensajes MVE encontrados en sentItems: {$total}");

        $stats = [
            'actualizados'         => 0,
            'ya_tenia_creator'     => 0,
            'folio_no_en_bd'       => 0,
            'usuario_no_encontrado'=> 0,
            'omitidos'             => 0,
        ];

        foreach ($mensajes as $msg) {
            [$folio, $recipientEmail] = $this->parseMessage($msg);

            if (!$folio || !$recipientEmail) {
                $stats['omitidos']++;
                continue;
            }

            // Buscar acuse por folio_manifestacion o numero_cove
            $acuse = MvAcuse::where(function ($q) use ($folio) {
                $q->where('folio_manifestacion', $folio)
                  ->orWhere('numero_cove', $folio);
            })->first();

            if (!$acuse) {
                $this->line("  ⚠  Folio <comment>{$folio}</comment> no encontrado en mv_acuses");
                $stats['folio_no_en_bd']++;
                continue;
            }

            // Ya tiene creator → no tocar
            if ($acuse->created_by_user_id !== null) {
                $this->line("  ✓  Folio <info>{$folio}</info> ya tiene creator (id={$acuse->created_by_user_id})");
                $stats['ya_tenia_creator']++;
                continue;
            }

            // Buscar usuario por email "Para:"
            $user = User::where('email', $recipientEmail)->first();

            // Si no encontramos por "Para:", intentar con CC (puede ser admin)
            if (!$user) {
                $ccEmails = collect($msg['ccRecipients'] ?? [])
                    ->pluck('emailAddress.address')
                    ->filter()
                    ->toArray();

                foreach ($ccEmails as $cc) {
                    $user = User::where('email', $cc)->first();
                    if ($user && $user->role !== 'Admin' && $user->role !== 'SuperAdmin') {
                        break; // Preferir usuario no-admin en CC
                    }
                }
            }

            if (!$user) {
                $this->line("  ✗  Folio <comment>{$folio}</comment>: usuario '{$recipientEmail}' no existe en users");
                $stats['usuario_no_encontrado']++;
                continue;
            }

            $folioInterno = $acuse->folio_interno; // no sobrescribir si ya existe

            $this->line("  →  Folio <info>{$folio}</info> → <info>{$user->full_name}</info> ({$user->email})");

            if (!$dryRun) {
                $acuse->update([
                    'created_by_user_id' => $user->id,
                    // folio_interno no se puede recuperar de los correos existentes;
                    // se rellenará automáticamente en nuevas MVEs desde el fix del código
                ]);
            }

            $stats['actualizados']++;
        }

        $this->newLine();
        $this->info('=== Resumen ===');
        $this->table(
            ['Estado', 'Cantidad'],
            [
                [$dryRun ? 'Se actualizarían' : 'Actualizados',          $stats['actualizados']],
                ['Ya tenían creator (sin cambio)',                         $stats['ya_tenia_creator']],
                ['Folio no encontrado en BD',                              $stats['folio_no_en_bd']],
                ['Usuario (Para:) no encontrado en users',                 $stats['usuario_no_encontrado']],
                ['Mensajes sin folio/email parseable',                     $stats['omitidos']],
            ]
        );

        if ($dryRun) {
            $this->warn('Ejecuta sin --dry-run para aplicar los cambios.');
        }

        return self::SUCCESS;
    }

    /**
     * Obtiene todos los mensajes de sentItems cuyo asunto contiene "MVE Enviada".
     * Pagina automáticamente con @odata.nextLink.
     */
    private function fetchSentMessages(string $token): array
    {
        $mensajes = [];
        // $search requiere el header ConsistencyLevel: eventual y no acepta $orderby
        $url = "https://graph.microsoft.com/v1.0/users/{$this->fromAddress}/mailFolders/sentItems/messages"
             . "?\$search=\"subject:MVE Enviada\""
             . "&\$select=subject,toRecipients,ccRecipients,sentDateTime"
             . "&\$top=500";

        $paginas = 0;
        while ($url && $paginas < 20) { // máximo 20 páginas = 10,000 mensajes
            $response = Http::withToken($token)
                ->withHeaders(['ConsistencyLevel' => 'eventual'])
                ->timeout(30)
                ->get($url);

            if (!$response->successful()) {
                $this->warn('Graph API error: ' . $response->status() . ' ' . $response->body());
                break;
            }

            $data      = $response->json();
            $mensajes  = array_merge($mensajes, $data['value'] ?? []);
            $url       = $data['@odata.nextLink'] ?? null;
            $paginas++;

            if ($url) {
                $this->line("  Paginando... ({$paginas}) total hasta ahora: " . count($mensajes));
            }
        }

        return $mensajes;
    }

    /**
     * Extrae folio del asunto y email del destinatario "Para:".
     * Asunto esperado: "MVE Enviada — Folio: 163860"
     *
     * @return array{0: string|null, 1: string|null}
     */
    private function parseMessage(array $msg): array
    {
        $subject = $msg['subject'] ?? '';
        $folio   = null;

        if (preg_match('/Folio:\s*([^\s\-\|]+)/ui', $subject, $m)) {
            $folio = trim($m[1]);
        }

        $recipientEmail = $msg['toRecipients'][0]['emailAddress']['address'] ?? null;

        return [$folio, $recipientEmail];
    }

    /**
     * Obtiene token de Graph API, con cache para reutilizar.
     */
    private function getAccessToken(): string
    {
        $tenantId     = config('services.microsoft_graph.tenant_id');
        $clientId     = config('services.microsoft_graph.client_id');
        $clientSecret = config('services.microsoft_graph.client_secret');

        // Intentar reutilizar el token cacheado por MicrosoftGraphMailService
        return Cache::remember('microsoft_graph_token_backfill', 3500, function () use ($tenantId, $clientId, $clientSecret) {
            $response = Http::asForm()->post(
                "https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token",
                [
                    'client_id'     => $clientId,
                    'client_secret' => $clientSecret,
                    'scope'         => 'https://graph.microsoft.com/.default',
                    'grant_type'    => 'client_credentials',
                ]
            );

            if (!$response->successful()) {
                throw new \RuntimeException('Graph token error: ' . $response->body());
            }

            return $response->json('access_token');
        });
    }
}
