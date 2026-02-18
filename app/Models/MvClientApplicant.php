<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MvClientApplicant extends Model
{
    use HasFactory;

    protected $table = 'mv_client_applicants';

    protected $fillable = [
        'user_email',
        'assigned_user_id',
        'created_by_user_id',
        'applicant_rfc',
        'business_name',
        'applicant_email',
        'vucem_key_file',
        'vucem_cert_file',
        'vucem_password',
        'vucem_webservice_key',
        'privacy_consent',
        'privacy_consent_at',
    ];

    /**
     * Los atributos que deben ser encriptados.
     * Laravel automáticamente encripta al guardar y desencripta al leer.
     * Los archivos .key y .cert se guardan como base64 encriptado.
     * La contraseña y clave de webservice también se encriptan.
     */
    protected $casts = [
        'applicant_rfc' => 'encrypted',
        'business_name' => 'encrypted',
        'applicant_email' => 'encrypted',
        'vucem_key_file' => 'encrypted',
        'vucem_cert_file' => 'encrypted',
        'vucem_password' => 'encrypted',
        'vucem_webservice_key' => 'encrypted',
        'privacy_consent' => 'boolean',
        'privacy_consent_at' => 'datetime',
    ];

    /**
     * Atributos ocultos en serialización (seguridad).
     */
    protected $hidden = [
        'vucem_key_file',
        'vucem_cert_file',
        'vucem_password',
        'vucem_webservice_key',
    ];

    /**
     * Verifica si el solicitante tiene credenciales VUCEM configuradas.
     */
    public function hasVucemCredentials(): bool
    {
        return !empty($this->vucem_key_file) 
            && !empty($this->vucem_cert_file) 
            && !empty($this->vucem_password);
    }

    /**
     * Verifica si tiene clave de webservice configurada.
     */
    public function hasWebserviceKey(): bool
    {
        return !empty($this->vucem_webservice_key);
    }

    /**
     * Relación: El solicitante pertenece a un Usuario (Representante).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_email', 'email');
    }

    /**
     * Relación: Usuario al que está asignado este solicitante.
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    /**
     * Relación: Usuario (Admin) que creó este solicitante.
     */
    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}