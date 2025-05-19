<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookEvent extends Model
{
    /** @use HasFactory<\Database\Factories\WebhookEventFactory> */
    use HasFactory;

    protected $fillable = [
        'url',
        'headers',
        'body',
    ];

    protected $casts = [
        'headers' => 'array',
        'body' => 'array',
    ];
}
