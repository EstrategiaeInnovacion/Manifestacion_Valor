<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * Email del SuperAdmin protegido (no se puede eliminar)
     */
    public const PROTECTED_SUPERADMIN_EMAIL = 'guillermo.aguilera@estrategiaeinnovacion.com.mx';

    /**
     * Los atributos que se pueden asignar masivamente.
     */
    protected $fillable = [
        'full_name',
        'email',
        'username',
        'password',
        'role',
        'company',
        'max_users',
        'max_applicants',
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
        'max_users' => 'integer',
        'max_applicants' => 'integer',
    ];

    /**
     * Relación: Un usuario (Representante Legal) puede tener varios RFCs asociados.
     */
    public function clientApplicants(): HasMany
    {
        return $this->hasMany(MvClientApplicant::class, 'user_email', 'email');
    }

    /**
     * Relación: Solicitantes asignados a este usuario.
     */
    public function assignedApplicants(): HasMany
    {
        return $this->hasMany(MvClientApplicant::class, 'assigned_user_id');
    }

    /**
     * Relación: Solicitantes creados por este usuario (Admin).
     */
    public function createdApplicants(): HasMany
    {
        return $this->hasMany(MvClientApplicant::class, 'created_by_user_id');
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

    /**
     * Relación: Todas las licencias asignadas a este admin
     */
    public function licenses(): HasMany
    {
        return $this->hasMany(License::class, 'admin_id');
    }

    /**
     * Relación: Licencia activa actual de este admin
     */
    public function activeLicense(): HasOne
    {
        return $this->hasOne(License::class, 'admin_id')
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->latest('expires_at');
    }

    /**
     * Verificar si este usuario (Admin) tiene una licencia activa.
     * SuperAdmin siempre retorna true.
     * Usuario regular hereda la licencia de su Admin creador.
     */
    public function hasActiveLicense(): bool
    {
        if ($this->role === 'SuperAdmin') {
            return true;
        }

        if ($this->role === 'Admin') {
            return $this->activeLicense()->exists();
        }

        // Usuario regular: verificar la licencia de su admin creador
        if ($this->created_by) {
            $admin = User::find($this->created_by);
            if ($admin) {
                return $admin->hasActiveLicense();
            }
        }

        return false;
    }

    /**
     * Obtener la licencia activa del admin asociado (para usuarios regulares).
     */
    public function getEffectiveLicense(): ?License
    {
        if ($this->role === 'SuperAdmin') {
            return null;
        }

        if ($this->role === 'Admin') {
            return $this->activeLicense;
        }

        // Usuario regular
        if ($this->created_by) {
            $admin = User::find($this->created_by);
            if ($admin) {
                return $admin->activeLicense;
            }
        }

        return null;
    }

    /**
     * Obtener el admin asociado a este usuario (para herencia de licencia).
     */
    public function getAdminOwner(): ?User
    {
        if ($this->role === 'Admin') {
            return $this;
        }

        if ($this->created_by) {
            return User::find($this->created_by);
        }

        return null;
    }

    /**
     * Obtener el email "dueño" de los solicitantes para este usuario.
     * - Admin: su propio email (él registra los solicitantes)
     * - Usuario: el email de su Admin (comparten los mismos solicitantes)
     * - SuperAdmin: null (no tiene acceso a solicitantes)
     */
    public function getApplicantOwnerEmail(): ?string
    {
        if ($this->role === 'SuperAdmin') {
            return null;
        }

        if ($this->role === 'Admin') {
            return $this->email;
        }

        // Usuario: usar el email de su Admin
        $admin = $this->getAdminOwner();
        return $admin ? $admin->email : $this->email;
    }

    /**
     * Verificar si este usuario puede ser eliminado.
     * El SuperAdmin del seeder está protegido.
     */
    public function canBeDeleted(): bool
    {
        return $this->email !== self::PROTECTED_SUPERADMIN_EMAIL;
    }

    /**
     * Verificar si este usuario es el SuperAdmin protegido.
     */
    public function isProtectedSuperAdmin(): bool
    {
        return $this->email === self::PROTECTED_SUPERADMIN_EMAIL;
    }
}