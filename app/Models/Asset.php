<?php

namespace App\Models;

use App\Models\Concerns\TracksActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model
{
    use HasFactory;
    use SoftDeletes;
    use TracksActivity;

    protected $fillable = [
        'asset_tag', 'name', 'asset_type_id', 'asset_model_id', 'serial_number',
        'asset_status_id', 'location_id', 'supplier_id', 'purchase_date',
        'purchase_cost', 'warranty_expires_at', 'specs', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'specs' => 'array',
            'purchase_date' => 'date',
            'warranty_expires_at' => 'date',
            'purchase_cost' => 'decimal:2',
        ];
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(AssetType::class, 'asset_type_id');
    }

    public function model(): BelongsTo
    {
        return $this->belongsTo(AssetModel::class, 'asset_model_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(AssetStatus::class, 'asset_status_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /** Histórico completo: por qué empleados ha pasado el activo. */
    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class)->orderByDesc('assigned_at');
    }

    /** Asignación vigente (null si está libre). */
    public function currentAssignment(): HasOne
    {
        return $this->hasOne(Assignment::class)->whereNull('returned_at')->latestOfMany('assigned_at');
    }

    public function problems(): HasMany
    {
        return $this->hasMany(Problem::class);
    }

    /** Licencias instaladas en este equipo. */
    public function licenseAssignments(): MorphMany
    {
        return $this->morphMany(LicenseAssignment::class, 'assignable');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /** Notas libres sobre el dispositivo. */
    public function deviceNotes(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable')->latest();
    }

    /** Costo acumulado de reparaciones. */
    public function repairCost(): float
    {
        return (float) $this->problems()->sum('cost');
    }
}
