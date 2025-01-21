<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotionDatabase extends Model
{
    use HasFactory;

    protected $casts = [
        'notion_workspace_id' => 'integer',
    ];

    public function notionWorkspace(): BelongsTo
    {
        return $this->belongsTo(NotionWorkspace::class);
    }
}
