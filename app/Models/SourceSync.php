<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SourceSync extends Model
{
    use HasFactory;

    protected $casts = [
        'id' => 'integer',
        'source_id' => 'integer',
        'file_paths' => 'array',
        'document_ids' => 'array',
    ];

    /**
     * @return BelongsTo<Source, $this>
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }
}
