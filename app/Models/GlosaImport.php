<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GlosaImport extends Model
{
    protected $table = 'glosa_imports';

    protected $fillable = [
        'user_id',
        'admin_id',
        'original_filename',
        'folio',
        'rfc',
        'fecha_inicial',
        'fecha_final',
        'total_files',
        'total_pedimentos',
        'total_partidas',
        'total_valor_dolares',
        'total_contribuciones',
        'status',
        'error_message',
    ];

    protected $casts = [
        'fecha_inicial'        => 'date',
        'fecha_final'          => 'date',
        'total_files'          => 'integer',
        'total_pedimentos'     => 'integer',
        'total_partidas'       => 'integer',
        'total_valor_dolares'  => 'decimal:2',
        'total_contribuciones' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function datosGenerales501(): HasMany
    {
        return $this->hasMany(Glosa501DatosGenerales::class, 'import_id');
    }

    public function facturas505(): HasMany
    {
        return $this->hasMany(Glosa505Factura::class, 'import_id');
    }

    public function contribuciones510(): HasMany
    {
        return $this->hasMany(Glosa510Contribucion::class, 'import_id');
    }

    public function partidas551(): HasMany
    {
        return $this->hasMany(Glosa551Partida::class, 'import_id');
    }

    public function contribucionesPartida557(): HasMany
    {
        return $this->hasMany(Glosa557ContribucionPartida::class, 'import_id');
    }

    public function rectificaciones701(): HasMany
    {
        return $this->hasMany(Glosa701Rectificacion::class, 'import_id');
    }

    public function vaultRecords(): HasMany
    {
        return $this->hasMany(GlosaVaultRecord::class, 'import_id');
    }
}
