<?php

namespace Tests\Unit;

use App\Models\MvClientApplicant;
use App\Services\ManifestacionValorService;
use Tests\TestCase;

class ManifestacionValorServiceTest extends TestCase
{
    public function test_parse_pedimento_edocuments_extracts_unique_folios(): void
    {
        $service = new ManifestacionValorService();
        $layout = "507|12345|ED|ABC12345|OTRO|\n507|12345|ED|abc12345|OTRO|\n507|99999|ED|ZZZ99999|OTRO|";

        $folios = $service->parsePedimentoEdocuments($layout);

        $this->assertSame(['ABC12345', 'ZZZ99999'], $folios);
    }

    public function test_build_registro_manifestacion_xml_includes_documents(): void
    {
        $service = new ManifestacionValorService();
        $xml = $service->buildRegistroManifestacionXml([
            ['folio_edocument' => 'ABC12345'],
            ['folio_edocument' => ''],
            ['folio_edocument' => 'XYZ99999'],
        ]);

        $this->assertStringContainsString('<eDocument>ABC12345</eDocument>', $xml);
        $this->assertStringContainsString('<eDocument>XYZ99999</eDocument>', $xml);
        $this->assertSame(2, substr_count($xml, '<eDocument>'));
    }

    public function test_build_cadena_original_uses_edocument_folios_in_order(): void
    {
        $service = new ManifestacionValorService();
        $applicant = new MvClientApplicant();
        $applicant->applicant_rfc = 'RFC123456789';

        $cadenaOriginal = $service->buildCadenaOriginal(
            $applicant,
            null,
            null,
            null,
            [
                ['folio_edocument' => 'FOLIOUNO'],
                ['folio_edocument' => 'FOLIODOS'],
            ]
        );

        $parts = explode('|', trim($cadenaOriginal, '|'));

        $this->assertSame('RFC123456789', $parts[0]);
        $this->assertSame('', $parts[1]);
        $this->assertSame('', $parts[2]);
        $this->assertSame('FOLIOUNO', $parts[3]);
        $this->assertSame('FOLIODOS', $parts[4]);
    }
}
