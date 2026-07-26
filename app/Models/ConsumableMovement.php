<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsumableMovement extends Model
{
    public const TYPES = [
        'in' => 'Entrada',
        'out' => 'Salida',
    ];

    protected $fillable = [
        'consumable_id', 'type', 'quantity', 'employee_id', 'user_id',
        'unit_cost', 'moved_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'moved_at' => 'datetime',
            'unit_cost' => 'decimal:2',
        ];
    }

    public function consumable(): BelongsTo
    {
        return $this->belongsTo(Consumable::class);
    }

    /** Destinatario de la salida. */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /** Usuario que registró el movimiento. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
