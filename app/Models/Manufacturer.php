<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Manufacturer extends Model
{
    use SoftDeletes;

    protected $fillable = ['name'];

    public function models(): HasMany
    {
        return $this->hasMany(AssetModel::class);
    }

    public function assets(): HasManyThrough
    {
        return $this->hasManyThrough(Asset::class, AssetModel::class);
    }
}
