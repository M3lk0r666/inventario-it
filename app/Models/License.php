<?php

namespace App\Models;

use App\Models\Concerns\TracksActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class License extends Model
{
    use HasFactory;
    use SoftDeletes;
    use TracksActivity;

    protected $fillable = [
        'software_name', 'version', 'license_type_id', 'supplier_id', 'seats',
        'product_key', 'purchase_date', 'cost', 'expires_at', 'notes',
        'renewal_date', 'alerts_enabled', 'alert_days_before',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'expires_at' => 'date',
            'renewal_date' => 'date',
            'cost' => 'decimal:2',
            'alerts_enabled' => 'boolean',
        ];
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(LicenseType::class, 'license_type_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(LicenseAssignment::class);
    }

    /** Asientos en uso (asignaciones sin liberar). */
    public function activeAssignments(): HasMany
    {
        return $this->hasMany(LicenseAssignment::class)->whereNull('released_at');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function availableSeats(): int
    {
        return $this->seats - $this->activeAssignments()->count();
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Estado de la alerta de renovación:
     *  none     = sin alerta (deshabilitada o sin fecha)
     *  overdue  = fecha de renovación ya pasó
     *  upcoming = dentro de la ventana de anticipación
     *  ok       = aún lejos
     */
    public function renewalStatus(): string
    {
        if (! $this->alerts_enabled || ! $this->renewal_date) {
            return 'none';
        }

        if ($this->renewal_date->isPast()) {
            return 'overdue';
        }

        return $this->renewal_date->lte(now()->addDays($this->alert_days_before ?? 30))
            ? 'upcoming'
            : 'ok';
    }

    public function needsRenewalAlert(): bool
    {
        return in_array($this->renewalStatus(), ['overdue', 'upcoming'], true);
    }

    /** Scope: licencias con alerta de renovación activa. */
    public function scopeNeedingRenewal($query)
    {
        return $query->where('alerts_enabled', true)
            ->whereNotNull('renewal_date')
            ->whereRaw('renewal_date <= DATE_ADD(CURDATE(), INTERVAL alert_days_before DAY)');
    }
}

