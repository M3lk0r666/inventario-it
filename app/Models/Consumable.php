<?php

namespace App\Models;

use App\Models\Concerns\TracksActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Consumable extends Model
{
    use HasFactory;
    use SoftDeletes;
    use TracksActivity;

    protected $fillable = [
        'name', 'description', 'stock', 'min_stock', 'unit', 'location_id', 'supplier_id',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(ConsumableMovement::class)->orderByDesc('moved_at');
    }

    public function isLowStock(): bool
    {
        return $this->stock <= $this->min_stock;
    }
}
