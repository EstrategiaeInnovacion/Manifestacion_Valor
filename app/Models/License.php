<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Carbon\Carbon;

class License extends Model
{
    protected $fillable = [
        'license_key',
        'admin_id',
        'duration_type',
        'starts_at',
        'expires_at',
        'status',
        'expiry_notified',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'starts_at'  => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Duraciones válidas con su descripción y minutos
     */
    public const DURATIONS = [
        '1min'    => ['label' => '1 Minuto (Prueba)',  'minutes' => 1],
        '1month'  => ['label' => '1 Mes',              'minutes' => 43200],      // 30 días
        '6months' => ['label' => '6 Meses',            'minutes' => 262800],     // 182.5 días
        '1year'   => ['label' => '1 Año',              'minutes' => 525600],     // 365 días
    ];

    /**
     * Admin al que pertenece la licencia
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * SuperAdmin que creó la licencia
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Generar clave de licencia única
     */
    public static function generateKey(): string
    {
        do {
            // Formato: FILE-XXXX-XXXX-XXXX
            $key = 'FILE-' . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4));
        } while (self::where('license_key', $key)->exists());

        return $key;
    }

    /**
     * Verificar si la licencia está activa (no expirada ni revocada)
     */
    public function isActive(): bool
    {
        if ($this->status === 'revoked') {
            return false;
        }

        if ($this->expires_at->isPast()) {
            // Auto-marcar como expirada
            if ($this->status !== 'expired') {
                $this->update(['status' => 'expired']);
            }
            return false;
        }

        return $this->status === 'active';
    }

    /**
     * Tiempo restante legible
     */
    public function timeRemaining(): string
    {
        if (!$this->isActive()) {
            return 'Expirada';
        }

        return $this->expires_at->diffForHumans(now(), ['syntax' => Carbon::DIFF_ABSOLUTE]);
    }

    /**
     * Calcular fecha de expiración a partir de la duración
     */
    public static function calculateExpiration(string $durationType, ?Carbon $startDate = null): Carbon
    {
        $start = $startDate ?? now();
        $minutes = self::DURATIONS[$durationType]['minutes'] ?? 43200;

        return $start->copy()->addMinutes($minutes);
    }

    /**
     * Scope: solo licencias activas
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')->where('expires_at', '>', now());
    }

    /**
     * Scope: licencias de un admin específico
     */
    public function scopeForAdmin($query, int $adminId)
    {
        return $query->where('admin_id', $adminId);
    }
}
