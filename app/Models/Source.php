<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Source extends Model
{
    use HasFactory;

    protected $casts = [
        'id' => 'integer',
        'notion_database_id' => 'integer',
        'repository_id' => 'integer',
    ];

    public function repository(): BelongsTo
    {
        return $this->belongsTo(Repository::class);
    }

    public function notionDatabase(): BelongsTo
    {
        return $this->belongsTo(NotionDatabase::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }
}
