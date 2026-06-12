<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\ApplicantController;
use App\Http\Controllers\MveController;
use App\Http\Controllers\DocumentUploadController;
use App\Http\Controllers\EDocumentConsultaController;
use App\Http\Controllers\DigitalizacionController;
use App\Http\Controllers\SupportController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\LicenseController;
use App\Http\Controllers\AnnouncementController;
use App\Models\MvClientApplicant;
use App\Models\MvDatosManifestacion;
use App\Models\User;
use App\Models\MvInformacionCove;
use App\Models\MvDocumentos;
use App\Models\MvAcuse;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;

Route::get('/', function () {
    return view('welcome');
});

// Página pública de aviso de privacidad y condiciones de uso
Route::get('/privacidad', function() {
    return view('legal.privacidad', [
        'avisoCompleto'  => \App\Models\AppSetting::get('aviso_privacidad_completo'),
        'condicionesUso' => \App\Models\AppSetting::get('condiciones_uso'),
    ]);
})->name('legal.privacidad');

Route::get('/dashboard', function () {
    $user = auth()->user();

    if ($user->role === 'SuperAdmin') {
        // SuperAdmin solo ve datos de su propia empresa (nunca de otras empresas)
        if (empty($user->company)) {
            $applicantIds = MvClientApplicant::where('created_by_user_id', $user->id)->pluck('id');
        } else {
            $cIds    = User::where('company', $user->company)->pluck('id');
            $cEmails = User::where('company', $user->company)->pluck('email');
            $applicantIds = MvClientApplicant::where(function ($q) use ($cIds, $cEmails) {
                $q->whereIn('created_by_user_id', $cIds)
                  ->orWhere(function ($sub) use ($cEmails) {
                      $sub->whereNull('created_by_user_id')->whereIn('user_email', $cEmails);
                  });
            })->pluck('id');
        }
    } elseif ($user->role === 'Admin') {
        $applicantIds = MvClientApplicant::where(function($q) use ($user) {
            $q->where('created_by_user_id', $user->id)
              ->orWhere(function($sub) use ($user) {
                  $sub->whereNull('created_by_user_id')->where('user_email', $user->email);
              });
        })->pluck('id');
    } else {
        $applicantIds = MvClientApplicant::whereHas('assignedUsers', fn($q) => $q->where('user_id', $user->id))
            ->orWhere('user_email', $user->email)
            ->pluck('id');
    }

    $mvePendientesCount = MvDatosManifestacion::whereIn('applicant_id', $applicantIds)
        ->whereIn('status', ['borrador', 'guardado', 'rechazado'])
        ->when($user->role === 'Usuario', fn($q) => $q->where('created_by_user_id', $user->id))
        ->count();

    $mveCompletadasCount = MvAcuse::whereIn('applicant_id', $applicantIds)
        ->when($user->role === 'Usuario', function ($q) use ($user) {
            $q->where(function ($inner) use ($user) {
                $inner->where('created_by_user_id', $user->id)
                      ->orWhere(function ($sub) use ($user) {
                          $sub->whereNull('created_by_user_id')
                              ->whereHas('datosManifestacion', fn($dm) => $dm->where('created_by_user_id', $user->id));
                      });
            });
        })
        ->count();

    return view('dashboard', compact('mvePendientesCount', 'mveCompletadasCount'));
})->middleware(['auth', 'verified', 'license'])->name('dashboard');

