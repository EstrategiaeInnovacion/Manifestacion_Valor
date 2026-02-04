<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConsultarEDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'folio_edocument' => ['required', 'string', 'max:30'],
            'solicitante_id' => ['required', 'exists:mv_client_applicants,id'],
            'certificado' => ['required', 'file', 'max:4096', 'mimetypes:application/x-x509-ca-cert,application/x-x509-user-cert,application/pkix-cert,application/x-pem-file,application/octet-stream'],
            'llave_privada' => ['required', 'file', 'max:4096'],
            'contrasena_llave' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'folio_edocument.required' => 'El folio eDocument es obligatorio.',
            'solicitante_id.required' => 'Debe seleccionar un solicitante.',
            'solicitante_id.exists' => 'El solicitante seleccionado no es válido.',
            'certificado.required' => 'El certificado de eFirma es obligatorio.',
            'certificado.file' => 'El certificado debe ser un archivo válido.',
            'llave_privada.required' => 'La llave privada de eFirma es obligatoria.',
            'llave_privada.file' => 'La llave privada debe ser un archivo válido.',
            'contrasena_llave.required' => 'La contraseña de la llave privada es obligatoria.',
        ];
    }
}
