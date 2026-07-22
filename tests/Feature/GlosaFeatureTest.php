<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\License;
use App\Models\GlosaImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

class GlosaFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_glosa_access_is_blocked(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);

        License::create([
            'license_key'      => License::generateKey(),
            'admin_id'         => $admin->id,
            'duration_type'    => '1month',
            'starts_at'        => now(),
            'expires_at'       => now()->addDays(30),
            'status'           => 'active',
            'has_glosa_access' => false,
            'created_by'       => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get('/glosa');
        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('error');
    }

    public function test_user_with_glosa_access_can_view_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);

        License::create([
            'license_key'      => License::generateKey(),
            'admin_id'         => $admin->id,
            'duration_type'    => '1month',
            'starts_at'        => now(),
            'expires_at'       => now()->addDays(30),
            'status'           => 'active',
            'has_glosa_access' => true,
            'created_by'       => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get('/glosa');
        $response->assertStatus(200);
        $response->assertSee('Glosa Aduanera &amp; Data Stage', false);
    }

    public function test_can_upload_zip_fetch_metrics_and_export_excel(): void
    {
        $sampleZipPath = 'C:\\Users\\Admin\\Downloads\\1920833_solicitudes.zip';
        $admin = User::factory()->create(['role' => 'Admin']);

        License::create([
            'license_key'      => License::generateKey(),
            'admin_id'         => $admin->id,
            'duration_type'    => '1month',
            'starts_at'        => now(),
            'expires_at'       => now()->addDays(30),
            'status'           => 'active',
            'has_glosa_access' => true,
            'created_by'       => $admin->id,
        ]);

        $file = new UploadedFile($sampleZipPath, '1920833_solicitudes.zip', 'application/zip', null, true);

        // 1. Cargar ZIP
        $response = $this->actingAs($admin)->post('/glosa/upload', [
            'zip_file' => $file,
        ]);
        $response->assertRedirect(route('glosa.index'));
        $response->assertSessionHas('success');

        $import = GlosaImport::where('admin_id', $admin->id)->first();
        $this->assertNotNull($import);

        // 2. Probar API de métricas
        $metricsResponse = $this->actingAs($admin)->getJson('/glosa/metrics');
        $metricsResponse->assertStatus(200);
        $metricsResponse->assertJsonStructure([
            'kpis' => ['total_operaciones', 'importaciones', 'exportaciones', 'valor_comercial_usd', 'total_impuestos'],
            'compliance' => ['total_rectificaciones', 'tasa_rectificacion'],
            'charts' => ['tendencia_mensual', 'top_fracciones', 'por_aduana'],
        ]);

        // 3. Probar descarga de Excel
        $exportResponse = $this->actingAs($admin)->get("/glosa/export/{$import->id}");
        $exportResponse->assertStatus(200);
        $exportResponse->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }
}
