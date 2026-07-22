<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\License;
use App\Models\GlosaImport;
use App\Services\GlosaDataStageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

class GlosaDataStageServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_process_sample_data_stage_zip(): void
    {
        $sampleZipPath = 'C:\\Users\\Admin\\Downloads\\1920833_solicitudes.zip';
        $this->assertFileExists($sampleZipPath, 'El archivo ZIP de muestra 1920833_solicitudes.zip debe existir en Downloads.');

        $admin = User::factory()->create([
            'role' => 'Admin',
        ]);

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

        $uploadedFile = new UploadedFile(
            $sampleZipPath,
            '1920833_solicitudes.zip',
            'application/zip',
            null,
            true
        );

        $service = new GlosaDataStageService();
        $import = $service->processZipFile($uploadedFile, $admin);

        $this->assertInstanceOf(GlosaImport::class, $import);
        $this->assertEquals('completed', $import->status);
        $this->assertEquals(26, $import->total_files);
        $this->assertGreaterThan(0, $import->total_pedimentos);
        $this->assertGreaterThan(0, $import->total_partidas);
        $this->assertGreaterThan(0, $import->total_valor_dolares);
        $this->assertDatabaseHas('glosa_imports', [
            'id'     => $import->id,
            'status' => 'completed',
        ]);
        $this->assertDatabaseHas('glosa_501_datos_generales', [
            'import_id' => $import->id,
        ]);
    }
}
