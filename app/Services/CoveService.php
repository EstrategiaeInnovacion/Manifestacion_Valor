<?php

namespace App\Services;

use App\Models\Cove;
use App\Models\MvClientApplicant;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use SoapClient;
use SoapFault;

class CoveService
{
    private EFirmaService $efirmaService;
    private ?string $rfc = null;
    private ?string $claveWebService = null;
    private array $debugInfo = [];

    // Namespaces oficiales para COVE VUCEM
    private const NS_SOAP = 'http://schemas.xmlsoap.org/soap/envelope/';
    private const NS_COVE_SERVICE = 'http://www.ventanillaunica.gob.mx/cove/ws/service/';
    private const NS_COVE_OXML = 'http://www.ventanillaunica.gob.mx/cove/ws/oxml/';

    // Diccionarios de equivalencias de catálogos VUCEM
    private const PAISES_MAP = [
        'ESTADOS UNIDOS DE AMERICA' => 'USA',
        'ESTADOS UNIDOS'           => 'USA',
        'USA'                      => 'USA',
        'MEXICO'                   => 'MEX',
        'MÉXICO'                   => 'MEX',
        'MEX'                      => 'MEX',
    ];

    private const UNIDADES_MEDIDA_MAP = [
        'PIECE' => '2',
        'PZA'   => '2',
        'PZS'   => '2',
        'PIEZAS'=> '2',
        'PIEZA' => '2',
        'KG'    => '1',
        'KILO'  => '1',
        'LITRO' => '5',
        'L'     => '5',
    ];

    private const TIPO_FIGURA_MAP = [
        'AGENTE ADUANAL' => '1',
        'EXPORTADOR'     => '4',
        'IMPORTADOR'     => '5',
    ];

    private const TIPO_OPERACION_MAP = [
        'IMPORTACION' => 'TOCE.IMP',
        'IMPORTACIÓN' => 'TOCE.IMP',
        'EXPORTACION' => 'TOCE.EXP',
        'EXPORTACIÓN' => 'TOCE.EXP',
    ];

    public function __construct(EFirmaService $efirmaService)
    {
        $this->efirmaService = $efirmaService;
    }

    /**
     * Escapa caracteres especiales (&, ñ, Ñ) para el XML SOAP de VUCEM
     */
    private function escapeXmlEntities(string $value): string
    {
        $value = htmlspecialchars($value, ENT_XML1, 'UTF-8');
        
        // Reemplazar ñ y Ñ según los lineamientos de VUCEM
        $value = str_replace('ñ', '&ntilde;', $value);
        $value = str_replace('Ñ', '&Ntilde;', $value);
        
        return $value;
    }

