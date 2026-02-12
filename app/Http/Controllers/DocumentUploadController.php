<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Services\DocumentUploadService;
use App\Models\MvDocumentos;
use App\Models\MvClientApplicant;

class DocumentUploadController extends Controller
{
    protected DocumentUploadService $documentService;

    public function __construct(DocumentUploadService $documentService)
    {
        $this->documentService = $documentService;
    }

    /**
     * Subir documento PDF con validación y conversión automática VUCEM
     */
    public function uploadDocument(Request $request)
    {
        try {
            // Validar request
            $maxSizeKb = config('pdftools.max_size_mb', 50) * 1024; // Convertir MB a KB
            $request->validate([
                'applicant_id' => 'required|exists:mv_client_applicants,id',
                'document_name' => 'required|string|max:255',
                'document_file' => "required|file|mimes:pdf|max:{$maxSizeKb}", // Máx configurable
            ]);

            $applicantId = $request->input('applicant_id');
            $documentName = $request->input('document_name');
            $file = $request->file('document_file');

            // Verificar que el solicitante pertenece al usuario actual
            $applicant = MvClientApplicant::findOrFail($applicantId);
            if ($applicant->user_email !== auth()->user()->email) {
                return response()->json([
                    'success' => false,
                    'error' => 'No tienes permiso para subir documentos a este solicitante.'
                ], 403);
            }

            Log::info('DocumentUploadController: Iniciando subida de documento', [
                'applicant_id' => $applicantId,
                'document_name' => $documentName,
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize()
            ]);

            // Procesar archivo (validar y convertir si es necesario)
            $result = $this->documentService->processUploadedPdf($file); // Sin parámetro destinationPath

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'error' => $result['error']
                ], 422);
            }

            // Guardar información del documento en base de datos con contenido base64
            $documento = new MvDocumentos();
            $documento->applicant_id = $applicantId;
            $documento->document_name = $documentName;
            $documento->file_path = null; // Ya no usamos file_path
            $documento->original_filename = $result['original_name'];
            $documento->file_size = $result['final_size'];
            $documento->is_vucem_compliant = $result['is_vucem_valid'];
            $documento->was_converted = $result['was_converted'];
            $documento->file_content_base64 = $result['file_content'];
            $documento->mime_type = $result['mime_type'];
            $documento->status = 'borrador';
            $documento->uploaded_by = auth()->user()->email;
            $documento->save();

            Log::info('DocumentUploadController: Documento guardado exitosamente', [
                'documento_id' => $documento->id,
                'was_converted' => $result['was_converted'],
                'is_vucem_valid' => $result['is_vucem_valid']
            ]);

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'document' => [
                    'id' => $documento->id,
                    'name' => $documento->document_name,
                    'filename' => $documento->original_filename,
                    'size' => $documento->file_size,
                    'was_converted' => $documento->was_converted,
                    'is_vucem_compliant' => $documento->is_vucem_compliant,
                    'upload_date' => $documento->created_at->format('d/m/Y H:i')
                ],
                'validation_details' => $result['validation_details']
            ]);

        }
        catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Datos de entrada inválidos',
                'validation_errors' => $e->errors()
            ], 422);

        }
        catch (\Exception $e) {
            Log::error('DocumentUploadController: Error subiendo documento', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error interno del servidor. Por favor, intente nuevamente.'
            ], 500);
        }
    }

    /**
     * Obtener lista de documentos de un solicitante
     */
    public function getDocuments(Request $request, $applicantId)
    {
        try {
            // Verificar que el solicitante pertenece al usuario actual
            $applicant = MvClientApplicant::findOrFail($applicantId);
            if ($applicant->user_email !== auth()->user()->email) {
                return response()->json([
                    'success' => false,
                    'error' => 'No tienes permiso para ver los documentos de este solicitante.'
                ], 403);
            }

            $documents = MvDocumentos::where('applicant_id', $applicantId)
                ->where('status', 'borrador')
                ->whereNotNull('original_filename')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($doc) {
                return [
                'id' => $doc->id,
                'name' => $doc->document_name,
                'filename' => $doc->original_filename,
                'size' => $doc->file_size,
                'size_formatted' => $this->formatFileSize($doc->file_size),
                'was_converted' => $doc->was_converted,
                'is_vucem_compliant' => $doc->is_vucem_compliant,
                'upload_date' => $doc->created_at->format('d/m/Y H:i'),
                'uploaded_by' => $doc->uploaded_by
                ];
            });

            return response()->json([
                'success' => true,
                'documents' => $documents
            ]);

        }
        catch (\Exception $e) {
            Log::error('DocumentUploadController: Error obteniendo documentos', [
                'applicant_id' => $applicantId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error obteniendo documentos'
            ], 500);
        }
    }

    /**
     * Eliminar documento
     */
    public function deleteDocument(Request $request, $documentId)
    {
        try {
            $document = MvDocumentos::findOrFail($documentId);

            // Verificar que el documento pertenece al usuario actual
            $applicant = MvClientApplicant::findOrFail($document->applicant_id);
            if ($applicant->user_email !== auth()->user()->email) {
                return response()->json([
                    'success' => false,
                    'error' => 'No tienes permiso para eliminar este documento.'
                ], 403);
            }

            // Ya no necesitamos eliminar archivo físico (se almacena en base64)
            // Eliminar registro de base de datos
            $document->delete();

            Log::info('DocumentUploadController: Documento eliminado', [
                'document_id' => $documentId,
                'document_name' => $document->document_name
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Documento eliminado exitosamente'
            ]);

        }
        catch (\Exception $e) {
            Log::error('DocumentUploadController: Error eliminando documento', [
                'document_id' => $documentId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error eliminando documento'
            ], 500);
        }
    }

    /**
     * Descargar documento
     */
    public function downloadDocument(Request $request, $documentId)
    {
        try {
            $document = MvDocumentos::findOrFail($documentId);

            // Verificar que el documento pertenece al usuario actual
            $applicant = MvClientApplicant::findOrFail($document->applicant_id);
            if ($applicant->user_email !== auth()->user()->email) {
                return response()->json([
                    'success' => false,
                    'error' => 'No tienes permiso para descargar este documento.'
                ], 403);
            }

            // Verificar que el documento tiene contenido base64
            if (empty($document->file_content_base64)) {
                return response()->json([
                    'success' => false,
                    'error' => 'El documento no tiene contenido disponible.'
                ], 404);
            }

            // Crear respuesta con el contenido decodificado
            $decodedContent = base64_decode($document->file_content_base64);
            $mimeType = $document->mime_type ?: 'application/pdf';

            return response($decodedContent, 200)
                ->header('Content-Type', $mimeType)
                ->header('Content-Disposition', 'attachment; filename="' . $document->original_filename . '"')
                ->header('Content-Length', strlen($decodedContent));

        }
        catch (\Exception $e) {
            Log::error('DocumentUploadController: Error descargando documento', [
                'document_id' => $documentId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error descargando documento'
            ], 500);
        }
    }

    /**
     * Visualizar documento (mostrar en navegador)
     */
    public function viewDocument(Request $request, $documentId)
    {
        try {
            $document = MvDocumentos::findOrFail($documentId);

            // Verificar que el documento pertenece al usuario actual
            $applicant = MvClientApplicant::findOrFail($document->applicant_id);
            if ($applicant->user_email !== auth()->user()->email) {
                return response()->json([
                    'success' => false,
                    'error' => 'No tienes permiso para visualizar este documento.'
                ], 403);
            }

            // Verificar que el documento tiene contenido base64
            if (empty($document->file_content_base64)) {
                return response()->json([
                    'success' => false,
                    'error' => 'El documento no tiene contenido disponible.'
                ], 404);
            }

            // Crear respuesta para mostrar en navegador
            $decodedContent = base64_decode($document->file_content_base64);
            $mimeType = $document->mime_type ?: 'application/pdf';

            return response($decodedContent, 200)
                ->header('Content-Type', $mimeType)
                ->header('Content-Disposition', 'inline; filename="' . $document->original_filename . '"')
                ->header('Content-Length', strlen($decodedContent));

        }
        catch (\Exception $e) {
            Log::error('DocumentUploadController: Error visualizando documento', [
                'document_id' => $documentId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error visualizando documento'
            ], 500);
        }
    }

    /**
     * Validar PDF sin subirlo (preview de validación)
     */
    public function validatePdfPreview(Request $request)
    {
        try {
            $maxSizeKb = config('pdftools.max_size_mb', 50) * 1024; // Convertir MB a KB
            $request->validate([
                'pdf_file' => "required|file|mimes:pdf|max:{$maxSizeKb}", // Máx configurable
            ]);

            $file = $request->file('pdf_file');

            // Guardar temporalmente
            $tempPath = $file->store('tmp/validation');
            $fullTempPath = Storage::path($tempPath);

            // Validar formato VUCEM
            $validationResult = $this->documentService->validateVucemFormat($fullTempPath);

            // Limpiar archivo temporal
            Storage::delete($tempPath);

            return response()->json([
                'success' => true,
                'is_valid' => $validationResult['is_valid'],
                'errors' => $validationResult['errors'],
                'details' => $validationResult['details'],
                'message' => $validationResult['is_valid']
                ? 'El archivo cumple con los requisitos VUCEM'
                : 'El archivo requiere conversión para cumplir con VUCEM'
            ]);

        }
        catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error al validar el archivo. Por favor, intente nuevamente.'
            ], 500);
        }
    }

    /**
     * Formatear tamaño de archivo
     */
    protected function formatFileSize(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        }
        elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        else {
            return $bytes . ' bytes';
        }
    }
}
