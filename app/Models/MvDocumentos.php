<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MvDocumentos extends Model
{
    protected $table = 'mv_documentos';
    
    protected $fillable = [
        'applicant_id',
        'status',
        'documentos',
        'document_name',
        'tipo_documento',
        'folio_edocument',

        'original_filename',
        'file_size',
        'is_vucem_compliant',
        'was_converted',
        'uploaded_by',
        'file_content_base64',
        'mime_type',
    ];

    protected $casts = [
        'is_vucem_compliant' => 'boolean',
        'was_converted' => 'boolean',
        'file_size' => 'integer',
    ];

    /**
     * Obtener el contenido del archivo decodificado
     */
    public function getDecodedContent(): string
    {
        return base64_decode($this->file_content_base64);
    }
    
    /**
     * Establecer el contenido del archivo desde contenido binario
     */
    public function setContentFromBinary(string $binaryContent): void
    {
        $this->file_content_base64 = base64_encode($binaryContent);
    }

    // Relación con el solicitante
    public function applicant(): BelongsTo
    {
        return $this->belongsTo(MvClientApplicant::class, 'applicant_id');
    }

    // Encriptación automática para Documentos (JSON)
    protected function documentos(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? json_decode(Crypt::decryptString($value), true) : null,
            set: fn ($value) => ($value && !empty($value)) ? Crypt::encryptString(json_encode($value)) : null,
        );
    }
}
