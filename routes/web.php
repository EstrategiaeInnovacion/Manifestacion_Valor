<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\ApplicantController;
use App\Http\Controllers\MveController;
use App\Http\Controllers\DocumentUploadController;
use App\Http\Controllers\EDocumentConsultaController;
use App\Http\Controllers\Api\ExchangeRateController;
use App\Http\Controllers\DigitalizacionController;
use App\Http\Controllers\SupportController;
use App\Models\MvClientApplicant;
use App\Models\MvDatosManifestacion;
use App\Models\MvInformacionCove;
use App\Models\MvDocumentos;
use App\Models\MvAcuse;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    // Contar cuántos solicitantes tienen al menos una sección guardada en borrador
    $applicantIds = MvClientApplicant::where('user_email', auth()->user()->email)->pluck('id');

    $pendientesIds = collect();
    $pendientesIds = $pendientesIds->merge(MvDatosManifestacion::whereIn('applicant_id', $applicantIds)->where('status', 'borrador')->pluck('applicant_id'));
    $pendientesIds = $pendientesIds->merge(MvInformacionCove::whereIn('applicant_id', $applicantIds)->where('status', 'borrador')->pluck('applicant_id'));
    $pendientesIds = $pendientesIds->merge(MvDocumentos::whereIn('applicant_id', $applicantIds)->where('status', 'borrador')->pluck('applicant_id'));

    $mvePendientesCount = $pendientesIds->unique()->count();

    // Contar manifestaciones completadas (enviadas a VUCEM)
    $mveCompletadasCount = MvAcuse::whereIn('applicant_id', $applicantIds)->count();

    return view('dashboard', compact('mvePendientesCount', 'mveCompletadasCount'));
})->middleware(['auth', 'verified'])->name('dashboard');

// API Routes for Exchange Rates (con rate limiting)
Route::prefix('api')->middleware('throttle:60,1')->group(function () {
    Route::get('/exchange-rate', [ExchangeRateController::class , 'getRate'])->name('api.exchange-rate');
    Route::get('/exchange-rate/currencies', [ExchangeRateController::class , 'getSupportedCurrencies'])->name('api.exchange-rate.currencies');
    Route::get('/exchange-rate/test', [ExchangeRateController::class , 'testConnection'])->name('api.exchange-rate.test');
    Route::post('/exchange-rate/clear-cache', [ExchangeRateController::class , 'clearCache'])->name('api.exchange-rate.clear-cache');
});

