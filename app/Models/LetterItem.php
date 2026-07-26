<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LetterItem extends Model
{
    protected $fillable = ['responsive_letter_id', 'additional_item_type_id', 'value'];

    public function letter(): BelongsTo
    {
        return $this->belongsTo(ResponsiveLetter::class, 'responsive_letter_id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(AdditionalItemType::class, 'additional_item_type_id');
    }
}
