<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class LicenseAssignment extends Model
{
    protected $fillable = [
        'license_id', 'assignable_type', 'assignable_id', 'assigned_at',
        'released_at', 'assigned_by', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'date',
            'released_at' => 'date',
        ];
    }

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }

    /** Asset o Employee. */
    public function assignable(): MorphTo
    {
        return $this->morphTo();
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
