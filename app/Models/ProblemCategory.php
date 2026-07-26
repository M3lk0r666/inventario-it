<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProblemCategory extends Model
{
    use SoftDeletes;

    protected $fillable = ['name'];

    public function problems(): HasMany
    {
        return $this->hasMany(Problem::class);
    }
}
