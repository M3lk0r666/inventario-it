<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssetType extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'slug', 'icon', 'spec_fields'];

    protected function casts(): array
    {
        return ['spec_fields' => 'array'];
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    public function models(): HasMany
    {
        return $this->hasMany(AssetModel::class);
    }
}
