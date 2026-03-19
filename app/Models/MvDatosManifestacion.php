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
        'created_by_user_id',
        'folio_interno',
        'status',
        'rfc_importador',
        'metodo_valoracion',
        'existe_vinculacion',
        'pedimento',
        'patente',
        'aduana',
        'persona_consulta',
    ];

    /**
     * Generar folio_interno unico automaticamente al crear un nuevo registro.
     * Formato: MVE-YYYY-NNNNN (e.g. MVE-2026-00042)
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model) {
            if (empty($model->folio_interno)) {
                $year = date('Y');
                // Usar MAX en lugar de COUNT para evitar colisiones cuando existen
                // registros eliminados o intentos fallidos previos que ya consumieron un número
                $maxFolio = self::whereYear('created_at', $year)
                    ->where('folio_interno', 'like', 'MVE-' . $year . '-%')
                    ->max('folio_interno');
                if ($maxFolio) {
                    $lastNum = (int) substr($maxFolio, strrpos($maxFolio, '-') + 1);
                    $next = $lastNum + 1;
                } else {
                    $next = 1;
                }
                $model->folio_interno = 'MVE-' . $year . '-' . str_pad($next, 5, '0', STR_PAD_LEFT);
            }
        });
    }

    // Relacion con el solicitante
    public function applicant(): BelongsTo
    {
        return $this->belongsTo(MvClientApplicant::class, 'applicant_id');
    }

    // Relacion con el usuario que creó la manifestación
    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by_user_id');
    }

    // Relacion con la informacion COVE
    public function informacionCove(): HasOne
    {
        return $this->hasOne(MvInformacionCove::class, 'datos_manifestacion_id');
    }

    // Relacion con los documentos
    public function documentos(): HasOne
    {
        return $this->hasOne(MvDocumentos::class, 'datos_manifestacion_id');
    }

    // Encriptacion automatica para RFC Importador
    protected function rfcImportador(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Crypt::decryptString($value) : null,
            set: fn ($value) => $value ? Crypt::encryptString(strtoupper($value)) : null,
        );
    }

    // Encriptacion automatica para Metodo de Valoracion
    protected function metodoValoracion(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Crypt::decryptString($value) : null,
            set: fn ($value) => ($value && $value !== '') ? Crypt::encryptString($value) : null,
        );
    }

    // Encriptacion automatica para Existe Vinculacion
    protected function existeVinculacion(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Crypt::decryptString($value) : null,
            set: fn ($value) => ($value && $value !== '') ? Crypt::encryptString($value) : null,
        );
    }

    // Encriptacion automatica para Pedimento
    protected function pedimento(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Crypt::decryptString($value) : null,
            set: fn ($value) => ($value && $value !== '') ? Crypt::encryptString(strtoupper($value)) : null,
        );
    }

    // Encriptacion automatica para Patente
    protected function patente(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Crypt::decryptString($value) : null,
            set: fn ($value) => ($value && $value !== '') ? Crypt::encryptString(strtoupper($value)) : null,
        );
    }

    // Encriptacion automatica para Aduana
    protected function aduana(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Crypt::decryptString($value) : null,
            set: fn ($value) => ($value && $value !== '') ? Crypt::encryptString(strtoupper($value)) : null,
        );
    }

    // Encriptacion automatica para Persona Consulta (JSON)
    protected function personaConsulta(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? json_decode(Crypt::decryptString($value), true) : null,
            set: fn ($value) => ($value && !empty($value)) ? Crypt::encryptString(json_encode($value)) : null,
        );
    }
}