    /**
     * Valida los datos del COVE según el check-list QSI-059
     * 
     * @param array $data Estructura JSON del COVE
     * @return array ['valido' => bool, 'errores' => array]
     */
    public function validarDatosCove(array $data): array
    {
        $rules = [
            'tipoOperacion' => 'required|in:IMPORTACION,EXPORTACION,Importación,Exportación',
            'relacionFacturas' => 'required|in:SIN_RELACION_FACTURAS,CON_RELACION_FACTURAS,SIN RELACION DE FACTURAS,CON RELACION DE FACTURAS',
            'tipoFigura' => 'required|string',
            'patentesAduanales' => 'nullable|array',
            'rfcsConsulta' => 'required|array|min:1',
            'rfcsConsulta.*' => 'required|string|regex:/^[A-Z&N]{3,4}[0-9]{6}[A-Z0-9]{3}$/i',
            'correoElectronico' => 'nullable|email|max:100',
            'fechaExpedicion' => 'required|date_format:Y-m-d',
            
            // Factura y Datos
            'facturas' => 'required|array|min:1',
            'facturas.*.numeroFactura' => 'required|string|max:40',
            'facturas.*.fechaExpedicion' => 'required|date_format:Y-m-d',
            'facturas.*.subdivision' => 'required|integer|in:0,1', // 0 = Sin subdivisión, 1 = Con subdivisión
            'facturas.*.certificadoOrigen' => 'required|integer|in:0,1', // 0 = No funge como Certificado de Origen, 1 = Sí funge
            'facturas.*.numeroExportadorConfiable' => 'nullable|string',
            
            // Emisor (Proveedor)
            'facturas.*.emisor.tipoIdentificador' => 'required|integer|in:0,1,2,3',
            'facturas.*.emisor.identificacion' => 'required|string|max:50',
            'facturas.*.emisor.nombre' => 'required|string|max:120',
            'facturas.*.emisor.domicilio.calle' => 'required|string|max:100',
            'facturas.*.emisor.domicilio.numeroExterior' => 'required|string|max:50',
            'facturas.*.emisor.domicilio.numeroInterior' => 'nullable|string|max:50',
            'facturas.*.emisor.domicilio.codigoPostal' => 'required|string|max:10',
            'facturas.*.emisor.domicilio.entidadFederativa' => 'required|string|max:50',
            'facturas.*.emisor.domicilio.pais' => 'required|string|max:50',

            // Destinatario
            'facturas.*.destinatario.tipoIdentificador' => 'required|integer|in:1', // Solo RFC admitido para importador mexicano
            'facturas.*.destinatario.identificacion' => 'required|string|regex:/^[A-Z&N]{3,4}[0-9]{6}[A-Z0-9]{3}$/i',
            'facturas.*.destinatario.nombre' => 'required|string|max:120',
            'facturas.*.destinatario.domicilio.calle' => 'required|string|max:100',
            'facturas.*.destinatario.domicilio.numeroExterior' => 'required|string|max:50',
            'facturas.*.destinatario.domicilio.numeroInterior' => 'nullable|string|max:50',
            'facturas.*.destinatario.domicilio.colonia' => 'required|string|max:100',
            'facturas.*.destinatario.domicilio.codigoPostal' => 'required|string|max:10',
            'facturas.*.destinatario.domicilio.municipio' => 'required|string|max:50',
            'facturas.*.destinatario.domicilio.entidadFederativa' => 'required|string|max:50',
            'facturas.*.destinatario.domicilio.pais' => 'required|string|max:50',

            // Mercancías
            'facturas.*.mercancias' => 'required|array|min:1',
            'facturas.*.mercancias.*.descripcionGenerica' => 'required|string|max:250',
            'facturas.*.mercancias.*.claveUnidadMedida' => 'required|string|max:5',
            'facturas.*.mercancias.*.tipoMoneda' => 'required|string|max:5',
            'facturas.*.mercancias.*.cantidad' => 'required|numeric|gt:0',
            'facturas.*.mercancias.*.valorUnitario' => 'required|numeric|gte:0',
            'facturas.*.mercancias.*.valorTotal' => 'required|numeric|gte:0',
            'facturas.*.mercancias.*.valorDolares' => 'required|numeric|gte:0',
        ];

        $validator = Validator::make($data, $rules);

        return [
            'valido' => !$validator->fails(),
            'errores' => $validator->errors()->all()
        ];
    }

