<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SourceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class Source extends Model
{
    /** @use HasFactory<SourceFactory> */
    use HasFactory;

    protected $casts = [
        'id' => 'integer',
        'notion_database_id' => 'integer',
        'repository_id' => 'integer',
    ];

    /**
     * @return BelongsTo<Repository, $this>
     */
    public function repository(): BelongsTo
    {
        return $this->belongsTo(Repository::class);
    }

    /**
     * @return BelongsTo<NotionDatabase, $this>
     */
    public function notionDatabase(): BelongsTo
    {
        return $this->belongsTo(NotionDatabase::class);
    }

    /**
     * @return HasMany<Document, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /**
     * @return Collection<int, Document>
     */
    public function latestDocuments(): Collection
    {
        $firstResults = DB::table('documents')
            ->select('id', DB::raw('MAX(created_at)'))
            ->where('source_id', $this->id)
            ->groupBy('path')
            ->get();

        $docIDs = $firstResults->pluck('id')
            ->toArray();

        return Document::whereIn('id', $docIDs)->get();
    }
}
