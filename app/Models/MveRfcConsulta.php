<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Crypt;

class MveRfcConsulta extends Model
{
    protected $table = 'mve_rfc_consulta';
    
    protected $fillable = [
        'applicant_rfc',
        'rfc_consulta',
        'razon_social',
        'tipo_figura',
    ];

    // Encriptación automática para RFC de consulta
    protected function rfcConsulta(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Crypt::decryptString($value) : null,
            set: fn ($value) => $value ? Crypt::encryptString(strtoupper($value)) : null,
        );
    }

    // Encriptación automática para razón social
    protected function razonSocial(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Crypt::decryptString($value) : null,
            set: fn ($value) => $value ? Crypt::encryptString(strtoupper($value)) : null,
        );
    }

    // Encriptación automática para tipo figura
    protected function tipoFigura(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Crypt::decryptString($value) : null,
            set: fn ($value) => $value ? Crypt::encryptString($value) : null,
        );
    }
}