    /**
     * Genera la Cadena Original de COVE según el estándar de VUCEM
     * 
     * Formato general concatenado por pipes (|):
     * |rfcsConsulta|patenteAduanal|tipoOperacion|numeroFacturaRelacionFacturas|relacionFacturas|automotriz|tipoFigura|observaciones|
     * Y después por cada factura, emisor, destinatario, mercancía, etc.
     */
    public function generarCadenaOriginal(array $data): string
    {
        $chain = [];

        // 1. tipoOperacion (obligatorio)
        $rawOperacion = strtoupper(trim($data['tipoOperacion'] ?? 'IMPORTACION'));
        $tipoOperacion = self::TIPO_OPERACION_MAP[$rawOperacion] ?? 'TOCE.IMP';
        if (!empty($tipoOperacion)) {
            $chain[] = $tipoOperacion;
        }

        // 2. numeroFacturaOriginal (facturas[0].numeroFactura)
        $numFacturaOrig = trim($data['numeroFacturaRelacionFacturas'] ?? $data['facturas'][0]['numeroFactura'] ?? '');
        if (!empty($numFacturaOrig)) {
            $chain[] = $numFacturaOrig;
        }

        // 3. Parent node check: para solicitarRecibirCoveServicio siempre es |0
        $chain[] = '0';

        // 4. fechaExpedicion (primeros 10 caracteres)
        $fechaExpedicion = trim($data['fechaExpedicion'] ?? $data['facturas'][0]['fechaExpedicion'] ?? date('Y-m-d'));
        if (!empty($fechaExpedicion)) {
            $chain[] = substr($fechaExpedicion, 0, 10);
        }

        // 5. tipoFigura
        $rawFigura = strtoupper(trim($data['tipoFigura'] ?? 'IMPORTADOR'));
        $tipoFigura = self::TIPO_FIGURA_MAP[$rawFigura] ?? '5';
        if (!empty($tipoFigura)) {
            $chain[] = $tipoFigura;
        }

        // 6. observaciones
        $observaciones = trim($data['observaciones'] ?? '');
        if (!empty($observaciones)) {
            $chain[] = $observaciones;
        }

        // 7. rfcConsulta
        $rfcs = $data['rfcsConsulta'] ?? [];
        foreach ($rfcs as $rfc) {
            $rfcVal = trim($rfc);
            if (!empty($rfcVal)) {
                $chain[] = $rfcVal;
            }
        }

        // 8. patenteAduanal
        $patentes = $data['patentesAduanales'] ?? [];
        foreach ($patentes as $patente) {
            $patenteVal = trim($patente);
            if (!empty($patenteVal)) {
                $chain[] = $patenteVal;
            }
        }

        // 9. Facturas y sus componentes
        $facturas = $data['facturas'] ?? [];
        foreach ($facturas as $factura) {
            // factura tag
            $subdiv = $factura['subdivision'] ?? 0;
            $chain[] = $subdiv;
            $certOrig = $factura['certificadoOrigen'] ?? 0;
            $chain[] = $certOrig;
            $expConf = trim($factura['numeroExportadorConfiable'] ?? '');
            if (!empty($expConf)) {
                $chain[] = $expConf;
            }

            // Emisor de la factura
            if (isset($factura['emisor'])) {
                $emisor = $factura['emisor'];
                
                $tipoId = (string)($emisor['tipoIdentificador'] ?? '');
                if ($tipoId !== '') $chain[] = $tipoId;
                
                $ident = trim($emisor['identificacion'] ?? '');
                if ($ident !== '') $chain[] = $ident;
                
                $apPat = trim($emisor['apellidoPaterno'] ?? '');
                if ($apPat !== '') $chain[] = $apPat;
                
                $apMat = trim($emisor['apellidoMaterno'] ?? '');
                if ($apMat !== '') $chain[] = $apMat;
                
                $nombre = trim($emisor['nombre'] ?? '');
                if ($nombre !== '') $chain[] = $nombre;

                if (isset($emisor['domicilio'])) {
                    $dom = $emisor['domicilio'];
                    
                    $calle = trim($dom['calle'] ?? '');
                    if ($calle !== '') $chain[] = $calle;
                    
                    $numExt = trim($dom['numeroExterior'] ?? '');
                    if ($numExt !== '') $chain[] = $numExt;
                    
                    $numInt = trim($dom['numeroInterior'] ?? '');
                    if ($numInt !== '') $chain[] = $numInt;
                    
                    $colonia = trim($dom['colonia'] ?? '');
                    if ($colonia !== '') $chain[] = $colonia;
                    
                    $localidad = trim($dom['localidad'] ?? '');
                    if ($localidad !== '') $chain[] = $localidad;
                    
                    $municipio = trim($dom['municipio'] ?? '');
                    if ($municipio !== '') $chain[] = $municipio;
                    
                    $entFed = trim($dom['entidadFederativa'] ?? '');
                    if ($entFed !== '') $chain[] = $entFed;
                    
                    $rawPais = strtoupper(trim($dom['pais'] ?? 'MEXICO'));
                    $pais = self::PAISES_MAP[$rawPais] ?? 'MEX';
                    if ($pais !== '') $chain[] = $pais;

                    $cp = trim($dom['codigoPostal'] ?? '');
                    if ($cp !== '') $chain[] = $cp;
                }
            }

            // Destinatario de la factura
            if (isset($factura['destinatario'])) {
                $dest = $factura['destinatario'];
                
                $tipoId = (string)($dest['tipoIdentificador'] ?? '');
                if ($tipoId !== '') $chain[] = $tipoId;
                
                $ident = trim($dest['identificacion'] ?? '');
                if ($ident !== '') $chain[] = $ident;
                
                $apPat = trim($dest['apellidoPaterno'] ?? '');
                if ($apPat !== '') $chain[] = $apPat;
                
                $apMat = trim($dest['apellidoMaterno'] ?? '');
                if ($apMat !== '') $chain[] = $apMat;
                
                $nombre = trim($dest['nombre'] ?? '');
                if ($nombre !== '') $chain[] = $nombre;

                if (isset($dest['domicilio'])) {
                    $dom = $dest['domicilio'];
                    
                    $calle = trim($dom['calle'] ?? '');
                    if ($calle !== '') $chain[] = $calle;
                    
                    $numExt = trim($dom['numeroExterior'] ?? '');
                    if ($numExt !== '') $chain[] = $numExt;
                    
                    $numInt = trim($dom['numeroInterior'] ?? '');
                    if ($numInt !== '') $chain[] = $numInt;
                    
                    $colonia = trim($dom['colonia'] ?? '');
                    if ($colonia !== '') $chain[] = $colonia;
                    
                    $localidad = trim($dom['localidad'] ?? '');
                    if ($localidad !== '') $chain[] = $localidad;
                    
                    $municipio = trim($dom['municipio'] ?? '');
                    if ($municipio !== '') $chain[] = $municipio;
                    
                    $entFed = trim($dom['entidadFederativa'] ?? '');
                    if ($entFed !== '') $chain[] = $entFed;
                    
                    $rawPais = strtoupper(trim($dom['pais'] ?? 'MEXICO'));
                    $pais = self::PAISES_MAP[$rawPais] ?? 'MEX';
                    if ($pais !== '') $chain[] = $pais;

                    $cp = trim($dom['codigoPostal'] ?? '');
                    if ($cp !== '') $chain[] = $cp;
                }
            }

            // Mercancías
            $mercancias = $factura['mercancias'] ?? [];
            foreach ($mercancias as $merc) {
                $chain[] = trim($merc['descripcionGenerica'] ?? '');

                // Traducir Unidad de Medida dinámicamente desde la BD
                // Si la tabla cove_units no existe (ej. en tests), cae al mapa hardcodeado
                $rawUM = strtolower(trim($merc['claveUnidadMedida'] ?? 'piece'));
                try {
                    $dbUnit = \App\Models\CoveUnit::where('name', $rawUM)->first() 
                        ?? \App\Models\CoveUnit::where('cove_code', $rawUM)->first();
                } catch (\Exception $e) {
                    $dbUnit = null;
                }
                $claveUM = $dbUnit ? $dbUnit->cove_code : (self::UNIDADES_MEDIDA_MAP[strtoupper($rawUM)] ?? 'C62_1');
                $chain[] = $claveUM;

                $chain[] = number_format((float)($merc['cantidad'] ?? 0), 3, '.', '');
                $chain[] = trim($merc['tipoMoneda'] ?? '');
                $chain[] = number_format((float)($merc['valorUnitario'] ?? 0), 2, '.', '');
                $chain[] = number_format((float)($merc['valorTotal'] ?? 0), 2, '.', '');
                $chain[] = number_format((float)($merc['valorDolares'] ?? 0), 4, '.', '');

                // Descripciones específicas (si existen)
                $detalles = $merc['descripcionesEspecificas'] ?? [];
                foreach ($detalles as $det) {
                    $marca = trim($det['marca'] ?? '');
                    $modelo = trim($det['modelo'] ?? '');
                    $subMod = trim($det['subModelo'] ?? '');
                    $serie = trim($det['numeroSerie'] ?? '');

                    // En la cadena original, los campos opcionales vacíos se omiten del pipe (no se concatenan)
                    if ($marca !== '') $chain[] = $marca;
                    if ($modelo !== '') $chain[] = $modelo;
                    if ($subMod !== '') $chain[] = $subMod;
                    if ($serie !== '') $chain[] = $serie;
                }
            }
        }

        // Retornar cadena con pipes inicial y final
        return '|' . implode('|', $chain) . '|';
    }

