<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdditionalItemType extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'requires_value', 'value_label', 'is_active'];

    protected function casts(): array
    {
        return [
            'requires_value' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function letterItems(): HasMany
    {
        return $this->hasMany(LetterItem::class);
    }
}
