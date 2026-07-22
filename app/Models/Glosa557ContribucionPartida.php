<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Glosa557ContribucionPartida extends Model
{
    protected $table = 'glosa_557_contribuciones_partida';

    protected $fillable = [
        'import_id',
        'admin_id',
        'clave_operacion',
        'secuencia',
        'clave_contribucion',
        'forma_pago',
        'importe',
        'fecha_pago_real',
        'raw_data',
    ];

    protected $casts = [
        'secuencia'       => 'integer',
        'fecha_pago_real' => 'date',
        'importe'         => 'decimal:2',
        'raw_data'        => 'array',
    ];

    public function import(): BelongsTo
    {
        return $this->belongsTo(GlosaImport::class, 'import_id');
    }
}