    public function construirXmlRecibirCove(array $data, array $firma): string
    {
        $rfc = htmlspecialchars($this->rfc, ENT_XML1);
        
        // Traducir Tipo Operación
        $tipoOperacion = htmlspecialchars($data['tipoOperacion'] ?? 'IMPORTACION', ENT_XML1);
        $tipoOperacion = self::TIPO_OPERACION_MAP[strtoupper($tipoOperacion)] ?? 'TOCE.IMP';
        $tipoOperacion = $this->escapeXmlEntities($tipoOperacion);

        $fechaExpedicion = trim($data['fechaExpedicion'] ?? $data['facturas'][0]['fechaExpedicion'] ?? date('Y-m-d'));
        $fechaExpedicion = substr($fechaExpedicion, 0, 10);

        $numeroFacturaRelacionFacturas = $this->escapeXmlEntities($data['numeroFacturaRelacionFacturas'] ?? $data['facturas'][0]['numeroFactura'] ?? '');
        $relacionFacturas = $this->escapeXmlEntities(strtoupper(str_replace(' ', '_', $data['relacionFacturas'] ?? 'SIN_RELACION_FACTURAS')));
        $automotriz = $this->escapeXmlEntities(strtoupper($data['automotriz'] ?? 'NO'));

        // Traducir Tipo Figura
        $rawFigura = strtoupper(trim($data['tipoFigura'] ?? 'IMPORTADOR'));
        $tipoFigura = self::TIPO_FIGURA_MAP[$rawFigura] ?? '5';
        $tipoFigura = $this->escapeXmlEntities($tipoFigura);

        $observaciones = $this->escapeXmlEntities($data['observaciones'] ?? '');
        $correoElectronico = $this->escapeXmlEntities($data['correoElectronico'] ?? '');

        // Generar XML de RFCS Consulta
        $rfcsXml = '';
        foreach (($data['rfcsConsulta'] ?? []) as $rfcC) {
            $rfcsXml .= "<oxml:rfcConsulta>" . $this->escapeXmlEntities($rfcC) . "</oxml:rfcConsulta>\n";
        }

        // Generar XML de Patentes
        $patentesXml = '';
        foreach (($data['patentesAduanales'] ?? []) as $patente) {
            $patentesXml .= "<oxml:patenteAduanal>" . $this->escapeXmlEntities($patente) . "</oxml:patenteAduanal>\n";
        }

        // Generar XML de Facturas y datos internos del comprobante (emisor, destinatario, mercancías)
        // NOTA: En el XML correcto de VUCEM, estos datos van ordenados al final
        $facturaInnerXml = '';
        $emisorXml = '';
        $destXml = '';
        $mercanciasXml = '';

        foreach (($data['facturas'] ?? []) as $factura) {
            $numFact = $this->escapeXmlEntities($factura['numeroFactura'] ?? '');
            $certOrig = (int)($factura['certificadoOrigen'] ?? 0);
            $expConf = $this->escapeXmlEntities($factura['numeroExportadorConfiable'] ?? '');
            $subdiv = (int)($factura['subdivision'] ?? 0);

            $emisorXml .= $this->construirPersonaXml($factura['emisor'] ?? [], 'emisor');
            $destXml .= $this->construirPersonaXml($factura['destinatario'] ?? [], 'destinatario');

            // Mercancías
            foreach (($factura['mercancias'] ?? []) as $merc) {
                $descGen = $this->escapeXmlEntities($merc['descripcionGenerica'] ?? '');

                // Traducir Unidad de Medida dinámicamente desde la BD
                // Si la tabla cove_units no existe (ej. en tests), cae al mapa hardcodeado
                $rawUM = strtolower(trim($merc['claveUnidadMedida'] ?? 'piece'));
                try {
                    $dbUnit = \App\Models\CoveUnit::where('name', $rawUM)->first() 
                        ?? \App\Models\CoveUnit::where('cove_code', $rawUM)->first();
                } catch (\Exception $e) {
                    $dbUnit = null;
                }
                $claveUM = $dbUnit ? $dbUnit->cove_code : (self::UNIDADES_MEDIDA_MAP[strtoupper($rawUM)] ?? 'C62_1');
                $claveUM = $this->escapeXmlEntities($claveUM);

                $tipoMon = $this->escapeXmlEntities($merc['tipoMoneda'] ?? '');
                $cant = number_format((float)($merc['cantidad'] ?? 0), 3, '.', '');
                $valUnit = number_format((float)($merc['valorUnitario'] ?? 0), 2, '.', '');
                $valTot = number_format((float)($merc['valorTotal'] ?? 0), 2, '.', '');
                $valDol = number_format((float)($merc['valorDolares'] ?? 0), 4, '.', '');

                $especificasXml = '';
                foreach (($merc['descripcionesEspecificas'] ?? []) as $det) {
                    $marca = $this->escapeXmlEntities(trim($det['marca'] ?? ''));
                    $modelo = $this->escapeXmlEntities(trim($det['modelo'] ?? ''));
                    $subMod = $this->escapeXmlEntities(trim($det['subModelo'] ?? ''));
                    $serie = $this->escapeXmlEntities(trim($det['numeroSerie'] ?? ''));

                    if ($marca !== '' || $modelo !== '' || $subMod !== '' || $serie !== '') {
                        $especificasXml .= "
                <oxml:descripcionesEspecificas>
                    " . ($marca !== '' ? "<oxml:marca>{$marca}</oxml:marca>" : "") . "
                    " . ($modelo !== '' ? "<oxml:modelo>{$modelo}</oxml:modelo>" : "") . "
                    " . ($subMod !== '' ? "<oxml:subModelo>{$subMod}</oxml:subModelo>" : "") . "
                    " . ($serie !== '' ? "<oxml:numeroSerie>{$serie}</oxml:numeroSerie>" : "") . "
                </oxml:descripcionesEspecificas>";
                    }
                }

                $mercanciasXml .= "
            <oxml:mercancias>
                <oxml:descripcionGenerica>{$descGen}</oxml:descripcionGenerica>
                <oxml:claveUnidadMedida>{$claveUM}</oxml:claveUnidadMedida>
                <oxml:tipoMoneda>{$tipoMon}</oxml:tipoMoneda>
                <oxml:cantidad>{$cant}</oxml:cantidad>
                <oxml:valorUnitario>{$valUnit}</oxml:valorUnitario>
                <oxml:valorTotal>{$valTot}</oxml:valorTotal>
                <oxml:valorDolares>{$valDol}</oxml:valorDolares>
                {$especificasXml}
            </oxml:mercancias>";
            }

            $facturaInnerXml .= "
            <oxml:factura>
                <oxml:certificadoOrigen>{$certOrig}</oxml:certificadoOrigen>
                " . (!empty($expConf) ? "<oxml:numeroExportadorAutorizado>{$expConf}</oxml:numeroExportadorAutorizado>" : "") . "
                <oxml:subdivision>{$subdiv}</oxml:subdivision>
            </oxml:factura>";
        }

        // WS-Security Header
        $created = gmdate("Y-m-d\TH:i:s\Z");
        $expires = gmdate("Y-m-d\TH:i:s\Z", strtotime('+5 minutes'));
        $claveWS = $this->escapeXmlEntities($this->claveWebService);

        // Construir XML respetando el orden estricto del XSD oficial de VUCEM:
        // tipoOperacion -> patenteAduanal -> fechaExpedicion -> observaciones -> rfcConsulta -> tipoFigura -> correoElectronico -> firmaElectronica -> numeroFacturaOriginal -> factura -> emisor -> destinatario -> mercancias
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="' . self::NS_SOAP . '" xmlns:ser="' . self::NS_COVE_SERVICE . '" xmlns:oxml="' . self::NS_COVE_OXML . '">
   <soapenv:Header>
      <wsse:Security soapenv:mustUnderstand="1" xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd" xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd">
         <wsu:Timestamp wsu:Id="TS-1">
            <wsu:Created>' . $created . '</wsu:Created>
            <wsu:Expires>' . $expires . '</wsu:Expires>
         </wsu:Timestamp>
         <wsse:UsernameToken wsu:Id="UsernameToken-1">
            <wsse:Username>' . $rfc . '</wsse:Username>
            <wsse:Password Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText">' . $claveWS . '</wsse:Password>
         </wsse:UsernameToken>
      </wsse:Security>
   </soapenv:Header>
   <soapenv:Body>
      <oxml:solicitarRecibirCoveServicio>
         <oxml:comprobantes>
            <oxml:tipoOperacion>' . $tipoOperacion . '</oxml:tipoOperacion>
            ' . $patentesXml . '
            <oxml:fechaExpedicion>' . $this->escapeXmlEntities($fechaExpedicion) . '</oxml:fechaExpedicion>
            ' . (!empty($observaciones) ? "<oxml:observaciones>{$observaciones}</oxml:observaciones>" : "") . '
            ' . $rfcsXml . '
            <oxml:tipoFigura>' . $tipoFigura . '</oxml:tipoFigura>
            ' . (!empty($correoElectronico) ? "<oxml:correoElectronico>{$correoElectronico}</oxml:correoElectronico>" : "") . '
            <oxml:firmaElectronica>
               <oxml:certificado>' . $firma['certificado'] . '</oxml:certificado>
               <oxml:cadenaOriginal>' . htmlspecialchars($firma['cadenaOriginal'], ENT_XML1) . '</oxml:cadenaOriginal>
               <oxml:firma>' . $firma['firma'] . '</oxml:firma>
            </oxml:firmaElectronica>
            ' . (!empty($data['eDocumentOriginal']) ? "<oxml:eDocument>" . $this->escapeXmlEntities(trim($data['eDocumentOriginal'])) . "</oxml:eDocument>" : "") . '
            <oxml:numeroFacturaOriginal>' . $numeroFacturaRelacionFacturas . '</oxml:numeroFacturaOriginal>
            ' . $facturaInnerXml . '
            ' . $emisorXml . '
            ' . $destXml . '
            ' . $mercanciasXml . '
         </oxml:comprobantes>
      </oxml:solicitarRecibirCoveServicio>
   </soapenv:Body>
</soapenv:Envelope>';

        return $xml;
    }