Route::middleware(['auth', 'license'])->group(function () {
    // Conteo de pendientes para polling en tiempo real
    Route::get('/mve/pending-count', function () {
        $user = auth()->user();
        if ($user->role === 'SuperAdmin') {
            // SuperAdmin solo ve datos de su propia empresa
            if (empty($user->company)) {
                $applicantIds = MvClientApplicant::where('created_by_user_id', $user->id)->pluck('id');
            } else {
                $cIds    = User::where('company', $user->company)->pluck('id');
                $cEmails = User::where('company', $user->company)->pluck('email');
                $applicantIds = MvClientApplicant::where(function ($q) use ($cIds, $cEmails) {
                    $q->whereIn('created_by_user_id', $cIds)
                      ->orWhere(function ($sub) use ($cEmails) {
                          $sub->whereNull('created_by_user_id')->whereIn('user_email', $cEmails);
                      });
                })->pluck('id');
            }
        } elseif ($user->role === 'Admin') {
            $applicantIds = MvClientApplicant::where(function($q) use ($user) {
                $q->where('created_by_user_id', $user->id)
                  ->orWhere(function($sub) use ($user) {
                      $sub->whereNull('created_by_user_id')->where('user_email', $user->email);
                  });
            })->pluck('id');
        } else {
            $applicantIds = MvClientApplicant::whereHas('assignedUsers', fn($q) => $q->where('user_id', $user->id))
                ->orWhere('user_email', $user->email)
                ->pluck('id');
        }
        $count = MvDatosManifestacion::whereIn('applicant_id', $applicantIds)
            ->whereIn('status', ['borrador', 'guardado', 'rechazado'])
            ->when($user->role === 'Usuario', fn($q) => $q->where('created_by_user_id', $user->id))
            ->count();
        return response()->json(['count' => $count]);
    })->name('mve.pending-count');

    // Soporte técnico
    Route::post('/support/send', [SupportController::class, 'send'])->name('support.send');

    // Tickets de soporte
    Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
    Route::post('/tickets/{ticket}/respond', [TicketController::class, 'respond'])->name('tickets.respond');
    Route::patch('/tickets/{ticket}/status', [TicketController::class, 'updateStatus'])->name('tickets.status');
    Route::get('/tickets/attachment/{attachment}', [TicketController::class, 'downloadAttachment'])->name('tickets.attachment');
    Route::post('/tickets/{ticket}/cancel', [TicketController::class, 'cancel'])->name('tickets.cancel');

    Route::get('/profile', [ProfileController::class , 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class , 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class , 'destroy'])->name('profile.destroy');
    Route::patch('/profile/preferences', [ProfileController::class , 'updatePreferences'])->name('profile.preferences');

    // Rutas de gestión de usuarios (solo Admin y SuperAdmin)
    Route::middleware('role:SuperAdmin,Admin')->group(function () {
            Route::get('/users', [UserManagementController::class , 'index'])->name('users.index');
            Route::get('/users/add', [UserManagementController::class , 'create'])->name('users.create');
            Route::post('/users', [UserManagementController::class , 'store'])->name('users.store');
            Route::get('/users/{user}/edit', [UserManagementController::class , 'edit'])->name('users.edit');
            Route::put('/users/{user}', [UserManagementController::class , 'update'])->name('users.update');
            Route::delete('/users/{user}', [UserManagementController::class , 'destroy'])->name('users.destroy');
        }
        );

        // Rutas de gestión de licencias (solo SuperAdmin)
        Route::middleware('role:SuperAdmin')->prefix('admin/licenses')->name('admin.licenses.')->group(function () {
            Route::get('/', [LicenseController::class , 'index'])->name('index');
            Route::post('/', [LicenseController::class , 'store'])->name('store');
            Route::post('/{license}/renew', [LicenseController::class , 'renew'])->name('renew');
            Route::patch('/{license}/revoke', [LicenseController::class , 'revoke'])->name('revoke');
            Route::patch('/limits/{user}', [LicenseController::class , 'updateLimits'])->name('limits');
        }
        );

        // Rutas de gestión de solicitantes
        Route::resource('applicants', ApplicantController::class);

        // Ajustes del sistema (SuperAdmin)
        Route::middleware('role:SuperAdmin')->group(function () {
            Route::get('/admin/settings', [App\Http\Controllers\AdminSettingsController::class, 'index'])->name('admin.settings');
            Route::patch('/admin/settings', [App\Http\Controllers\AdminSettingsController::class, 'update'])->name('admin.settings.update');
            Route::get('/admin/estadisticas', [App\Http\Controllers\AdminStatsController::class, 'index'])->name('admin.estadisticas');
            Route::post('/admin/estadisticas/forzar-operando', [App\Http\Controllers\AdminStatsController::class, 'forzarOperando'])->name('admin.estadisticas.forzar-operando');
            Route::post('/admin/estadisticas/limpiar-override', [App\Http\Controllers\AdminStatsController::class, 'limpiarOverride'])->name('admin.estadisticas.limpiar-override');

            // Debug digitalizador VUCEM (solo SuperAdmin, requiere PDF_DEBUG_ENABLED=true en .env)
            if (config('app.pdf_debug_enabled')) {
                Route::get('/admin/pdf-debug', [App\Http\Controllers\Admin\PdfDebugController::class, 'index'])->name('admin.pdf-debug');
                Route::post('/admin/pdf-debug/test', [App\Http\Controllers\Admin\PdfDebugController::class, 'test'])->name('admin.pdf-debug.test');
            }

            // Manuales de uso — subir y eliminar (solo SuperAdmin)
            Route::post('/manuals', [App\Http\Controllers\UserManualController::class, 'store'])->name('manuals.store');
            Route::delete('/manuals/{manual}', [App\Http\Controllers\UserManualController::class, 'destroy'])->name('manuals.destroy');

            // Avisos generales — crear y eliminar (solo SuperAdmin)
            Route::post('/announcements', [AnnouncementController::class, 'store'])->name('announcements.store');
            Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'destroy'])->name('announcements.destroy');
        });

        // Manuales de uso — ver y abrir (todos los usuarios autenticados)
        Route::get('/manuals', [App\Http\Controllers\UserManualController::class, 'index'])->name('manuals.index');
        Route::get('/manuals/{manual}', [App\Http\Controllers\UserManualController::class, 'show'])->name('manuals.show');

        // Avisos generales — marcar como leído (todos los usuarios autenticados)
        Route::post('/announcements/{announcement}/read', [AnnouncementController::class, 'markRead'])->name('announcements.read');

        // Rutas de Manifestación de Valor
        Route::get('/mve/select-applicant', [MveController::class , 'selectApplicant'])->name('mve.select-applicant');
        Route::get('/mve/manual/{applicant}', [MveController::class , 'createManual'])->name('mve.create-manual');
        Route::get('/mve/archivo-m/{applicant}', [MveController::class , 'createWithFile'])->name('mve.upload-file');
        Route::post('/mve/archivo-m/{applicant}', [MveController::class , 'createWithFile'])->name('mve.process-file');

        // Rutas para borradores MVE por sección
        Route::get('/mve/pendientes', [MveController::class , 'pendientes'])->name('mve.pendientes');
        Route::get('/mve/completadas', [MveController::class , 'completadas'])->name('mve.completadas');
        Route::delete('/mve/borrar-borrador', [MveController::class , 'borrarBorrador'])->name('mve.borrar-borrador');

        // Rutas para guardar secciones individuales
        Route::post('/mve/save-datos-manifestacion/{applicant}', [MveController::class , 'saveDatosManifestacion'])->name('mve.save-datos-manifestacion');
        Route::post('/mve/save-informacion-cove/{applicant}', [MveController::class , 'saveInformacionCove'])->name('mve.save-informacion-cove');
        Route::post('/mve/save-valor-aduana/{applicant}', [MveController::class , 'saveValorAduana'])->name('mve.save-valor-aduana');
        Route::post('/mve/save-documentos/{applicant}', [MveController::class , 'saveDocumentos'])->name('mve.save-documentos');
        Route::post('/mve/digitalizar-documento/{applicant}', [MveController::class , 'digitalizarDocumento'])->name('mve.digitalizar-documento');
        Route::post('/mve/consultar-operacion/{applicant}', [MveController::class , 'consultarOperacion'])->name('mve.consultar-operacion');
        Route::post('/mve/descartar-operacion/{applicant}', [MveController::class , 'descartarOperacion'])->name('mve.descartar-operacion');
        Route::post('/mve/validar-pdf', [MveController::class , 'validarPdf'])->name('mve.validar-pdf');
        Route::get('/mve/verificar-red', [MveController::class , 'verificarRed'])->name('mve.verificar-red');
        Route::post('/mve/parse-pedimento-edocuments', [MveController::class , 'parsePedimentoEdocuments'])->name('mve.parse-pedimento-edocuments');
        Route::post('/mve/validate-edocument', [MveController::class , 'validateEdocument'])->name('mve.validate-edocument');
        Route::get('/mve/cove/info/{applicant}', [MveController::class , 'buscarCoveInfo'])->name('mve.cove.info');

        // ==========================================================
        // MÓDULO DE CONSULTA DE COVE
        // ==========================================================
    
        Route::get('/cove/consulta', [EDocumentConsultaController::class , 'index'])->name('cove.consulta.index');

        // Y el POST apunta a 'consultar' (no a consultarCove)
        Route::post('/cove/consulta', [EDocumentConsultaController::class , 'consultar'])->name('cove.consulta');

        // API: verificar credenciales VUCEM de un solicitante
        Route::get('/cove/credenciales/{applicant}', [EDocumentConsultaController::class , 'checkCredentials'])->name('cove.credenciales.check');

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
        Route::get('/mve/consultar/{acuse}/xml', [MveController::class , 'downloadConsultaXml'])->name('mve.consultar.xml');
        Route::get('/mve/consultar/{acuse}/declaracion-xml', [MveController::class , 'downloadDeclaracionXml'])->name('mve.consultar.declaracion.xml');

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
            ->name('digitalizacion.store');

        // 3. Consultar folio eDocument por número de operación pendiente
        Route::post('/digitalizacion/{id}/consultar-operacion', [DigitalizacionController::class , 'consultarOperacion'])
            ->name('digitalizacion.consultar-operacion');

        // ==========================================================
        // MÓDULO DE PREGUNTAS FRECUENTES (FAQs)
        // ==========================================================

        // Lista y adjuntos — visibles para todos los usuarios autenticados con licencia
        Route::get('/faqs', [App\Http\Controllers\FaqController::class, 'index'])->name('faqs.index');
        Route::get('/faqs/attachment/{attachment}', [App\Http\Controllers\FaqController::class, 'attachment'])->name('faqs.attachment');

        // Gestión (solo SuperAdmin) — declaradas ANTES de las rutas dinámicas {faq}
        Route::middleware('role:SuperAdmin')->group(function () {
            Route::get('/faqs/create', [App\Http\Controllers\FaqController::class, 'create'])->name('faqs.create');
            Route::post('/faqs', [App\Http\Controllers\FaqController::class, 'store'])->name('faqs.store');
            Route::get('/faqs/{faq}/edit', [App\Http\Controllers\FaqController::class, 'edit'])->name('faqs.edit');
            Route::put('/faqs/{faq}', [App\Http\Controllers\FaqController::class, 'update'])->name('faqs.update');
            Route::delete('/faqs/{faq}', [App\Http\Controllers\FaqController::class, 'destroy'])->name('faqs.destroy');
            Route::delete('/faqs/attachment/{attachment}', [App\Http\Controllers\FaqController::class, 'destroyAttachment'])->name('faqs.attachment.destroy');
        });

        // Vista individual — accesible para todos los usuarios autenticados con licencia
        Route::get('/faqs/{faq}', [App\Http\Controllers\FaqController::class, 'show'])->name('faqs.show');
    });

require __DIR__ . '/auth.php';
