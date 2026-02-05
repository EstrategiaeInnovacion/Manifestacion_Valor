<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * Los atributos que se pueden asignar masivamente.
     */
    protected $fillable = [
        'full_name',
        'email',
        'username',
        'password',
        'role',
        'created_by',
        'rfc',
    ];

    /**
     * Los atributos que deben estar ocultos para la serialización.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Castings para encriptación automática
     */
    protected $casts = [
        'rfc' => 'encrypted',
    ];

    /**
     * Relación: Un usuario (Representante Legal) puede tener varios RFCs asociados.
     * Se utiliza 'user_email' en la tabla de destino y 'email' en esta tabla.
     */
    public function clientApplicants(): HasMany
    {
        return $this->hasMany(MvClientApplicant::class, 'user_email', 'email');
    }
    
    /**
     * Relación: Usuarios creados por este usuario (Admin/SuperAdmin).
     */
    public function createdUsers(): HasMany
    {
        return $this->hasMany(User::class, 'created_by', 'id');
    }
    
    /**
     * Relación: Usuario que creó este usuario.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

}