    private function construirPersonaXml(array $persona, string $tagName): string
    {
        if (empty($persona)) {
            return '';
        }

        $tipoId = (int)($persona['tipoIdentificador'] ?? 0);
        $ident = $this->escapeXmlEntities($persona['identificacion'] ?? '');
        $apPat = $this->escapeXmlEntities($persona['apellidoPaterno'] ?? '');
        $apMat = $this->escapeXmlEntities($persona['apellidoMaterno'] ?? '');
        $nombre = $this->escapeXmlEntities($persona['nombre'] ?? '');

        $domXml = '';
        if (isset($persona['domicilio'])) {
            $dom = $persona['domicilio'];
            $calle = $this->escapeXmlEntities($dom['calle'] ?? '');
            $numExt = $this->escapeXmlEntities($dom['numeroExterior'] ?? '');
            $numInt = $this->escapeXmlEntities($dom['numeroInterior'] ?? '');
            $colonia = $this->escapeXmlEntities($dom['colonia'] ?? '');
            $local = $this->escapeXmlEntities($dom['localidad'] ?? '');
            $mun = $this->escapeXmlEntities($dom['municipio'] ?? '');
            $entFed = $this->escapeXmlEntities($dom['entidadFederativa'] ?? '');
            
            // Traducir País
            $rawPais = strtoupper(trim($dom['pais'] ?? 'MEXICO'));
            $pais = self::PAISES_MAP[$rawPais] ?? 'MEX';
            $pais = $this->escapeXmlEntities($pais);

            $cp = $this->escapeXmlEntities($dom['codigoPostal'] ?? '');

            $domXml = "
                <oxml:domicilio>
                    <oxml:calle>{$calle}</oxml:calle>
                    <oxml:numeroExterior>{$numExt}</oxml:numeroExterior>
                    " . (!empty($numInt) ? "<oxml:numeroInterior>{$numInt}</oxml:numeroInterior>" : "") . "
                    " . (!empty($colonia) ? "<oxml:colonia>{$colonia}</oxml:colonia>" : "") . "
                    " . (!empty($local) ? "<oxml:localidad>{$local}</oxml:localidad>" : "") . "
                    " . (!empty($mun) ? "<oxml:municipio>{$mun}</oxml:municipio>" : "") . "
                    <oxml:entidadFederativa>{$entFed}</oxml:entidadFederativa>
                    <oxml:pais>{$pais}</oxml:pais>
                    <oxml:codigoPostal>{$cp}</oxml:codigoPostal>
                </oxml:domicilio>";
        }

        return "
            <oxml:{$tagName}>
                <oxml:tipoIdentificador>{$tipoId}</oxml:tipoIdentificador>
                <oxml:identificacion>{$ident}</oxml:identificacion>
                " . (!empty($apPat) ? "<oxml:apellidoPaterno>{$apPat}</oxml:apellidoPaterno>" : "") . "
                " . (!empty($apMat) ? "<oxml:apellidoMaterno>{$apMat}</oxml:apellidoMaterno>" : "") . "
                <oxml:nombre>{$nombre}</oxml:nombre>
                {$domXml}
            </oxml:{$tagName}>";
    }

