<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Glosa551Partida extends Model
{
    protected $table = 'glosa_551_partidas';

    protected $fillable = [
        'import_id',
        'admin_id',
        'clave_operacion',
        'secuencia',
        'fraccion_arancelaria',
        'subdivision',
        'descripcion_mercancia',
        'precio_unitario',
        'valor_aduana',
        'valor_comercial',
        'valor_dolares',
        'cantidad_umc',
        'umc',
        'cantidad_umt',
        'umt',
        'pais_origen_destino',
        'fecha_pago_real',
        'raw_data',
    ];

    protected $casts = [
        'secuencia'       => 'integer',
        'fecha_pago_real' => 'date',
        'precio_unitario' => 'decimal:4',
        'valor_aduana'    => 'decimal:2',
        'valor_comercial' => 'decimal:2',
        'valor_dolares'   => 'decimal:2',
        'cantidad_umc'    => 'decimal:3',
        'cantidad_umt'    => 'decimal:3',
        'raw_data'        => 'array',
    ];

    public function import(): BelongsTo
    {
        return $this->belongsTo(GlosaImport::class, 'import_id');
    }
}
