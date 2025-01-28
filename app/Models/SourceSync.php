<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SourceSyncFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

/**
 * @property int[] $document_ids
 */
class SourceSync extends Model
{
    /** @use HasFactory<SourceSyncFactory> */
    use HasFactory;

    protected $casts = [
        'id' => 'integer',
        'source_id' => 'integer',
        'file_paths' => 'array',
    ];

    /**
     * @return BelongsTo<Source, $this>
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }

    /**
     * @return Attribute<int[], int[]>
     */
    protected function documentIds(): Attribute
    {
        return Attribute::make(
            get: function ($json) {
                // Ensure the input is a string; otherwise return an empty array.
                if (! is_string($json) || trim($json) === '') {
                    return [];
                }

                // Decode the JSON string into a PHP array.
                $decoded = json_decode($json, true);

                // Ensure the result is a valid array.
                if (! is_array($decoded)) {
                    return []; // Or throw an exception if invalid JSON is critical.
                }

                return $decoded;
            },
            set: function ($value) {
                // Ensure the value is an array of integers.
                if (! is_array($value) || array_filter($value, 'is_int') !== $value) {
                    throw new InvalidArgumentException('The document_ids attribute must be an array of integers.');
                }

                // Serialize the array to JSON and return it.
                return json_encode($value);
            }
        );
    }
}
