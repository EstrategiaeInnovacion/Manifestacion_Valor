<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuración VUCEM (Ventanilla Única de Comercio Exterior)
    |--------------------------------------------------------------------------
    |
    | Configuración para los servicios web de VUCEM.
    | Configuración para ambiente de PRUEBAS.
    |
    | IMPORTANTE: Las credenciales (RFC y clave webservice) se obtienen 
    | automáticamente del perfil del usuario autenticado, NO del .env
    |
    */

    /*
    |--------------------------------------------------------------------------
    | SEGURIDAD COVE - Control de Generación
    |--------------------------------------------------------------------------
    |
    | ⚠️  PELIGRO: RecibirCove genera trámites REALES ante SAT
    | ✅  SEGURO:  ConsultarRespuestaCove solo consulta
    |
    | Deshabilita RecibirCove en producción para evitar generar 
    | trámites no deseados.
    |
    */
    'cove_recibir_enabled' => env('COVE_RECIBIR_ENABLED', false),

    // RFC para el sello digital (RFC de prueba oficial SAT)
    'rfc' => env('VUCEM_RFC', 'GWT921026L97'),

    /*
    |--------------------------------------------------------------------------
    | Configuración del Servicio ConsultarEdocument  
    |--------------------------------------------------------------------------
    */
    'edocument' => [
        'endpoint' => env('VUCEM_EDOCUMENT_ENDPOINT', 'https://www.ventanillaunica.gob.mx/ventanilla/ConsultarEdocumentService'),
        'wsdl_url' => env('VUCEM_EDOCUMENT_WSDL', 'https://www.ventanillaunica.gob.mx/ventanilla/ConsultarEdocumentService?wsdl'),
        'wsdl_path' => base_path('wsdl/vucem/COVE/edocument/ConsultarEdocument.wsdl'), // Fallback local
        // soap_action se obtiene automáticamente del WSDL
        'soap_action' => env('VUCEM_EDOCUMENT_ACTION', null), // null = auto-detect from WSDL
        
        // Configuración SOAP para ambiente de pruebas
        'soap_version' => SOAP_1_1,
        'connection_timeout' => 30,
        'trace' => true,
        'exceptions' => true,
        'cache_wsdl' => WSDL_CACHE_NONE,
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración del Servicio ConsultarRespuestaCove  
    |--------------------------------------------------------------------------
    | Servicio para consultar datos estructurados de COVE (no el PDF)
    | Puerto 8110 es específico para este servicio según WSDL oficial
    */
    'consultar_respuesta_cove' => [
        'endpoint' => env('VUCEM_CONSULTAR_COVE_ENDPOINT', 'https://www.ventanillaunica.gob.mx:8110/ventanilla/ConsultarRespuestaCoveService'),
        'soap_action' => env('VUCEM_CONSULTAR_COVE_ACTION', 'http://www.ventanillaunica.gob.mx/ConsultarRespuestaCove'),
        'wsdl_path' => base_path('wsdl/vucem/COVE/ConsultarRespuestaCoveService.wsdl'),
        
        // Configuración SOAP
        'soap_version' => SOAP_1_1,
        'connection_timeout' => 30,
        'trace' => true,
        'exceptions' => true,
        'cache_wsdl' => WSDL_CACHE_NONE,
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración del Servicio ConsultaAcuses (para descargar PDFs)
    |--------------------------------------------------------------------------
    */
    'consulta_acuses' => [
        'endpoint' => env('VUCEM_CONSULTA_ACUSES_ENDPOINT', 'https://www.ventanillaunica.gob.mx/ventanilla-acuses-HA/ConsultaAcusesServiceWS'),
        'soap_action' => env('VUCEM_CONSULTA_ACUSES_ACTION', 'http://www.ventanillaunica.gob.mx/consulta/acuses/ConsultaAcuses'),
        'wsdl_path' => base_path('wsdl/vucem/ACUSES/ConsultaAcusesServiceWS.wsdl'),
        
        // Configuración SOAP
        'soap_version' => SOAP_1_1,
        'connection_timeout' => 30,
        'trace' => true,
        'exceptions' => true,
        'cache_wsdl' => WSDL_CACHE_NONE,
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Seguridad y Logging
    |--------------------------------------------------------------------------
    */
    'security' => [
        // Namespace para WS-Security
        'ws_security_namespace' => 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd',
        
        // Tipo de password para UsernameToken
        'password_type' => 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText',
    ],

    'logging' => [
        // Log detallado de requests/responses SOAP (habilitado para pruebas)
        'log_soap_requests' => env('VUCEM_LOG_SOAP', true),
        
        // Log de errores siempre habilitado
        'log_errors' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | URLs de Servicios VUCEM por ambiente
    |--------------------------------------------------------------------------
    */
    'endpoints' => [
        'production' => [
            'consultar_cove' => 'https://www.ventanillaunica.gob.mx/ConsultarRespuestaCoveService',
        ],
        'testing' => [
            'consultar_cove' => 'https://www2.ventanillaunica.gob.mx/ventanilla/ConsultarRespuestaCoveService',
        ],
        'development' => [
            'consultar_cove' => 'http://localhost:8080/mock/ConsultarRespuestaCoveService',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración e.firma (Firma Electrónica)
    |--------------------------------------------------------------------------
    */
    'efirma' => [
        // Ruta relativa donde están los archivos de e.firma
        'path' => env('E_FIRMA_PATH', 'pruebaEfirma'),
        
        // Contraseña desde archivo (más seguro que variable de entorno)
        'password_file' => 'CONTRASEÑA.txt',
        
        // Nombres de archivos específicos para NET070608EM9
        'cert_file' => '00001000000716248795.cer',
        'key_file' => 'Claveprivada_FIEL_NET070608EM9_20250604_163343.key',
        
        // Configuración de firma
        'signature_algorithm' => OPENSSL_ALGO_SHA256,
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración Manifestación de Valor
    |--------------------------------------------------------------------------
    */
    'send_manifestation_enabled' => env('VUCEM_SEND_MANIFESTATION_ENABLED', false),
    
    'mv_endpoint' => env('VUCEM_MV_ENDPOINT', 'https://privados.ventanillaunica.gob.mx:8104/IngresoManifestacionImpl/IngresoManifestacionService'),
    'mv_wsdl' => env('VUCEM_MV_WSDL', 'https://privados.ventanillaunica.gob.mx/IngresoManifestacionImpl/IngresoManifestacionService?wsdl'),
    
    'mv_consulta_endpoint' => env('VUCEM_MV_CONSULTA_ENDPOINT', 'https://privados.ventanillaunica.gob.mx/ConsultaManifestacionImpl/ConsultaManifestacionService'),
    'mv_consulta_wsdl' => env('VUCEM_MV_CONSULTA_WSDL', 'https://privados.ventanillaunica.gob.mx/ConsultaManifestacionImpl/ConsultaManifestacionService?wsdl'),
    
    'soap_timeout' => env('VUCEM_SOAP_TIMEOUT', 30),
];
