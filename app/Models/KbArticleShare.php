<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KbArticleShare extends Model
{
    protected $fillable = ['kb_article_id', 'user_id', 'recipients', 'message'];

    protected function casts(): array
    {
        return ['recipients' => 'array'];
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(KbArticle::class, 'kb_article_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
