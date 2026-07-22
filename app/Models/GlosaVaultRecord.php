<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GlosaVaultRecord extends Model
{
    protected $table = 'glosa_vault_records';

    protected $fillable = [
        'import_id',
        'admin_id',
        'vault_code',
        'sheet_name',
        'clave_operacion',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function import(): BelongsTo
    {
        return $this->belongsTo(GlosaImport::class, 'import_id');
    }
}
