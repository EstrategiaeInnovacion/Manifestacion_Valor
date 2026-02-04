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
        'applicant_rfc',
        'business_name',
        'main_economic_activity',
        'country',
        'postal_code',
        'state',
        'municipality',
        'locality',
        'neighborhood',
        'street',
        'exterior_number',
        'interior_number',
        'area_code',
        'phone',
        'ws_file_upload_key',
    ];

    /**
     * Los atributos que deben ser encriptados.
     * Laravel automáticamente encripta al guardar y desencripta al leer.
     */
    protected $casts = [
        'applicant_rfc' => 'encrypted',
        'business_name' => 'encrypted',
        'main_economic_activity' => 'encrypted',
        'country' => 'encrypted',
        'postal_code' => 'encrypted',
        'state' => 'encrypted',
        'municipality' => 'encrypted',
        'locality' => 'encrypted',
        'neighborhood' => 'encrypted',
        'street' => 'encrypted',
        'exterior_number' => 'encrypted',
        'interior_number' => 'encrypted',
        'area_code' => 'encrypted',
        'phone' => 'encrypted',
        //'ws_file_upload_key' => 'encrypted',
    ];

    /**
     * Relación: El solicitante pertenece a un Usuario (Representante).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_email', 'email');
    }
}