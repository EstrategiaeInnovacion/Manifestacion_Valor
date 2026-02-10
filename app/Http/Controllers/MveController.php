<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MvClientApplicant;
use App\Models\MveRfcConsulta;
use App\Models\MvDatosManifestacion;
use App\Models\MvInformacionCove;
use App\Models\MvDocumentos;
use App\Models\EdocumentRegistrado;
use App\Models\MvAcuse;
use App\Constants\VucemCatalogs;
use App\Services\ManifestacionValorService;
use App\Services\MveSignService;
use App\Services\EFirmaService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MveController extends Controller
{
    public function selectApplicant(Request $request)
    {
        $mode = $request->query('mode', 'manual'); // manual o archivo_m
        
        // Obtener solicitantes del usuario actual
        $applicants = MvClientApplicant::where('user_email', auth()->user()->email)
            ->orderBy('business_name')
            ->get();
        
        return view('mve.select-applicant', compact('applicants', 'mode'));
    }
    
    public function createManual($applicantId)
    {
        $applicant = MvClientApplicant::findOrFail($applicantId);
        
        // Verificar que el solicitante pertenece al usuario actual
        if ($applicant->user_email !== auth()->user()->email) {
            abort(403, 'No tienes permiso para acceder a este solicitante.');
        }
        
        // Pasar catálogos VUCEM
        $tiposFigura = VucemCatalogs::$tiposFigura;
        $metodosValoracion = VucemCatalogs::$metodosValoracion;
        $incoterms = VucemCatalogs::$incoterms;
        $aduanas = VucemCatalogs::$aduanas;
        $incrementables = VucemCatalogs::$incrementables;
        $decrementables = VucemCatalogs::$decrementables;
        $formasPago = VucemCatalogs::$formasPago;
        
        // Cargar datos guardados (si existen) - buscar en cualquier status editable
        $statusEditables = ['borrador', 'guardado', 'rechazado'];
        
        $datosManifestacion = MvDatosManifestacion::where('applicant_id', $applicantId)
            ->whereIn('status', $statusEditables)
            ->first();
            
        $informacionCove = MvInformacionCove::where('applicant_id', $applicantId)
            ->whereIn('status', $statusEditables)
            ->first();
            
        $documentos = MvDocumentos::where('applicant_id', $applicantId)
            ->whereNotNull('documentos')
            ->first();

        // Obtener sugerencias de eDocuments registrados
        $edocumentSuggestions = EdocumentRegistrado::orderByDesc('fecha_ultima_consulta')
            ->limit(50)
            ->pluck('folio_edocument');
        
        return view('mve.create-manual', compact(
            'applicant', 
            'tiposFigura', 
            'metodosValoracion',
            'incoterms',
            'aduanas',
            'incrementables',
            'decrementables',
            'formasPago',
            'datosManifestacion',
            'informacionCove',
            'documentos',
            'edocumentSuggestions'
        ));
    }
    
    public function createWithFile(Request $request, $applicantId)
    {
        $applicant = MvClientApplicant::findOrFail($applicantId);
        
        // Verificar que el solicitante pertenece al usuario actual
        if ($applicant->user_email !== auth()->user()->email) {
            abort(403, 'No tienes permiso para acceder a este solicitante.');
        }
        
        if ($request->isMethod('get')) {
            return view('mve.upload-file', compact('applicant'));
        }
        
        // Procesar el archivo M
        $request->validate([
            'archivo_m' => 'required|file|max:2048'
        ]);
        
        try {
            // Leer el contenido del archivo
            $file = $request->file('archivo_m');
            $content = file_get_contents($file->getRealPath());
            
            // Instanciar el servicio
            $mveService = new ManifestacionValorService();
            
            // Parsear el archivo M
            $datosExtraidos = $mveService->parseArchivoMForMV($content);
            
            // VALIDACIÓN CRÍTICA: Verificar que el RFC del archivo coincide con el del solicitante
            $rfcArchivoM = $datosExtraidos['datos_manifestacion']['rfc_importador'] ?? null;
            
            if (empty($rfcArchivoM)) {
                return redirect()->back()
                    ->withErrors(['archivo_m' => 'El archivo no contiene un RFC de importador válido.'])
                    ->withInput();
            }
            
            if (strtoupper($rfcArchivoM) !== strtoupper($applicant->applicant_rfc)) {
                return redirect()->back()
                    ->withErrors([
                        'archivo_m' => 'El RFC del archivo (' . $rfcArchivoM . ') no coincide con el RFC del solicitante (' . $applicant->applicant_rfc . '). El archivo no pertenece a este cliente.'
                    ])
                    ->withInput();
            }
            
            // Pasar catálogos VUCEM
            $tiposFigura = VucemCatalogs::$tiposFigura;
            $metodosValoracion = VucemCatalogs::$metodosValoracion;
            $incoterms = VucemCatalogs::$incoterms;
            $aduanas = VucemCatalogs::$aduanas;
            $incrementables = VucemCatalogs::$incrementables;
            $decrementables = VucemCatalogs::$decrementables;
            $formasPago = VucemCatalogs::$formasPago;
            
            // Cargar datos guardados (si existen) - borradores previos
            $datosManifestacion = MvDatosManifestacion::where('applicant_id', $applicantId)
                ->where('status', 'borrador')
                ->first();
                
            $informacionCove = MvInformacionCove::where('applicant_id', $applicantId)
                ->where('status', 'borrador')
                ->first();
                
            $documentos = MvDocumentos::where('applicant_id', $applicantId)
                ->where('status', 'borrador')
                ->whereNotNull('documentos')
                ->first();
            
            // Obtener sugerencias de eDocuments registrados
            $edocumentSuggestions = EdocumentRegistrado::orderByDesc('fecha_ultima_consulta')
                ->limit(50)
                ->pluck('folio_edocument');
            
            // Retornar la vista con los datos extraídos del Archivo M
            return view('mve.create-manual', compact(
                'applicant',
                'tiposFigura',
                'metodosValoracion',
                'incoterms',
                'aduanas',
                'incrementables',
                'decrementables',
                'formasPago',
                'datosManifestacion',
                'informacionCove',
                'documentos',
                'edocumentSuggestions',
                'datosExtraidos' // Nueva variable con los datos del Archivo M
            ));
            
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['archivo_m' => 'Error al procesar el archivo: ' . $e->getMessage()])
                ->withInput();
        }
    }
    
    /**
     * Buscar RFC de consulta en la base de datos
     */
    public function searchRfcConsulta(Request $request)
    {
        $request->validate([
            'applicant_rfc' => 'required|string|max:13',
            'rfc_consulta' => 'required|string|max:13'
        ]);
        
        $applicantRfc = strtoupper($request->applicant_rfc);
        $rfcConsulta = strtoupper($request->rfc_consulta);
        
        // Buscar el RFC de consulta asociado al RFC del solicitante
        $rfcData = MveRfcConsulta::where('applicant_rfc', $applicantRfc)
            ->get()
            ->first(function ($item) use ($rfcConsulta) {
                return strtoupper($item->rfc_consulta) === $rfcConsulta;
            });
        
        if ($rfcData) {
            return response()->json([
                'found' => true,
                'data' => [
                    'rfc_consulta' => $rfcData->rfc_consulta,
                    'razon_social' => $rfcData->razon_social,
                    'tipo_figura' => $rfcData->tipo_figura
                ]
            ]);
        }
        
        return response()->json([
            'found' => false,
            'message' => 'El RFC no se encuentra registrado en la BD del sistema'
        ]);
    }
    
    /**
     * Guardar RFC de consulta en la base de datos
     */
    public function storeRfcConsulta(Request $request)
    {
        try {
            $validated = $request->validate([
                'applicant_rfc' => 'required|string|max:13',
                'rfc_consulta' => 'required|string|max:13',
                'razon_social' => 'required|string|max:255',
                'tipo_figura' => 'required|string'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        }
        
        $applicantRfc = strtoupper($request->applicant_rfc);
        $rfcConsulta = strtoupper($request->rfc_consulta);
        
        // Verificar si ya existe
        $exists = MveRfcConsulta::where('applicant_rfc', $applicantRfc)
            ->get()
            ->first(function ($item) use ($rfcConsulta) {
                return strtoupper($item->rfc_consulta) === $rfcConsulta;
            });
        
        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Este RFC de consulta ya está registrado'
            ], 422);
        }
        
        // Guardar el RFC de consulta
        MveRfcConsulta::create([
            'applicant_rfc' => $applicantRfc,
            'rfc_consulta' => $rfcConsulta,
            'razon_social' => $request->razon_social,
            'tipo_figura' => $request->tipo_figura
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'RFC de consulta guardado exitosamente'
        ]);
    }
    
    /**
     * Eliminar RFC de consulta de la base de datos
     */
    public function deleteRfcConsulta(Request $request)
    {
        $request->validate([
            'applicant_rfc' => 'required|string|max:13',
            'rfc_consulta' => 'required|string|max:13'
        ]);
        
        $applicantRfc = strtoupper($request->applicant_rfc);
        $rfcConsulta = strtoupper($request->rfc_consulta);
        
        // Buscar y eliminar el RFC de consulta
        $deleted = false;
        $records = MveRfcConsulta::where('applicant_rfc', $applicantRfc)->get();
        
        foreach ($records as $record) {
            if (strtoupper($record->rfc_consulta) === $rfcConsulta) {
                $record->delete();
                $deleted = true;
                break;
            }
        }
        
        if ($deleted) {
            return response()->json([
                'success' => true,
                'message' => 'RFC de consulta eliminado exitosamente'
            ]);
        }
        
        return response()->json([
            'success' => false,
            'message' => 'RFC de consulta no encontrado'
        ], 404);
    }
    

    
    /**
     * Ver MVE Pendientes (borradores por sección)
     */
    public function pendientes()
    {
        // Obtener todos los solicitantes del usuario
        $applicantIds = MvClientApplicant::where('user_email', auth()->user()->email)
            ->pluck('id');

        // Estados que requieren acción del usuario (borrador, guardado, rechazado)
        $estadosPendientes = ['borrador', 'guardado', 'rechazado'];

        // IMPORTANTE: Excluir applicants que ya tienen una manifestación enviada
        // Esto previene mostrar secciones huérfanas de manifestaciones completadas
        $applicantsConMveEnviada = MvDatosManifestacion::whereIn('applicant_id', $applicantIds)
            ->where('status', 'enviado')
            ->pluck('applicant_id')
            ->toArray();

        $applicantIdsPendientes = array_diff($applicantIds->toArray(), $applicantsConMveEnviada);

        // Obtener pendientes de todas las secciones (incluyendo guardadas sin enviar y rechazadas)
        // SOLO de applicants que NO tienen manifestación enviada
        $datosMvPendientes = MvDatosManifestacion::with('applicant')
            ->whereIn('applicant_id', $applicantIdsPendientes)
            ->whereIn('status', $estadosPendientes)
            ->get();

        $covePendientes = MvInformacionCove::with('applicant')
            ->whereIn('applicant_id', $applicantIdsPendientes)
            ->whereIn('status', $estadosPendientes)
            ->get();

        $documentosPendientes = MvDocumentos::with('applicant')
            ->whereIn('applicant_id', $applicantIdsPendientes)
            ->whereIn('status', $estadosPendientes)
            ->get();

        return view('mve.pendientes', compact('datosMvPendientes', 'covePendientes', 'documentosPendientes'));
    }
    
    /**
     * Ver MVE Completadas (enviadas a VUCEM)
     */
    public function completadas()
    {
        // Obtener todos los solicitantes del usuario
        $applicantIds = MvClientApplicant::where('user_email', auth()->user()->email)
            ->pluck('id');
        
        // Obtener manifestaciones enviadas (con acuse)
        $mveCompletadas = MvAcuse::with(['applicant', 'datosManifestacion'])
            ->whereIn('applicant_id', $applicantIds)
            ->orderByDesc('fecha_envio')
            ->get();
            
        return view('mve.completadas', compact('mveCompletadas'));
    }


    /**
     * Guardar Sección: Datos de Manifestación
     */
    public function saveDatosManifestacion(Request $request, $applicantId)
    {
        $applicant = MvClientApplicant::findOrFail($applicantId);
        
        // Verificar que el solicitante pertenece al usuario actual
        if ($applicant->user_email !== auth()->user()->email) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para acceder a este solicitante.'
            ], 403);
        }
        
        // Buscar registro existente por applicant_id (sin importar status)
        $datosManifestacion = MvDatosManifestacion::where('applicant_id', $applicantId)->first();
        
        $datosActualizar = [
            'rfc_importador' => $request->rfc_importador ?? $applicant->applicant_rfc,
            'metodo_valoracion' => $request->metodo_valoracion,
            'existe_vinculacion' => $request->existe_vinculacion,
            'pedimento' => $request->pedimento,
            'patente' => $request->patente,
            'aduana' => $request->aduana,
            'persona_consulta' => $request->persona_consulta,
            'status' => 'borrador', // Al editar, vuelve a borrador
        ];
        
        if ($datosManifestacion) {
            $datosManifestacion->update($datosActualizar);
        } else {
            $datosActualizar['applicant_id'] = $applicantId;
            $datosManifestacion = MvDatosManifestacion::create($datosActualizar);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Datos de Manifestación guardados exitosamente',
            'section_id' => $datosManifestacion->id
        ]);
    }

    /**
     * Guardar Sección: Información COVE
     */
    public function saveInformacionCove(Request $request, $applicantId)
    {
        $applicant = MvClientApplicant::findOrFail($applicantId);
        
        // Verificar que el solicitante pertenece al usuario actual
        if ($applicant->user_email !== auth()->user()->email) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para acceder a este solicitante.'
            ], 403);
        }

        // Debug log completo para verificar los datos recibidos
        \Log::info('=== DATOS RECIBIDOS EN saveInformacionCove ===');
        \Log::info('Applicant ID: ' . $applicantId);
        \Log::info('Request all:', $request->all());
        \Log::info('informacion_cove recibido:', [
            'cantidad' => count($request->informacion_cove ?? []),
            'datos' => $request->informacion_cove ?? []
        ]);
        
        // Buscar registro existente
        $informacionCove = MvInformacionCove::where('applicant_id', $applicantId)->first();
        
        // Preparar datos para actualizar - SOLO actualizar campos que se envían
        $datosActualizar = [
            'status' => 'borrador',
        ];
        
        // Solo actualizar si se envía en la request (no sobrescribir con vacío)
        if ($request->has('informacion_cove') && !empty($request->informacion_cove)) {
            $datosActualizar['informacion_cove'] = $request->informacion_cove;
        }
        if ($request->has('pedimentos')) {
            $datosActualizar['pedimentos'] = $request->pedimentos;
        }
        if ($request->has('incrementables')) {
            $datosActualizar['incrementables'] = $request->incrementables;
        }
        if ($request->has('decrementables')) {
            $datosActualizar['decrementables'] = $request->decrementables;
        }
        if ($request->has('precio_pagado')) {
            $datosActualizar['precio_pagado'] = $request->precio_pagado;
        }
        if ($request->has('precio_por_pagar')) {
            $datosActualizar['precio_por_pagar'] = $request->precio_por_pagar;
        }
        if ($request->has('compenso_pago')) {
            $datosActualizar['compenso_pago'] = $request->compenso_pago;
        }
        if ($request->has('valor_en_aduana')) {
            $datosActualizar['valor_en_aduana'] = $request->valor_en_aduana;
        }
        
        // Crear o actualizar
        if ($informacionCove) {
            $informacionCove->update($datosActualizar);
        } else {
            $datosActualizar['applicant_id'] = $applicantId;
            $informacionCove = MvInformacionCove::create($datosActualizar);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Información COVE guardada exitosamente',
            'section_id' => $informacionCove->id
        ]);
    }

    /**
     * Guardar Sección: Valor en Aduana
     * NOTA: Ahora se guarda junto con Información COVE en saveInformacionCove
     * Este método se mantiene por compatibilidad pero redirige a saveInformacionCove
     */
    public function saveValorAduana(Request $request, $applicantId)
    {
        // Redirigir a saveInformacionCove ya que ahora todo está en una sola tabla
        return $this->saveInformacionCove($request, $applicantId);
    }

    /**
     * Guardar Sección: Documentos
     */
    public function saveDocumentos(Request $request, $applicantId)
    {
        $applicant = MvClientApplicant::findOrFail($applicantId);
        
        // Verificar que el solicitante pertenece al usuario actual
        if ($applicant->user_email !== auth()->user()->email) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para acceder a este solicitante.'
            ], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'documentos' => 'nullable|array',
            'documentos.*.tipo_documento' => 'nullable|string|max:255',
            'documentos.*.folio_edocument' => 'nullable|string|max:30',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $service = new ManifestacionValorService();
        $documentosInput = $request->input('documentos', []);
        $normalizedDocuments = [];

        foreach ($documentosInput as $documento) {
            $folio = $service->normalizeEdocumentFolio($documento['folio_edocument'] ?? $documento['eDocument'] ?? '');

            if ($folio === '') {
                continue;
            }

            $validation = $service->validateEdocumentFolio($folio);
            if (!$validation['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => $validation['message']
                ], 422);
            }

            $normalizedDocuments[] = [
                'tipo_documento' => trim($documento['tipo_documento'] ?? $documento['nombre'] ?? ''),
                'folio_edocument' => $folio,
                'created_at' => $documento['created_at'] ?? now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
            ];
        }

        // Buscar registro existente por applicant_id (sin importar status)
        $documentos = MvDocumentos::where('applicant_id', $applicantId)->first();
        
        if ($documentos) {
            $documentos->update([
                'documentos' => $normalizedDocuments,
                'status' => 'borrador', // Al editar, vuelve a borrador
            ]);
        } else {
            $documentos = MvDocumentos::create([
                'applicant_id' => $applicantId,
                'documentos' => $normalizedDocuments,
                'status' => 'borrador',
            ]);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Documentos guardados exitosamente',
            'section_id' => $documentos->id
        ]);
    }
    
    public function borrarBorrador(Request $request)
    {
        try {
            $applicantId = $request->applicant_id;
            
            // Verificar que el applicant pertenece al usuario actual
            $applicant = MvClientApplicant::findOrFail($applicantId);
            if ($applicant->user_email !== auth()->user()->email) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para eliminar este borrador.'
                ], 403);
            }
            
            // Eliminar todos los registros editables relacionados con este applicant
            $statusEditables = ['borrador', 'guardado', 'rechazado'];
            
            MvDatosManifestacion::where('applicant_id', $applicantId)
                ->whereIn('status', $statusEditables)
                ->delete();
                
            MvInformacionCove::where('applicant_id', $applicantId)
                ->whereIn('status', $statusEditables)
                ->delete();
                
            MvDocumentos::where('applicant_id', $applicantId)
                ->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Borrador eliminado exitosamente'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el borrador: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verificar si todas las secciones están completas
     */
    public function checkCompletion($applicantId)
    {
        try {
            // Verificar que el applicant pertenece al usuario actual
            $applicant = MvClientApplicant::findOrFail($applicantId);
            if ($applicant->user_email !== auth()->user()->email) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado'
                ], 403);
            }

            // Verificar cada sección
            $datosManifestacion = MvDatosManifestacion::where('applicant_id', $applicantId)->exists();
            $informacionCove = MvInformacionCove::where('applicant_id', $applicantId)->exists();
            
            $documentosRecord = MvDocumentos::where('applicant_id', $applicantId)
                ->whereNotNull('documentos')
                ->first();
            $documentos = $documentosRecord?->documentos ?? [];
            $hasDocuments = collect($documentos)->filter(function ($documento) {
                return !empty($documento['folio_edocument']);
            })->isNotEmpty();
            
            $allComplete = $datosManifestacion && $informacionCove;
            
            return response()->json([
                'success' => true,
                'all_sections_complete' => $allComplete,
                'sections' => [
                    'datos_manifestacion' => $datosManifestacion,
                    'informacion_cove' => $informacionCove,
                    'documentos' => $hasDocuments,
                    'documentos_count' => count($documentos)
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al verificar completitud: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Guardar la manifestación final (cambiar status a 'guardado' - lista para firmar)
     */
    public function saveFinalManifestacion($applicantId)
    {
        try {
            // Verificar que el applicant pertenece al usuario actual
            $applicant = MvClientApplicant::findOrFail($applicantId);
            if ($applicant->user_email !== auth()->user()->email) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado'
                ], 403);
            }

            // Verificar que todas las secciones están completas
            $completionCheck = $this->checkCompletion($applicantId);
            $completionData = json_decode($completionCheck->getContent(), true);
            
            if (!$completionData['all_sections_complete']) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pueden guardar manifestaciones incompletas'
                ], 400);
            }

            // Actualizar el status de todas las secciones a 'guardado' (lista para firma)
            $datosManifestacion = MvDatosManifestacion::where('applicant_id', $applicantId)
                ->first();
            
            if ($datosManifestacion) {
                $datosManifestacion->update(['status' => 'guardado', 'updated_at' => now()]);
            }
                
            MvInformacionCove::where('applicant_id', $applicantId)
                ->update(['status' => 'guardado', 'updated_at' => now()]);
                
            // Los documentos no tienen status, pero actualizamos su timestamp
            MvDocumentos::where('applicant_id', $applicantId)
                ->update(['updated_at' => now()]);
            
            return response()->json([
                'success' => true,
                'message' => 'Manifestación de Valor guardada exitosamente. Ahora puede firmar y enviar a VUCEM.',
                'manifestacion_id' => $datosManifestacion ? $datosManifestacion->id : null,
                'applicant_id' => $applicantId,
                'status' => 'guardado'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar la manifestación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener todos los datos guardados para vista previa
     */
    public function previewData($applicantId)
    {
        try {
            // Verificar que el applicant pertenece al usuario actual
            $applicant = MvClientApplicant::findOrFail($applicantId);
            if ($applicant->user_email !== auth()->user()->email) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado'
                ], 403);
            }

            // Obtener datos de cada sección
            $datosManifestacion = MvDatosManifestacion::where('applicant_id', $applicantId)->first();
            $informacionCove = MvInformacionCove::where('applicant_id', $applicantId)->first();
            $documentosRecord = MvDocumentos::where('applicant_id', $applicantId)
                ->whereNotNull('documentos')
                ->first();
            $documentos = $documentosRecord?->documentos ?? [];
            $cadenaOriginal = app(ManifestacionValorService::class)->buildCadenaOriginal(
                $applicant,
                $datosManifestacion,
                $informacionCove,
                $documentos
            );

            // Preparar respuesta estructurada
            return response()->json([
                'success' => true,
                'applicant' => [
                    'rfc' => $applicant->applicant_rfc,
                    'razon_social' => $applicant->business_name,
                    'email' => $applicant->user_email
                ],
                'datos_manifestacion' => $datosManifestacion ? [
                    'rfc_importador' => $datosManifestacion->rfc_importador,
                    'metodo_valoracion' => $datosManifestacion->metodo_valoracion,
                    'existe_vinculacion' => $datosManifestacion->existe_vinculacion,
                    'pedimento' => $datosManifestacion->pedimento,
                    'patente' => $datosManifestacion->patente,
                    'aduana' => $datosManifestacion->aduana,
                    'persona_consulta' => $datosManifestacion->persona_consulta,
                    'guardado_en' => $datosManifestacion->updated_at->format('d/m/Y H:i:s')
                ] : null,
                'informacion_cove' => $informacionCove ? [
                    'informacion_cove' => $informacionCove->informacion_cove,
                    'pedimentos' => $informacionCove->pedimentos,
                    'incrementables' => $informacionCove->incrementables,
                    'decrementables' => $informacionCove->decrementables,
                    'precio_pagado' => $informacionCove->precio_pagado,
                    'precio_por_pagar' => $informacionCove->precio_por_pagar,
                    'compenso_pago' => $informacionCove->compenso_pago,
                    'guardado_en' => $informacionCove->updated_at->format('d/m/Y H:i:s')
                ] : null,
                'valor_aduana' => $informacionCove ? [
                    'valor_en_aduana_data' => $informacionCove->valor_en_aduana, // Este es el JSON desencriptado
                    'guardado_en' => $informacionCove->updated_at->format('d/m/Y H:i:s')
                ] : null,
                'cadena_original' => $cadenaOriginal,
                'documentos' => $documentos
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener datos de vista previa: ' . $e->getMessage()
            ], 500);
        }
    }

    public function parsePedimentoEdocuments(Request $request, ManifestacionValorService $service)
    {
        $request->validate([
            'layout_text' => 'required|string'
        ]);

        $folios = $service->parsePedimentoEdocuments($request->input('layout_text'));

        return response()->json([
            'success' => true,
            'folios' => $folios
        ]);
    }

    public function validateEdocument(Request $request, ManifestacionValorService $manifestacionService)
    {
        $request->validate([
            'folio' => 'required|string'
        ]);

        $folio = $manifestacionService->normalizeEdocumentFolio($request->input('folio'));
        $validation = $manifestacionService->validateEdocumentFolio($folio);

        if (!$validation['valid']) {
            return response()->json([
                'success' => false,
                'message' => $validation['message']
            ], 422);
        }

        $registro = EdocumentRegistrado::where('folio_edocument', $folio)->first();

        return response()->json([
            'success' => true,
            'configured' => true,
            'valid' => $registro?->existe_en_vucem ?? false,
            'message' => $registro
                ? ($registro->existe_en_vucem ? 'Folio registrado previamente en VUCEM.' : 'Folio sin registro en VUCEM.')
                : 'Folio no registrado en la base. Use Consulta eDocument para registrarlo.'
        ]);
    }

    /**
     * Visualizar documento en el navegador
     */
    public function viewDocument($documentId)
    {
        try {
            $documento = MvDocumentos::findOrFail($documentId);
            
            // Verificar que el documento pertenece al usuario actual
            if ($documento->applicant->user_email !== auth()->user()->email) {
                return response()->json(['message' => 'No autorizado'], 403);
            }

            $content = $documento->getDecodedContent();
            
            return response($content)
                ->header('Content-Type', $documento->mime_type ?: 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="' . $documento->original_filename . '"');
                
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al visualizar documento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Descargar documento
     */
    public function downloadDocument($documentId)
    {
        try {
            $documento = MvDocumentos::findOrFail($documentId);
            
            // Verificar que el documento pertenece al usuario actual
            if ($documento->applicant->user_email !== auth()->user()->email) {
                return response()->json(['message' => 'No autorizado'], 403);
            }

            $content = $documento->getDecodedContent();
            
            return response($content)
                ->header('Content-Type', $documento->mime_type ?: 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $documento->original_filename . '"')
                ->header('Content-Length', strlen($content));
                
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al descargar documento: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Firmar y enviar manifestación a VUCEM mediante AJAX
     * Usa PhpCfdi para procesar los archivos de e.firma
     * Valida el XML SOAP antes de enviar
     */
    public function firmarYEnviarAjax(Request $request, $applicantId)
    {
        try {
            // Verificar que el applicant pertenece al usuario actual
            $applicant = MvClientApplicant::findOrFail($applicantId);
            if ($applicant->user_email !== auth()->user()->email) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado'
                ], 403);
            }
            
            // Validar archivos de e.firma y clave webservice
            $validator = Validator::make($request->all(), [
                'certificado' => 'required|file',
                'llave_privada' => 'required|file',
                'password_llave' => 'required|string',
                'clave_webservice' => 'required|string',
                'confirmacion' => 'required|accepted'
            ], [
                'certificado.required' => 'El archivo de certificado (.cer) es obligatorio',
                'llave_privada.required' => 'El archivo de llave privada (.key) es obligatorio',
                'password_llave.required' => 'La contraseña de la llave privada es obligatoria',
                'clave_webservice.required' => 'La clave del web service de VUCEM es obligatoria',
                'confirmacion.required' => 'Debe confirmar que la información es correcta',
                'confirmacion.accepted' => 'Debe confirmar que la información es correcta'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }
            
            // Obtener los datos de la manifestación
            $datosManifestacion = MvDatosManifestacion::where('applicant_id', $applicantId)->first();
            $informacionCove = MvInformacionCove::where('applicant_id', $applicantId)->first();
            $documentosRecord = MvDocumentos::where('applicant_id', $applicantId)->first();
            
            if (!$datosManifestacion || $datosManifestacion->status !== 'guardado') {
                return response()->json([
                    'success' => false,
                    'message' => 'La manifestación debe estar en estado "guardado" para poder firmar'
                ], 400);
            }

            // =====================================================
            // VALIDACIÓN DEL XML SOAP ANTES DE PROCESAR
            // =====================================================
            $claveWebservice = $request->input('clave_webservice');
            $soapService = new \App\Services\MvVucemSoapService();
            
            // Generar y validar XML sin firma (para verificar estructura de datos)
            $xmlValidation = $soapService->buildSoapXml(
                $applicant,
                $datosManifestacion,
                $informacionCove,
                $documentosRecord,
                strtoupper($applicant->applicant_rfc),
                $claveWebservice,
                [] // Sin firma en validación inicial
            );
            
            // Si hay errores en la estructura del XML, devolver sin continuar
            if (!$xmlValidation['success'] || !empty($xmlValidation['errors'])) {
                $errores = $xmlValidation['errors'] ?? ['Error desconocido en la estructura del XML'];
                Log::warning('MVE AJAX - Validación XML fallida', [
                    'applicant_id' => $applicantId,
                    'errors' => $errores
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Error en los datos de la manifestación: ' . implode(', ', $errores),
                    'validation_errors' => $errores
                ], 400);
            }
            
            // Validar estructura XML
            $structureValidation = $xmlValidation['validation'] ?? [];
            if (!($structureValidation['xml_valid'] ?? false)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error en la estructura del XML SOAP. Verifique los datos ingresados.',
                    'validation_errors' => $structureValidation['errors'] ?? []
                ], 400);
            }

            Log::info('MVE AJAX - Validación XML exitosa', [
                'applicant_id' => $applicantId,
                'xml_valid' => true
            ]);
            // =====================================================
            
            // Guardar archivos temporalmente
            $certificadoPath = $request->file('certificado')->store('temp/efirma');
            $llavePrivadaPath = $request->file('llave_privada')->store('temp/efirma');
            $password = $request->input('password_llave');
            
            try {
                // Usar EFirmaService con PhpCfdi para generar la firma
                $efirmaService = new EFirmaService();
                $mveService = new ManifestacionValorService();
                
                // Obtener la cadena original
                $cadenaOriginal = $mveService->buildCadenaOriginal(
                    $applicant,
                    $datosManifestacion,
                    $informacionCove,
                    $documentosRecord?->documentos ?? []
                );
                
                Log::info('MVE AJAX - Generando firma con PhpCfdi', [
                    'applicant_id' => $applicantId,
                    'longitud_cadena' => strlen($cadenaOriginal)
                ]);
                
                // Generar firma con PhpCfdi
                $firmaResult = $efirmaService->generarFirmaElectronicaConArchivos(
                    $cadenaOriginal,
                    strtoupper($applicant->applicant_rfc),
                    Storage::path($certificadoPath),
                    Storage::path($llavePrivadaPath),
                    $password
                );
                
                Log::info('MVE AJAX - Firma generada exitosamente', [
                    'applicant_id' => $applicantId
                ]);
                
                // Usar MveSignService para construir XML y enviar a VUCEM
                $signService = new MveSignService();
                $resultado = $signService->firmarYEnviarManifestacion(
                    $applicant,
                    $datosManifestacion,
                    $informacionCove,
                    $documentosRecord,
                    Storage::path($certificadoPath),
                    Storage::path($llavePrivadaPath),
                    $password,
                    $claveWebservice // <--- CORRECCIÓN APLICADA AQUÍ
                );
                
                // Limpiar archivos temporales
                Storage::delete([$certificadoPath, $llavePrivadaPath]);
                
                if ($resultado['success']) {
                    // Actualizar status a 'enviado' en TODAS las secciones
                    $datosManifestacion->update(['status' => 'enviado']);
                    MvInformacionCove::where('applicant_id', $applicantId)
                        ->update(['status' => 'enviado']);
                    MvDocumentos::where('applicant_id', $applicantId)
                        ->update(['status' => 'enviado']);

                    return response()->json([
                        'success' => true,
                        'message' => $resultado['message'],
                        'folio' => $resultado['folio'] ?? null,
                        'acuse_id' => $resultado['acuse_id'] ?? null,
                        'modo' => $resultado['modo'] ?? 'produccion',
                        'redirect_url' => isset($resultado['acuse_id']) ? route('mve.acuse', $datosManifestacion->id) : null
                    ]);
                } else {
                    // Si VUCEM rechazó, actualizar status a 'rechazado' en TODAS las secciones
                    if (isset($resultado['status']) && $resultado['status'] === 'RECHAZADO') {
                        $datosManifestacion->update(['status' => 'rechazado']);
                        MvInformacionCove::where('applicant_id', $applicantId)
                            ->update(['status' => 'rechazado']);
                        MvDocumentos::where('applicant_id', $applicantId)
                            ->update(['status' => 'rechazado']);
                    }
                    
                    return response()->json([
                        'success' => false,
                        'message' => $resultado['message']
                    ], 400);
                }
                
            } catch (\Exception $e) {
                // Limpiar archivos temporales en caso de error
                Storage::delete([$certificadoPath, $llavePrivadaPath]);
                
                Log::error('MVE AJAX - Error en firma', [
                    'applicant_id' => $applicantId,
                    'error' => $e->getMessage()
                ]);
                
                throw $e;
            }
            
        } catch (\Exception $e) {
            Log::error('MVE AJAX - Error general', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la firma: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Descartar/eliminar una manifestación de valor
     */
    public function descartarManifestacion($applicantId)
    {
        try {
            // Verificar que el applicant pertenece al usuario actual
            $applicant = MvClientApplicant::findOrFail($applicantId);
            if ($applicant->user_email !== auth()->user()->email) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado'
                ], 403);
            }
            
            // Obtener datos para verificar estado
            $datosManifestacion = MvDatosManifestacion::where('applicant_id', $applicantId)->first();
            
            // Solo permitir descartar si está en estado borrador, guardado o rechazado
            $estadosPermitidos = ['borrador', 'guardado', 'rechazado'];
            if ($datosManifestacion && !in_array($datosManifestacion->status, $estadosPermitidos)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede descartar una manifestación que ya fue enviada a VUCEM'
                ], 400);
            }
            
            // Eliminar todos los registros relacionados
            MvDocumentos::where('applicant_id', $applicantId)->delete();
            MvInformacionCove::where('applicant_id', $applicantId)->delete();
            MvDatosManifestacion::where('applicant_id', $applicantId)->delete();
            
            Log::info('MVE - Manifestación descartada', [
                'applicant_id' => $applicantId,
                'user' => auth()->user()->email
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Manifestación de Valor descartada exitosamente'
            ]);
            
        } catch (\Exception $e) {
            Log::error('MVE - Error al descartar manifestación', [
                'applicant_id' => $applicantId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al descartar la manifestación: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Mostrar formulario de firma para una manifestación de valor
     */
    public function showSign($manifestacionId)
    {
        $manifestacion = MvDatosManifestacion::with(['applicant', 'informacionCove', 'documentos'])
            ->findOrFail($manifestacionId);
        
        // Verificar que el solicitante pertenece al usuario actual
        if ($manifestacion->applicant->user_email !== auth()->user()->email) {
            abort(403, 'No tienes permiso para acceder a esta manifestación.');
        }
        
        // Verificar que la manifestación está en estado adecuado para firmar
        if ($manifestacion->status !== 'completado' && $manifestacion->status !== 'rechazada') {
            return redirect()->back()->with('error', 'La manifestación debe estar completada para poder firmarla.');
        }
        
        // Verificar si ya existe un acuse
        $acuse = MvAcuse::where('datos_manifestacion_id', $manifestacionId)->first();
        
        return view('mve.sign', compact('manifestacion', 'acuse'));
    }
    
    /**
     * Procesar firma y envío a VUCEM
     */
    public function processSign(Request $request, $manifestacionId)
    {
        $manifestacion = MvDatosManifestacion::with(['applicant', 'informacionCove', 'documentos'])
            ->findOrFail($manifestacionId);
        
        // Verificar que el solicitante pertenece al usuario actual
        if ($manifestacion->applicant->user_email !== auth()->user()->email) {
            abort(403, 'No tienes permiso para acceder a esta manifestación.');
        }
        
        // Validar archivos de certificado
        $request->validate([
            'certificado' => 'required|file|mimes:cer,crt',
            'llave_privada' => 'required|file|mimes:key,pem',
            'password_llave' => 'required|string'
        ]);
        
        try {
            // Obtener contenido de los archivos
            $certificadoContent = file_get_contents($request->file('certificado')->getRealPath());
            $llavePrivadaContent = file_get_contents($request->file('llave_privada')->getRealPath());
            $password = $request->input('password_llave');
            
            // Usar el servicio de firma
            $signService = new MveSignService();
            $resultado = $signService->firmarYEnviarManifestacion(
                $manifestacion,
                $certificadoContent,
                $llavePrivadaContent,
                $password
            );
            
            if ($resultado['success']) {
                return redirect()->route('mve.acuse', ['manifestacionId' => $manifestacionId])
                    ->with('success', $resultado['message']);
            } else {
                return redirect()->back()
                    ->with('error', $resultado['message'])
                    ->withInput();
            }
            
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al procesar la firma: ' . $e->getMessage())
                ->withInput();
        }
    }
    
    /**
     * Ver el acuse de una manifestación
     */
    public function showAcuse($manifestacionId)
    {
        $manifestacion = MvDatosManifestacion::with('applicant')
            ->findOrFail($manifestacionId);
        
        // Verificar que el solicitante pertenece al usuario actual
        if ($manifestacion->applicant->user_email !== auth()->user()->email) {
            abort(403, 'No tienes permiso para acceder a esta manifestación.');
        }
        
        // Obtener el acuse
        $acuse = MvAcuse::where('datos_manifestacion_id', $manifestacionId)->firstOrFail();
        
        return view('mve.acuse', compact('manifestacion', 'acuse'));
    }
    
    /**
     * Descargar el PDF del acuse (Decodifica el Base64 guardado)
     * Acepta tanto acuse_id como datos_manifestacion_id
     */
    public function downloadAcusePdf($id)
    {
        try {
            // 1. Buscar el acuse - primero intenta por ID directo, luego por datos_manifestacion_id
            $acuse = MvAcuse::find($id);
            if (!$acuse) {
                $acuse = MvAcuse::where('datos_manifestacion_id', $id)->first();
            }
            
            if (!$acuse) {
                abort(404, 'Acuse no encontrado.');
            }
            
            // 2. Verificar permisos de seguridad
            if ($acuse->applicant->user_email !== auth()->user()->email) {
                abort(403, 'No tienes permiso para acceder a este acuse.');
            }
            
            // 3. Verificar si realmente tenemos el archivo
            if (empty($acuse->acuse_pdf)) {
                return back()->with('error', 'El archivo PDF aún no está disponible. Por favor, haga clic en "Consultar Estatus" primero para recuperarlo de VUCEM.');
            }
            
            // 4. Decodificar: De texto Base64 a binario PDF
            $base64Clean = $acuse->acuse_pdf;
            
            // Limpiar posibles entidades XML que hayan quedado guardadas
            $base64Clean = html_entity_decode($base64Clean, ENT_XML1, 'UTF-8');
            $base64Clean = preg_replace('/[\r\n\s]+/', '', $base64Clean);
            
            $pdfContent = base64_decode($base64Clean, true);
            
            // Verificar que el PDF sea válido
            if ($pdfContent === false || substr($pdfContent, 0, 4) !== '%PDF') {
                \Log::error('PDF acuse inválido', [
                    'acuse_id' => $acuse->id,
                    'base64_length' => strlen($acuse->acuse_pdf),
                    'decoded_length' => strlen($pdfContent ?? ''),
                    'starts_with' => substr($pdfContent ?? '', 0, 10)
                ]);
                return back()->with('error', 'El archivo PDF está corrupto. Intente consultar el estatus nuevamente.');
            }
            
            // 5. Generar nombre del archivo (Preferimos el MVE real "MNVA...", si no, el folio de operación)
            $nombreArchivo = 'Acuse_MVE_' . ($acuse->numero_cove ?? $acuse->folio_manifestacion) . '.pdf';
            
            // 6. Entregar al navegador para descarga
            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $nombreArchivo . '"')
                ->header('Content-Length', strlen($pdfContent));

        } catch (\Exception $e) {
            \Log::error('Error al descargar PDF acuse: ' . $e->getMessage());
            return back()->with('error', 'Ocurrió un error al procesar el archivo.');
        }
    }
    
    /**
     * Descargar el XML de respuesta del acuse
     */
    public function downloadAcuseXml($manifestacionId)
    {
        $acuse = MvAcuse::where('datos_manifestacion_id', $manifestacionId)->firstOrFail();

        // Verificar permisos
        $manifestacion = MvDatosManifestacion::with('applicant')->findOrFail($manifestacionId);
        if ($manifestacion->applicant->user_email !== auth()->user()->email) {
            abort(403, 'No tienes permiso para acceder a este acuse.');
        }

        if (!$acuse->xml_respuesta) {
            abort(404, 'No hay XML de respuesta disponible.');
        }

        return response($acuse->xml_respuesta)
            ->header('Content-Type', 'application/xml')
            ->header('Content-Disposition', 'attachment; filename="respuesta_mve_' . $acuse->folio_manifestacion . '.xml"')
            ->header('Content-Length', strlen($acuse->xml_respuesta));
    }

    /**
     * Limpiar datos huérfanos - Sincronizar status de todas las secciones
     * Actualiza documentos e información COVE que tienen status inconsistente
     */
    public function limpiarDatosHuerfanos()
    {
        try {
            $actualizados = 0;

            // Encontrar todos los applicants con datos_manifestacion enviados
            $applicantsEnviados = MvDatosManifestacion::where('status', 'enviado')
                ->pluck('applicant_id')
                ->unique();

            // Actualizar documentos e info COVE de estos applicants a 'enviado'
            foreach ($applicantsEnviados as $applicantId) {
                $updated = MvDocumentos::where('applicant_id', $applicantId)
                    ->whereIn('status', ['borrador', 'guardado', 'rechazado'])
                    ->update(['status' => 'enviado']);
                $actualizados += $updated;

                $updated = MvInformacionCove::where('applicant_id', $applicantId)
                    ->whereIn('status', ['borrador', 'guardado', 'rechazado'])
                    ->update(['status' => 'enviado']);
                $actualizados += $updated;
            }

            Log::info('[MVE] Limpieza de datos huérfanos completada', [
                'registros_actualizados' => $actualizados,
                'applicants_procesados' => count($applicantsEnviados)
            ]);

            return response()->json([
                'success' => true,
                'message' => "Limpieza completada. Se actualizaron {$actualizados} registros huérfanos.",
                'applicants_procesados' => count($applicantsEnviados)
            ]);

        } catch (\Exception $e) {
            Log::error('[MVE] Error en limpieza de datos huérfanos', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al limpiar datos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Consultar manifestación en VUCEM para obtener número de MV (MNVA) y acuse PDF
     * Soluciona el problema de tener solo el Número de Operación (folio pequeño).
     */
    public function consultarManifestacion(Request $request, $acuseId)
    {
        try {
            // 1. Obtener el acuse y verificar permisos
            $acuse = MvAcuse::with(['applicant', 'datosManifestacion'])->findOrFail($acuseId);

            if ($acuse->applicant->user_email !== auth()->user()->email) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para consultar este acuse.'
                ], 403);
            }

            // 2. Determinar qué folio usar para la consulta
            // Prioridad 1: Si el usuario lo escribe manualmente (corrección de errores)
            if ($request->has('folio') && !empty($request->input('folio'))) {
                $folioConsulta = trim($request->input('folio'));
            } 
            // Prioridad 2: Si ya tenemos el Número de MVE (MNVA...), usarlo para intentar bajar el PDF de nuevo
            elseif (!empty($acuse->numero_cove)) {
                $folioConsulta = $acuse->numero_cove;
            } 
            // Prioridad 3: Usar el "Folio Pequeño" (Número de Operación) que nos dio VUCEM al registrar
            else {
                $folioConsulta = $acuse->folio_manifestacion;
            }

            // Validar que tengamos algo que consultar
            if (empty($folioConsulta)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay folio de operación ni número de MVE para consultar.'
                ], 400);
            }

            // Obtener credenciales (del request o usar las guardadas si fuera necesario)
            $claveWebservice = $request->input('clave_webservice');
            if (empty($claveWebservice)) {
                 return response()->json([
                    'success' => false,
                    'message' => 'La clave Web Service es obligatoria para consultar.'
                ], 422);
            }

            $rfc = strtoupper($acuse->applicant->applicant_rfc);

            Log::info('[MV_CONSULTA] Iniciando consulta inteligente', [
                'acuse_id' => $acuseId,
                'folio_usado' => $folioConsulta,
                'tipo_origen' => !empty($acuse->numero_cove) ? 'MVE Existente' : 'Operación (Folio Pequeño)',
                'rfc' => $rfc
            ]);

            // 3. Ejecutar la consulta a VUCEM
            $consultaService = new \App\Services\MveConsultaService();
            $resultado = $consultaService->consultarManifestacion(
                $folioConsulta,
                $rfc,
                $claveWebservice
            );

            if ($resultado['success']) {
                // 4. LÓGICA CRÍTICA: Conversión de Folio Pequeño a MVE Real
                // Si la consulta nos devuelve un 'numero_mv' y nosotros NO lo teníamos (o era diferente)
                if (!empty($resultado['numero_mv']) && $acuse->numero_cove !== $resultado['numero_mv']) {
                    Log::info('[MV_CONSULTA] ¡Éxito! Folio de operación convertido a MVE Real', [
                        'operacion' => $folioConsulta,
                        'nuevo_mve' => $resultado['numero_mv']
                    ]);
                    
                    // Guardamos el MVE real en la base de datos
                    $acuse->numero_cove = $resultado['numero_mv'];
                    $acuse->save();
                    
                    // Actualizamos el folio de consulta para el siguiente paso (PDF)
                    $folioConsulta = $resultado['numero_mv']; 
                }

                // Actualizar otros datos del acuse con la respuesta (status, fechas, etc.)
                $consultaService->actualizarAcuseConConsulta($acuse, $resultado);

                return response()->json([
                    'success' => true,
                    'message' => 'Consulta exitosa. Estatus: ' . $resultado['status'],
                    'data' => [
                        'numero_mv' => $resultado['numero_mv'],
                        'folio_operacion' => $acuse->folio_manifestacion,
                        'status' => $resultado['status'],
                        'fecha_registro' => $resultado['fecha_registro'],
                        'datos_manifestacion' => $resultado['datos_manifestacion'] ?? null
                    ]
                ]);

            } else {
                // VUCEM respondió con error o "No encontrado"
                return response()->json([
                    'success' => false,
                    'message' => $resultado['message'],
                    'errores' => $resultado['errores'] ?? []
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('[MV_CONSULTA] Error crítico en controlador', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno al consultar la manifestación: ' . $e->getMessage()
            ], 500);
        }
    }

}
