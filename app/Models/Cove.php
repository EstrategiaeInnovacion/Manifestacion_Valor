<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cove extends Model
{
    protected $table = 'coves';

    protected $fillable = [
        'applicant_id',
        'factura_numero',
        'factura_fecha',
        'edocument',
        'numero_operacion',
        'intentos_consulta',
        'status',
        'cove_json',
        'xml_solicitud',
        'xml_respuesta',
        'error_mensaje',
    ];

    // Relación con el cliente solicitante
    public function applicant(): BelongsTo
    {
        return $this->belongsTo(MvClientApplicant::class, 'applicant_id');
    }

    // Encriptación/Desencriptación automática para cove_json (JSON)
    protected function coveJson(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? json_decode(Crypt::decryptString($value), true) : null,
            set: fn ($value) => ($value && !empty($value)) ? Crypt::encryptString(json_encode($value, JSON_INVALID_UTF8_SUBSTITUTE)) : null,
        );
    }
}
