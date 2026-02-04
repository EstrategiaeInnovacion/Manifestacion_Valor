<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MvDatosManifestacion extends Model
{
    protected $table = 'mv_datos_manifestacion';
    
    protected $fillable = [
        'applicant_id',
        'status',
        'rfc_importador',
        'metodo_valoracion',
        'existe_vinculacion',
        'pedimento',
        'patente',
        'aduana',
        'persona_consulta',
    ];

    // Relación con el solicitante
    public function applicant(): BelongsTo
    {
        return $this->belongsTo(MvClientApplicant::class, 'applicant_id');
    }

    // Relación con la información COVE
    public function informacionCove(): HasOne
    {
        return $this->hasOne(MvInformacionCove::class, 'applicant_id', 'applicant_id');
    }

    // Relación con los documentos
    public function documentos(): HasOne
    {
        return $this->hasOne(MvDocumentos::class, 'applicant_id', 'applicant_id');
    }

    // Encriptación automática para RFC Importador
    protected function rfcImportador(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Crypt::decryptString($value) : null,
            set: fn ($value) => $value ? Crypt::encryptString(strtoupper($value)) : null,
        );
    }

    // Encriptación automática para Método de Valoración
    protected function metodoValoracion(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Crypt::decryptString($value) : null,
            set: fn ($value) => ($value && $value !== '') ? Crypt::encryptString($value) : null,
        );
    }

    // Encriptación automática para Existe Vinculación
    protected function existeVinculacion(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Crypt::decryptString($value) : null,
            set: fn ($value) => ($value && $value !== '') ? Crypt::encryptString($value) : null,
        );
    }

    // Encriptación automática para Pedimento
    protected function pedimento(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Crypt::decryptString($value) : null,
            set: fn ($value) => ($value && $value !== '') ? Crypt::encryptString(strtoupper($value)) : null,
        );
    }

    // Encriptación automática para Patente
    protected function patente(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Crypt::decryptString($value) : null,
            set: fn ($value) => ($value && $value !== '') ? Crypt::encryptString(strtoupper($value)) : null,
        );
    }

    // Encriptación automática para Aduana
    protected function aduana(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Crypt::decryptString($value) : null,
            set: fn ($value) => ($value && $value !== '') ? Crypt::encryptString(strtoupper($value)) : null,
        );
    }

    // Encriptación automática para Persona Consulta (JSON)
    protected function personaConsulta(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? json_decode(Crypt::decryptString($value), true) : null,
            set: fn ($value) => ($value && !empty($value)) ? Crypt::encryptString(json_encode($value)) : null,
        );
    }
}
