<?php

namespace App\Models;

use App\Models\Concerns\TracksActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes;
    use TracksActivity;

    protected $fillable = [
        'name', 'rfc', 'contact_name', 'email', 'phone', 'website', 'address', 'notes',
    ];

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    public function licenses(): HasMany
    {
        return $this->hasMany(License::class);
    }
}
