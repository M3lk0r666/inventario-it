<?php

namespace App\Models;

use App\Models\Concerns\TracksActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ResponsiveLetter extends Model
{
    use SoftDeletes;
    use TracksActivity;

    public const STATUSES = [
        'generated' => 'Generada',
        'signed' => 'Firmada',
        'cancelled' => 'Anulada',
    ];

    public const TYPES = [
        'delivery' => 'Entrega',
        'return' => 'Recepción',
    ];

    protected $fillable = [
        'folio', 'type', 'employee_id', 'issued_at', 'pdf_path', 'status',
        'signed_document_path', 'signed_at', 'signed_by', 'created_by', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'date',
            'signed_at' => 'datetime',
        ];
    }

    public function signedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signed_by');
    }

    public function hasSignedDocument(): bool
    {
        return filled($this->signed_document_path);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /** Asignaciones amparadas por esta carta de entrega. */
    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    /** Asignaciones cuya devolución ampara esta carta de recepción. */
    public function returnedAssignments(): HasMany
    {
        return $this->hasMany(Assignment::class, 'return_letter_id');
    }

    /** Bienes adicionales (llaves, controles, accesos...) de la carta. */
    public function items(): HasMany
    {
        return $this->hasMany(LetterItem::class);
    }

    public function isReturn(): bool
    {
        return $this->type === 'return';
    }

    /** Activos listados en la carta según su tipo. */
    public function documentAssignments(): HasMany
    {
        return $this->isReturn() ? $this->returnedAssignments() : $this->assignments();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
