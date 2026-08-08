<?php

namespace App\Models;

use App\Models\Concerns\TracksActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Assignment extends Model
{
    use SoftDeletes;
    use TracksActivity;

    public const TYPES = [
        'permanent' => 'Definitiva',
        'loan' => 'Préstamo temporal',
    ];

    protected $fillable = [
        'asset_id', 'employee_id', 'responsive_letter_id', 'return_letter_id',
        'assigned_at', 'returned_at', 'condition_on_assign', 'condition_on_return',
        'assignment_type', 'expected_return_at',
        'assigned_by', 'received_by', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'date',
            'returned_at' => 'date',
            'expected_return_at' => 'date',
        ];
    }

    public function isLoan(): bool
    {
        return $this->assignment_type === 'loan';
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function responsiveLetter(): BelongsTo
    {
        return $this->belongsTo(ResponsiveLetter::class);
    }

    public function returnLetter(): BelongsTo
    {
        return $this->belongsTo(ResponsiveLetter::class, 'return_letter_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function isActive(): bool
    {
        return is_null($this->returned_at);
    }
}
