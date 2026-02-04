<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MvInformacionCove extends Model
{
    protected $table = 'mv_informacion_cove';
    
    protected $fillable = [
        'applicant_id',
        'status',
        'informacion_cove',
        'pedimentos',
        'precio_pagado',
        'precio_por_pagar',
        'compenso_pago',
        'incrementables',
        'decrementables',
        'valor_en_aduana',
    ];

    // Relación con el solicitante
    public function applicant(): BelongsTo
    {
        return $this->belongsTo(MvClientApplicant::class, 'applicant_id');
    }

    // Encriptación automática para Información COVE (JSON)
    protected function informacionCove(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? json_decode(Crypt::decryptString($value), true) : null,
            set: fn ($value) => ($value && !empty($value)) ? Crypt::encryptString(json_encode($value)) : null,
        );
    }

    // Encriptación automática para Pedimentos (JSON)
    protected function pedimentos(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? json_decode(Crypt::decryptString($value), true) : null,
            set: fn ($value) => ($value && !empty($value)) ? Crypt::encryptString(json_encode($value)) : null,
        );
    }

    // Encriptación automática para Precio Pagado (JSON)
    protected function precioPagado(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? json_decode(Crypt::decryptString($value), true) : null,
            set: fn ($value) => ($value && !empty($value)) ? Crypt::encryptString(json_encode($value)) : null,
        );
    }

    // Encriptación automática para Precio Por Pagar (JSON)
    protected function precioPorPagar(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? json_decode(Crypt::decryptString($value), true) : null,
            set: fn ($value) => ($value && !empty($value)) ? Crypt::encryptString(json_encode($value)) : null,
        );
    }

    // Encriptación automática para Compenso Pago (JSON)
    protected function compensoPago(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? json_decode(Crypt::decryptString($value), true) : null,
            set: fn ($value) => ($value && !empty($value)) ? Crypt::encryptString(json_encode($value)) : null,
        );
    }

    // Encriptación automática para Incrementables (JSON)
    protected function incrementables(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? json_decode(Crypt::decryptString($value), true) : null,
            set: fn ($value) => ($value && !empty($value)) ? Crypt::encryptString(json_encode($value)) : null,
        );
    }

    // Encriptación automática para Decrementables (JSON)
    protected function decrementables(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? json_decode(Crypt::decryptString($value), true) : null,
            set: fn ($value) => ($value && !empty($value)) ? Crypt::encryptString(json_encode($value)) : null,
        );
    }

    // Encriptación automática para Valor en Aduana (JSON)
    protected function valorEnAduana(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? json_decode(Crypt::decryptString($value), true) : null,
            set: fn ($value) => ($value && !empty($value)) ? Crypt::encryptString(json_encode($value)) : null,
        );
    }
}
