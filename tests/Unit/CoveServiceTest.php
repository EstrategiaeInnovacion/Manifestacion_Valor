<?php

namespace Tests\Unit;

use App\Services\CoveService;
use App\Services\EFirmaService;
use Tests\TestCase;

class CoveServiceTest extends TestCase
{
    private function getValidCoveData(): array
    {
        return [
            'tipoOperacion' => 'IMPORTACION',
            'relacionFacturas' => 'SIN RELACION DE FACTURAS',
            'tipoFigura' => 'IMPORTADOR',
            'fechaExpedicion' => '2025-06-01',
            'patentesAduanales' => ['1628'],
            'rfcsConsulta' => ['RIRV691116P84'],
            'observaciones' => 'TEST COVE',
            'facturas' => [
                [
                    'numeroFactura' => '391668',
                    'fechaExpedicion' => '2025-06-01',
                    'subdivision' => 0,
                    'certificadoOrigen' => 0,
                    'numeroExportadorConfiable' => null,
                    'emisor' => [
                        'tipoIdentificador' => 0,
                        'identificacion' => '320656860',
                        'nombre' => 'EXPONENTIAL TECHNOLOGY GROUP, INC.',
                        'domicilio' => [
                            'calle' => 'MARK IV PARKWAY',
                            'numeroExterior' => '5050',
                            'numeroInterior' => null,
                            'codigoPostal' => '76106',
                            'colonia' => 'TEXAS',
                            'localidad' => 'FORT WORTH',
                            'municipio' => 'FORT WORTH',
                            'entidadFederativa' => 'TEXAS',
                            'pais' => 'ESTADOS UNIDOS DE AMERICA'
                        ]
                    ],
                    'destinatario' => [
                        'tipoIdentificador' => 1,
                        'identificacion' => 'AEL1208226X2',
                        'nombre' => 'ARTEKO ELECTRONICS SA DE CV',
                        'domicilio' => [
                            'calle' => 'CORDILLERA HIMALAYA',
                            'numeroExterior' => '910',
                            'numeroInterior' => '11',
                            'codigoPostal' => '78216',
                            'colonia' => 'LOMAS 4A SECCION',
                            'localidad' => 'SAN LUIS POTOSI',
                            'municipio' => 'SAN LUIS POTOSI',
                            'entidadFederativa' => 'SAN LUIS POTOSÍ',
                            'pais' => 'MEXICO'
                        ]
                    ],
                    'mercancias' => [
                        [
                            'descripcionGenerica' => 'MICROCONTROLADORES',
                            'claveUnidadMedida' => 'piece',
                            'tipoMoneda' => 'USD',
                            'cantidad' => 2500.000,
                            'valorUnitario' => 1.760140,
                            'valorTotal' => 4400.350000,
                            'valorDolares' => 4400.3500,
                            'descripcionesEspecificas' => []
                        ]
                    ]
                ]
            ]
        ];
    }

    public function test_validar_datos_cove_returns_true_for_valid_data(): void
    {
        $efirmaMock = $this->createMock(EFirmaService::class);
        $service = new CoveService($efirmaMock);

        $result = $service->validarDatosCove($this->getValidCoveData());

        if (!$result['valido']) {
            fwrite(STDERR, print_r($result['errores'], true));
        }

        $this->assertTrue($result['valido']);
        $this->assertEmpty($result['errores']);
    }

    public function test_validar_datos_cove_returns_false_for_missing_required_fields(): void
    {
        $efirmaMock = $this->createMock(EFirmaService::class);
        $service = new CoveService($efirmaMock);

        $invalidData = $this->getValidCoveData();
        unset($invalidData['tipoOperacion']);
        unset($invalidData['facturas'][0]['emisor']['nombre']);

        $result = $service->validarDatosCove($invalidData);

        $this->assertFalse($result['valido']);
        $this->assertNotEmpty($result['errores']);
    }

    public function test_generar_cadena_original_produces_correct_pipe_concatenation(): void
    {
        $efirmaMock = $this->createMock(EFirmaService::class);
        $service = new CoveService($efirmaMock);

        $cadena = $service->generarCadenaOriginal($this->getValidCoveData());

        $this->assertStringStartsWith('|', $cadena);
        $this->assertStringEndsWith('|', $cadena);
        $this->assertStringContainsString('RIRV691116P84', $cadena);
        $this->assertStringContainsString('1628', $cadena);
        $this->assertStringContainsString('391668', $cadena);
        $this->assertStringContainsString('ARTEKO ELECTRONICS SA DE CV', $cadena);
        $this->assertStringContainsString('MICROCONTROLADORES', $cadena);
        $this->assertStringContainsString('2500.000', $cadena);
        $this->assertStringContainsString('1.76', $cadena);
        $this->assertStringContainsString('4400.35', $cadena);
    }

    public function test_construir_xml_recibir_cove_structures_soap_correctly(): void
    {
        $efirmaMock = $this->createMock(EFirmaService::class);
        $service = new CoveService($efirmaMock);

        $data = $this->getValidCoveData();
        $firma = [
            'certificado' => 'DUMMYCERT',
            'cadenaOriginal' => 'DUMMYCADENA',
            'firma' => 'DUMMYFIRMA'
        ];

        // Inyectar claves para el WS-Security header
        $reflection = new \ReflectionClass($service);
        $rfcProp = $reflection->getProperty('rfc');
        $rfcProp->setAccessible(true);
        $rfcProp->setValue($service, 'RFC123456789');

        $claveProp = $reflection->getProperty('claveWebService');
        $claveProp->setAccessible(true);
        $claveProp->setValue($service, 'WSKEY12345');

        $xml = $service->construirXmlRecibirCove($data, $firma);

        $this->assertStringContainsString('<soapenv:Envelope', $xml);
        $this->assertStringContainsString('<wsse:Security', $xml);
        $this->assertStringContainsString('<oxml:certificado>DUMMYCERT</oxml:certificado>', $xml);
        $this->assertStringContainsString('<oxml:cadenaOriginal>DUMMYCADENA</oxml:cadenaOriginal>', $xml);
        $this->assertStringContainsString('<oxml:firma>DUMMYFIRMA</oxml:firma>', $xml);
        $this->assertStringContainsString('<oxml:tipoOperacion>TOCE.IMP</oxml:tipoOperacion>', $xml);
        $this->assertStringContainsString('<oxml:numeroFacturaOriginal>391668</oxml:numeroFacturaOriginal>', $xml);
    }
}
