<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Glosa505Factura extends Model
{
    protected $table = 'glosa_505_facturas';

    protected $fillable = [
        'import_id',
        'admin_id',
        'clave_operacion',
        'numero_factura',
        'fecha_factura',
        'incoterm',
        'moneda',
        'valor_dolares',
        'valor_moneda_extranjera',
        'proveedor_nombre',
        'proveedor_tax_id',
        'raw_data',
    ];

    protected $casts = [
        'fecha_factura'           => 'date',
        'valor_dolares'           => 'decimal:2',
        'valor_moneda_extranjera' => 'decimal:2',
        'raw_data'                => 'array',
    ];

    public function import(): BelongsTo
    {
        return $this->belongsTo(GlosaImport::class, 'import_id');
    }
}
