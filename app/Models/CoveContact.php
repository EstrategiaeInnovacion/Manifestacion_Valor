<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoveContact extends Model
{
    protected $table = 'cove_contacts';

    protected $fillable = [
        'applicant_id',
        'type',
        'tipo_identificador',
        'tax_id',
        'razon_social',
        'calle',
        'num_exterior',
        'num_interior',
        'cp',
        'colonia',
        'localidad',
        'entidad_federativa',
        'municipio',
        'pais',
    ];

    // Relación con el cliente solicitante
    public function applicant(): BelongsTo
    {
        return $this->belongsTo(MvClientApplicant::class, 'applicant_id');
    }
}
