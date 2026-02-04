<?php

use Illuminate\Support\Facades\Route;

Route::get('/test-soap-laravel', function() {
    $soapLoaded = extension_loaded('soap');
    $soapClientExists = class_exists('SoapClient');
    $loadedExts = get_loaded_extensions();
    
    return response()->json([
        'extension_loaded' => $soapLoaded,
        'SoapClient_exists' => $soapClientExists,
        'soap_in_extensions' => in_array('soap', $loadedExts),
        'php_ini' => php_ini_loaded_file(),
        'all_extensions' => $loadedExts,
        'server_api' => php_sapi_name(),
    ]);
});