Route::middleware('auth')->group(function () {
    // Soporte técnico
    Route::post('/support/send', [SupportController::class, 'send'])->name('support.send');

    Route::get('/profile', [ProfileController::class , 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class , 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class , 'destroy'])->name('profile.destroy');

    // Rutas de gestión de usuarios (solo Admin y SuperAdmin)
    Route::middleware('role:SuperAdmin,Admin')->group(function () {
            Route::get('/users', [UserManagementController::class , 'index'])->name('users.index');
            Route::get('/users/add', [UserManagementController::class , 'create'])->name('users.create');
            Route::post('/users', [UserManagementController::class , 'store'])->name('users.store');
            Route::delete('/users/{user}', [UserManagementController::class , 'destroy'])->name('users.destroy');
        }
        );

        // Rutas de gestión de solicitantes
        Route::resource('applicants', ApplicantController::class);

        // Rutas de Manifestación de Valor
        Route::get('/mve/select-applicant', [MveController::class , 'selectApplicant'])->name('mve.select-applicant');
        Route::get('/mve/manual/{applicant}', [MveController::class , 'createManual'])->name('mve.create-manual');
        Route::get('/mve/archivo-m/{applicant}', [MveController::class , 'createWithFile'])->name('mve.upload-file');
        Route::post('/mve/archivo-m/{applicant}', [MveController::class , 'createWithFile'])->name('mve.process-file');

        // Rutas para RFC de consulta
        Route::post('/mve/rfc-consulta/search', [MveController::class , 'searchRfcConsulta'])->name('mve.rfc-consulta.search');
        Route::post('/mve/rfc-consulta/store', [MveController::class , 'storeRfcConsulta'])->name('mve.rfc-consulta.store');
        Route::delete('/mve/rfc-consulta/delete', [MveController::class , 'deleteRfcConsulta'])->name('mve.rfc-consulta.delete');

        // Rutas para borradores MVE por sección
        Route::get('/mve/pendientes', [MveController::class , 'pendientes'])->name('mve.pendientes');
        Route::get('/mve/completadas', [MveController::class , 'completadas'])->name('mve.completadas');
        Route::delete('/mve/borrar-borrador', [MveController::class , 'borrarBorrador'])->name('mve.borrar-borrador');

        // Rutas para guardar secciones individuales
        Route::post('/mve/save-datos-manifestacion/{applicant}', [MveController::class , 'saveDatosManifestacion'])->name('mve.save-datos-manifestacion');
        Route::post('/mve/save-informacion-cove/{applicant}', [MveController::class , 'saveInformacionCove'])->name('mve.save-informacion-cove');
        Route::post('/mve/save-valor-aduana/{applicant}', [MveController::class , 'saveValorAduana'])->name('mve.save-valor-aduana');
        Route::post('/mve/save-documentos/{applicant}', [MveController::class , 'saveDocumentos'])->name('mve.save-documentos');
        Route::post('/mve/parse-pedimento-edocuments', [MveController::class , 'parsePedimentoEdocuments'])->name('mve.parse-pedimento-edocuments');
        Route::post('/mve/validate-edocument', [MveController::class , 'validateEdocument'])->name('mve.validate-edocument');

        // ==========================================================
        // MÓDULO DE CONSULTA DE COVE
        // ==========================================================
    
        Route::get('/cove/consulta', [EDocumentConsultaController::class , 'index'])->name('cove.consulta.index');

        // Y el POST apunta a 'consultar' (no a consultarCove)
        Route::post('/cove/consulta', [EDocumentConsultaController::class , 'consultar'])->name('cove.consulta');

        // Ruta de descarga
        Route::get('/cove/descargar/{token}', [EDocumentConsultaController::class , 'descargar'])->name('cove.descargar');

        // Rutas para consultar y descargar PDF Acuse del COVE
        Route::post('/cove/acuse-pdf', [EDocumentConsultaController::class , 'consultarAcusePdf'])->name('cove.acuse.consultar');
        Route::get('/cove/acuse-pdf/{token}', [EDocumentConsultaController::class , 'descargarAcusePdf'])->name('cove.acuse.descargar');

        // Verificación de completitud y guardado final
        Route::get('/mve/check-completion/{applicant}', [MveController::class , 'checkCompletion'])->name('mve.check-completion');
        Route::get('/mve/preview-data/{applicant}', [MveController::class , 'previewData'])->name('mve.preview-data');
        Route::post('/mve/save-final-manifestacion/{applicant}', [MveController::class , 'saveFinalManifestacion'])->name('mve.save-final-manifestacion');

        // Rutas para visualizar y descargar documentos desde vista previa MVE
        Route::get('/mve/view-document/{document}', [MveController::class , 'viewDocument'])->name('mve.view-document');
        Route::get('/mve/download-document/{document}', [MveController::class , 'downloadDocument'])->name('mve.download-document');

        // Rutas para firma y envío a VUCEM mediante AJAX (nuevo flujo)
        Route::post('/mve/firmar-enviar/{applicant}', [MveController::class , 'firmarYEnviarAjax'])->name('mve.firmar-enviar');
        Route::delete('/mve/descartar/{applicant}', [MveController::class , 'descartarManifestacion'])->name('mve.descartar');
        Route::post('/mve/limpiar-huerfanos', [MveController::class , 'limpiarDatosHuerfanos'])->name('mve.limpiar-huerfanos');

        // Rutas legacy para firma (mantener compatibilidad)
        Route::get('/mve/firmar/{manifestacion}', [MveController::class , 'showSign'])->name('mve.firmar');
        Route::post('/mve/firmar/{manifestacion}', [MveController::class , 'processSign'])->name('mve.firmar.procesar');
        Route::get('/mve/acuse/{manifestacion}', [MveController::class , 'showAcuse'])->name('mve.acuse');
        Route::get('/mve/acuse/{manifestacion}/pdf', [MveController::class , 'downloadAcusePdf'])->name('mve.acuse.pdf');
        Route::get('/mve/acuse/{manifestacion}/xml', [MveController::class , 'downloadAcuseXml'])->name('mve.acuse.xml');

        // Ruta para consultar manifestación en VUCEM (obtener número de MV y acuse sellado)
        Route::post('/mve/consultar/{acuse}', [MveController::class , 'consultarManifestacion'])->name('mve.consultar');

        // Rutas para subida de documentos con validación/conversión VUCEM
        Route::post('/documents/upload', [DocumentUploadController::class , 'uploadDocument'])->name('documents.upload');
        Route::get('/documents/applicant/{applicant}', [DocumentUploadController::class , 'getDocuments'])->name('documents.list');
        Route::delete('/documents/{document}', [DocumentUploadController::class , 'deleteDocument'])->name('documents.delete');
        Route::get('/documents/download/{document}', [DocumentUploadController::class , 'downloadDocument'])->name('documents.download');
        Route::get('/documents/view/{document}', [DocumentUploadController::class , 'viewDocument'])->name('documents.view');
        Route::post('/documents/validate-preview', [DocumentUploadController::class , 'validatePdfPreview'])->name('documents.validate-preview');

        Route::get('/digitalizacion', [DigitalizacionController::class , 'create'])
            ->name('digitalizacion.create');

        // 2. Procesar el archivo, firmarlo y enviarlo a VUCEM
        Route::post('/digitalizacion', [DigitalizacionController::class , 'store'])
            ->name('digitalizacion.store');    });

require __DIR__ . '/auth.php';
