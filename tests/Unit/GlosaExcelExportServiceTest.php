<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\License;
use App\Services\GlosaDataStageService;
use App\Services\GlosaExcelExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;

class GlosaExcelExportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_generate_excel_with_26_sheets(): void
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

        $uploadedFile = new UploadedFile($sampleZipPath, '1920833_solicitudes.zip', 'application/zip', null, true);
        $ingestService = new GlosaDataStageService();
        $import = $ingestService->processZipFile($uploadedFile, $admin);

        $exportService = new GlosaExcelExportService();
        $excelFilePath = $exportService->generateExcel($import);

        $this->assertFileExists($excelFilePath);

        $spreadsheet = IOFactory::load($excelFilePath);
        $this->assertEquals(27, $spreadsheet->getSheetCount()); // 26 bóvedas (incluyendo Resumen, Inci, Sel)

        @unlink($excelFilePath);
    }
}
