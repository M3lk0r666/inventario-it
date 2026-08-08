<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reminder extends Model
{
    use SoftDeletes;

    public const RECURRENCES = [
        'none' => 'No se repite',
        'hourly' => 'Cada hora',
        'daily' => 'Cada día',
        'weekly' => 'Cada semana',
        'monthly' => 'Cada mes',
        'yearly' => 'Cada año',
    ];

    protected $fillable = [
        'title', 'body', 'starts_at', 'ends_at', 'visibility', 'recurrence', 'user_id',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    /** Avanza una fecha a la siguiente ocurrencia según la recurrencia. */
    public function advance(\Illuminate\Support\Carbon $date): \Illuminate\Support\Carbon
    {
        return match ($this->recurrence) {
            'hourly' => $date->copy()->addHour(),
            'daily' => $date->copy()->addDay(),
            'weekly' => $date->copy()->addWeek(),
            'monthly' => $date->copy()->addMonthNoOverflow(),
            'yearly' => $date->copy()->addYear(),
            default => $date,
        };
    }

    public function isRecurring(): bool
    {
        return $this->recurrence && $this->recurrence !== 'none';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Recordatorios visibles para un usuario: públicos o propios. */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->where(fn (Builder $q) => $q
            ->where('visibility', 'public')
            ->orWhere('user_id', $user->id));
    }
}
