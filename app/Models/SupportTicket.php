<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    protected $fillable = [
        'user_id',
        'category',
        'subject',
        'description',
        'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportTicketMessage::class, 'ticket_id')->orderBy('created_at');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'open'        => 'Abierto',
            'in_progress' => 'En Proceso',
            'closed'      => 'Cerrado',
            'cancelled'   => 'Cancelado',
            default       => $this->status,
        };
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'open'        => 'amber',
            'in_progress' => 'blue',
            'closed'      => 'slate',
            'cancelled'   => 'red',
            default       => 'slate',
        };
    }

    public function canBeCancelledBy(\App\Models\User $user): bool
    {
        return $this->user_id === $user->id
            && ! in_array($this->status, ['closed', 'cancelled']);
    }
}
