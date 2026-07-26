<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Location extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'address', 'notes'];

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function consumables(): HasMany
    {
        return $this->hasMany(Consumable::class);
    }
}
