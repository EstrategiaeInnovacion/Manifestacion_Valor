<?php

namespace App\Http\Controllers;

use App\Models\CoveDocument;
use App\Models\MvClientApplicant;
use App\Services\CoveService;
use App\Jobs\SendCoveToVucem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CoveController extends Controller
{
    /**
     * Lista de COVEs pendientes, en borrador o con error.
     */
    public function pendientes()
    {
        $user = auth()->user();
        
        $query = CoveDocument::whereIn('status', ['borrador', 'guardado', 'procesando_vucem', 'rechazado', 'error']);

        // Filtrado por permisos imitando a MVE
        if ($user->role === 'Usuario') {
            $query->where('created_by_user_id', $user->id);
        }

        $coves = $query->with('applicant')->orderBy('updated_at', 'desc')->paginate(10);

        return view('cove.pendientes', compact('coves'));
    }

    /**
     * Lista de COVEs enviados exitosamente.
     */
    public function completadas()
    {
        $user = auth()->user();
        
        $query = CoveDocument::where('status', 'enviado');

        if ($user->role === 'Usuario') {
             $query->where('created_by_user_id', $user->id);
        }

        $coves = $query->with('applicant')->orderBy('updated_at', 'desc')->paginate(10);

        return view('cove.completadas', compact('coves'));
    }

    /**
     * Selección de aplicante antes de crear COVE.
     */
    public function selectApplicant()
    {
        $user = auth()->user();
        $query = MvClientApplicant::query();

        if ($user->role === 'Usuario') {
            $query->whereHas('assignedUsers', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }
        
        $applicants = $query->get();
        return view('cove.select-applicant', compact('applicants'));
    }

    /**
     * Vista principal de captura manual de COVE.
     */
    public function createManual(MvClientApplicant $applicant)
    {
        // Se puede buscar un borrador existente o inicializar uno nuevo en memoria para la vista
        $coveDocument = CoveDocument::where('applicant_id', $applicant->id)
            ->whereIn('status', ['borrador', 'guardado'])
            ->where('created_by_user_id', Auth::id())
            ->first();

        return view('cove.create-manual', compact('applicant', 'coveDocument'));
    }

    /**
     * Guardado de borrador / payload mediante AJAX.
     */
    public function saveDraft(Request $request, MvClientApplicant $applicant)
    {
        $validated = $request->validate([
            'cove_id' => 'nullable|exists:cove_documents,id',
            'payload' => 'required|array',
            'tipo_operacion' => 'nullable|string',
            'patente_aduanal' => 'nullable|string'
        ]);

        $coveDocument = null;
        if (!empty($validated['cove_id'])) {
            $coveDocument = CoveDocument::find($validated['cove_id']);
        }

        if (!$coveDocument) {
             $coveDocument = new CoveDocument();
             $coveDocument->applicant_id = $applicant->id;
             $coveDocument->created_by_user_id = Auth::id();
        }

        $coveDocument->tipo_operacion = $validated['tipo_operacion'] ?? 'TOL';
        $coveDocument->patente_aduanal = $validated['patente_aduanal'] ?? null;
        $coveDocument->status = 'guardado';
        
        // El mutator se encarga de cifrar el JSON
        $coveDocument->payload = $validated['payload'];
        $coveDocument->save();

        return response()->json([
            'success' => true,
            'message' => 'Borrador guardado correctamente.',
            'cove_id' => $coveDocument->id
        ]);
    }

    /**
     * Proceso de firmado y envío asíncrono a VUCEM.
     */
    public function firmarEnviarAjax(Request $request, MvClientApplicant $applicant)
    {
        $request->validate([
             'cove_id' => 'required|exists:cove_documents,id',
             'rfc' => 'required|string',
             'password' => 'required|string', // Contraseña de la e.firma
        ]);

        $coveDocument = CoveDocument::findOrFail($request->cove_id);

        if ($coveDocument->status === 'procesando_vucem') {
             return response()->json(['success' => false, 'message' => 'Este documento ya se está procesando.']);
        }

        try {
            // Se asume que el Applicant tiene los archivos en su lugar 
            // (La logica real del archivo .cer y .key requiere adaptación a donde los almacenes)
            $certificatePath = storage_path("app/private/cer/{$applicant->id}/cert.cer");
            $privateKeyPath = storage_path("app/private/key/{$applicant->id}/key.key");

            if (!file_exists($certificatePath) || !file_exists($privateKeyPath)) {
                 return response()->json(['success' => false, 'message' => 'Archivos de la FIEL del Importador/Exportador faltantes.']);
            }

            // En Laravel el WebService Clave generalmte viene del Applicant o .env
            $claveWebservice = config('vucem.clave_webservice_default'); // Dummy config referenciada a algo tuyo

            $coveService = new CoveService();
            $result = $coveService->prepararCoveXml(
                $applicant,
                $coveDocument,
                $certificatePath,
                $privateKeyPath,
                $request->password,
                $claveWebservice
            );

            if (!$result['success']) {
                return response()->json(['success' => false, 'message' => $result['message'], 'errors' => $result['errors'] ?? []]);
            }

            $xmlIso = $result['xml'];

            // Lanzar el JOB
            SendCoveToVucem::dispatch(
                $coveDocument->id,
                $applicant->id,
                $xmlIso,
                $claveWebservice
            );

            // Cambiar status
            $coveDocument->status = 'procesando_vucem';
            $coveDocument->save();

            return response()->json([
                'success' => true,
                'message' => 'El COVE fue firmado y se ha mandado a VUCEM en segundo plano.'
            ]);

        } catch (\Exception $e) {
             return response()->json(['success' => false, 'message' => 'Error al procesar: ' . $e->getMessage()]);
        }
    }
}