    /**
     * Transmite el COVE a VUCEM utilizando cURL
     */
    public function transmitirCove(Cove $cove): array
    {
        $applicant = $cove->applicant;
        if (!$applicant) {
            return ['success' => false, 'message' => 'No se encontró el solicitante asociado al COVE.'];
        }

        $this->rfc = trim($applicant->applicant_rfc);
        $this->claveWebService = trim($applicant->vucem_webservice_key);

        // 1. Validar los datos del COVE
        $data = $cove->cove_json;
        $val = $this->validarDatosCove($data);
        if (!$val['valido']) {
            $msg = 'El COVE no cumple con las reglas QSI-059: ' . implode(', ', $val['errores']);
            $cove->update([
                'status' => 'error',
                'error_mensaje' => $msg
            ]);
            return ['success' => false, 'message' => $msg];
        }

        try {
            // 2. Generar cadena original y firmado
            $cadenaOriginal = $this->generarCadenaOriginal($data);
            
            // Rutas temporales de certificados
            $certPath = tempnam(sys_get_temp_dir(), 'cert_');
            $keyPath = tempnam(sys_get_temp_dir(), 'key_');
            
            file_put_contents($certPath, base64_decode($applicant->vucem_cert_file));
            file_put_contents($keyPath, base64_decode($applicant->vucem_key_file));
            $password = $applicant->vucem_password;

            $firma = $this->efirmaService->generarFirmaElectronicaConArchivos(
                $cadenaOriginal,
                $this->rfc,
                $certPath,
                $keyPath,
                $password
            );

            // Eliminar archivos temporales
            @unlink($certPath);
            @unlink($keyPath);

            // 3. Generar XML completo
            $xml = $this->construirXmlRecibirCove($data, $firma);

            // Endpoint de VUCEM
            $endpoint = config('vucem.recibir_cove.endpoint');
            
            Log::info('[COVE_SOAP] Iniciando transmisión a VUCEM', [
                'cove_id' => $cove->id,
                'rfc' => $this->rfc,
                'endpoint' => $endpoint
            ]);

            // Guardar XML de solicitud
            $cove->update(['xml_solicitud' => $xml]);

            if (!config('vucem.cove_recibir_enabled', false)) {
                // Modo simulador / deshabilitado
                $dummyEdoc = 'COVE' . date('Ymd') . rand(1000, 9999);
                $cove->update([
                    'status' => 'procesado',
                    'edocument' => $dummyEdoc,
                    'xml_respuesta' => '<simulado>Transmisión simulada exitosa</simulado>'
                ]);
                return [
                    'success' => true,
                    'edocument' => $dummyEdoc,
                    'message' => 'Transmisión simulada con éxito (cove_recibir_enabled está deshabilitado).'
                ];
            }

            // 4. Envío cURL
            $ch = curl_init($endpoint);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => trim($xml),
                CURLOPT_TIMEOUT => config('vucem.soap_timeout', 120),
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_SSL_CIPHER_LIST => 'DEFAULT@SECLEVEL=0',
                CURLOPT_HTTPHEADER => [
                    'Content-Type: text/xml; charset=utf-8',
                    'SOAPAction: "http://www.ventanillaunica.gob.mx/RecibirCove"',
                    'Content-Length: ' . strlen(trim($xml))
                ]
            ]);

