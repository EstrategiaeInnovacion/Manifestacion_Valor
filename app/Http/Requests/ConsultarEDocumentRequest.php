<?php

namespace App\Http\Requests;

use App\Models\MvClientApplicant;
use Illuminate\Foundation\Http\FormRequest;

class ConsultarEDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        \Illuminate\Support\Facades\Log::info('[COVE] FormRequest rules() called', [
            'solicitante_id' => $this->input('solicitante_id'),
            'all_inputs' => array_keys($this->all()),
        ]);

        // Verificar si el solicitante tiene credenciales almacenadas
        $solicitante = null;
        if ($this->filled('solicitante_id')) {
            $solicitante = auth()->user()->clientApplicants()->find($this->input('solicitante_id'));
        }

        $hasStoredCredentials = $solicitante && $solicitante->hasVucemCredentials();
        $hasStoredWebserviceKey = $solicitante && $solicitante->hasWebserviceKey();

        return [
            'folio_edocument' => ['required', 'string', 'max:30'],
            'solicitante_id' => ['required', 'exists:mv_client_applicants,id'],
            // Si tiene clave WS almacenada y no se envía manual, no es requerida
            'clave_webservice' => [$hasStoredWebserviceKey ? 'nullable' : 'required', 'string'],
            // Si tiene sellos almacenados, los archivos manuales son opcionales
            'certificado' => [
                $hasStoredCredentials ? 'nullable' : 'required',
                'file', 'max:4096',
                'mimetypes:application/x-x509-ca-cert,application/x-x509-user-cert,application/pkix-cert,application/x-pem-file,application/octet-stream',
            ],
            'llave_privada' => [
                $hasStoredCredentials ? 'nullable' : 'required',
                'file', 'max:4096',
            ],
            'contrasena_llave' => [
                $hasStoredCredentials ? 'nullable' : 'required',
                'string', 'max:255',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'folio_edocument.required' => 'El folio eDocument es obligatorio.',
            'solicitante_id.required' => 'Debe seleccionar un solicitante.',
            'solicitante_id.exists' => 'El solicitante seleccionado no es válido.',
            'clave_webservice.required' => 'La contraseña del Web Service VUCEM es obligatoria.',
            'certificado.required' => 'El certificado de eFirma es obligatorio.',
            'certificado.file' => 'El certificado debe ser un archivo válido.',
            'llave_privada.required' => 'La llave privada de eFirma es obligatoria.',
            'llave_privada.file' => 'La llave privada debe ser un archivo válido.',
            'contrasena_llave.required' => 'La contraseña de la llave privada es obligatoria.',
        ];
    }
}