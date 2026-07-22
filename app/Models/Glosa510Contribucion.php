<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Glosa510Contribucion extends Model
{
    protected $table = 'glosa_510_contribuciones';

    protected $fillable = [
        'import_id',
        'admin_id',
        'clave_operacion',
        'clave_contribucion',
        'forma_pago',
        'importe',
        'fecha_pago_real',
        'raw_data',
    ];

    protected $casts = [
        'fecha_pago_real' => 'date',
        'importe'         => 'decimal:2',
        'raw_data'        => 'array',
    ];

    public function import(): BelongsTo
    {
        return $this->belongsTo(GlosaImport::class, 'import_id');
    }
}
