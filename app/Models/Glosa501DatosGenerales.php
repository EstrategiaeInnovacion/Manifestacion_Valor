<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Glosa501DatosGenerales extends Model
{
    protected $table = 'glosa_501_datos_generales';

    protected $fillable = [
        'import_id',
        'admin_id',
        'clave_operacion',
        'patente',
        'pedimento',
        'seccion_aduanera',
        'tipo_operacion',
        'clave_documento',
        'seccion_aduanera_entrada',
        'curp_contribuyente',
        'rfc',
        'curp_agente',
        'tipo_cambio',
        'total_fletes',
        'total_seguros',
        'total_embalajes',
        'total_incrementables',
        'total_deducibles',
        'peso_bruto',
        'medio_transporte_salida',
        'medio_transporte_arribo',
        'medio_transporte_entrada_salida',
        'destino_mercancia',
        'nombre_contribuyente',
        'tipo_pedimento',
        'fecha_pago_real',
        'raw_data',
    ];

    protected $casts = [
        'fecha_pago_real'     => 'date',
        'tipo_cambio'         => 'decimal:4',
        'total_fletes'        => 'decimal:2',
        'total_seguros'       => 'decimal:2',
        'total_embalajes'     => 'decimal:2',
        'total_incrementables'=> 'decimal:2',
        'total_deducibles'    => 'decimal:2',
        'peso_bruto'          => 'decimal:3',
        'raw_data'            => 'array',
    ];

    public function import(): BelongsTo
    {
        return $this->belongsTo(GlosaImport::class, 'import_id');
    }
}