            $responseBody = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                throw new Exception("Error cURL: " . $curlError);
            }

            // Guardar respuesta
            $cove->update(['xml_respuesta' => $responseBody]);

            // 5. Parsear respuesta XML
            return $this->parseResponseXml($responseBody, $cove);

        } catch (Exception $e) {
            Log::error('[COVE_SOAP] Error crítico de transmisión', [
                'cove_id' => $cove->id,
                'message' => $e->getMessage()
            ]);

            $cove->update([
                'status' => 'error',
                'error_mensaje' => $e->getMessage()
            ]);

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function parseResponseXml(string $responseXml, Cove $cove): array
    {
        try {
            // Cargar respuesta en XML
            $cleanXml = str_ireplace(['soapenv:', 'ser:', 'oxml:', 'wsse:', 'S:', 'env:', 'wsu:'], '', $responseXml);
            $xmlElement = simplexml_load_string($cleanXml);
            if ($xmlElement === false) {
                throw new Exception("No se pudo parsear el XML de respuesta de VUCEM.");
            }

            // Buscar errores en el body de respuesta
            $body = $xmlElement->Body ?? null;
            if ($body) {
                // Caso SOAP Fault (Error del servidor VUCEM)
                $fault = $body->Fault ?? null;
                if ($fault) {
                    $faultString = (string)($fault->faultstring ?? 'Error SOAP interno de VUCEM.');
                    $cove->update([
                        'status' => 'error',
                        'error_mensaje' => 'SOAP Fault: ' . $faultString
                    ]);
                    return ['success' => false, 'message' => 'SOAP Fault: ' . $faultString];
                }

                // Caso 1: VUCEM estándar para recibir comprobante
                $respRecibir = $body->solicitarRecibirCoveServicioResponse ?? null;
                if ($respRecibir) {
                    $msgInfo = (string)($respRecibir->mensajeInformativo ?? '');

                    // Intentar capturar numeroOperacion del XML crudo (VUCEM lo incluye en algunas versiones)
                    $numeroOperacion = null;
                    if (preg_match('/<[:\w]*numero(?:De)?Operacion[^>]*>(.*?)<\/[:\w]*numero(?:De)?Operacion>/i', $responseXml, $opMatch)) {
                        $numeroOperacion = trim($opMatch[1]);
                    }

                    // Si contiene errores en la respuesta
                    if (str_contains(strtolower($msgInfo), 'error') || str_contains(strtolower($msgInfo), 'rechaz')) {
                        $cove->update([
                            'status'       => 'error',
                            'error_mensaje' => $msgInfo
                        ]);
                        return ['success' => false, 'message' => $msgInfo];
                    }

                    // Éxito: VUCEM recibió la solicitud. El e-document se obtendrá por polling.
                    $updateData = [
                        'status'        => 'enviado',
                        'error_mensaje' => null,
                        'edocument'     => 'PENDIENTE',
                    ];
                    if ($numeroOperacion) {
                        $updateData['numero_operacion'] = $numeroOperacion;
                        Log::info('[COVE] numeroOperacion capturado de respuesta VUCEM', [
                            'cove_id'          => $cove->id,
                            'numero_operacion' => $numeroOperacion,
                        ]);
                    }
                    $cove->update($updateData);
                    return ['success' => true, 'message' => $msgInfo, 'numero_operacion' => $numeroOperacion];
                }

                // Caso 2: Estructura alternativa
                $recibirCoveResp = $body->RecibirCoveResponse ?? null;
                if ($recibirCoveResp && isset($recibirCoveResp->response)) {
                    $resp = $recibirCoveResp->response;
                    $contieneError = (string)($resp->contieneError ?? 'false');

                    if ($contieneError === 'true' || $contieneError === '1') {
                        $msg = (string)($resp->mensaje ?? 'Error en respuesta VUCEM.');
                        if (isset($resp->errores) && isset($resp->errores->error)) {
                            $msg .= ' Detalle: ' . (string)$resp->errores->error;
                        }
                        
                        $cove->update([
                            'status' => 'error',
                            'error_mensaje' => $msg
                        ]);
                        return ['success' => false, 'message' => $msg];
                    }

                    // Éxito: Extraer eDocument
                    $eDoc = (string)($resp->resultadoBusqueda->cove->eDocument ?? '');
                    if (!empty($eDoc)) {
                        $cove->update([
                            'status' => 'procesado',
                            'edocument' => $eDoc,
                            'error_mensaje' => null
                        ]);
                        return ['success' => true, 'edocument' => $eDoc];
                    }
                }
            }

            throw new Exception("Respuesta XML de VUCEM vacía o con estructura inesperada.");

        } catch (Exception $e) {
            $cove->update([
                'status' => 'error',
                'error_mensaje' => $e->getMessage()
            ]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
