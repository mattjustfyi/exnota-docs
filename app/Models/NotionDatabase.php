<?php

namespace App\Models;

use Database\Factories\NotionDatabaseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotionDatabase extends Model
{
    /** @use HasFactory<NotionDatabaseFactory> */
    use HasFactory;

    protected $casts = [
        'id' => 'integer',
        'notion_workspace_id' => 'integer',
    ];

    /**
     * @return BelongsTo<NotionWorkspace, $this>
     */
    public function notionWorkspace(): BelongsTo
    {
        return $this->belongsTo(NotionWorkspace::class);
    }
}
