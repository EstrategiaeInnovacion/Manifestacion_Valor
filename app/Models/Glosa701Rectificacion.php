<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Glosa701Rectificacion extends Model
{
    protected $table = 'glosa_701_rectificaciones';

    protected $fillable = [
        'import_id',
        'admin_id',
        'clave_operacion',
        'clave_operacion_original',
        'pedimento_anterior',
        'patente_anterior',
        'seccion_anterior',
        'pedimento_original',
        'patente_original',
        'seccion_original',
        'fecha_pago_rectificacion',
        'fecha_pago_original',
        'raw_data',
    ];

    protected $casts = [
        'fecha_pago_rectificacion' => 'date',
        'fecha_pago_original'      => 'date',
        'raw_data'                 => 'array',
    ];

    public function import(): BelongsTo
    {
        return $this->belongsTo(GlosaImport::class, 'import_id');
    }
}
