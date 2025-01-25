<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotionWorkspace extends Model
{
    use HasFactory;

    protected $casts = [
        'id' => 'integer',
    ];

    /**
     * @return HasMany<NotionDatabase, $this>
     */
    public function notionDatabases(): HasMany
    {
        return $this->hasMany(NotionDatabase::class);
    }
}
