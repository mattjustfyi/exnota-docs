<?php

declare(strict_types=1);

namespace App\Jobs\PageCreator;

use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Spatie\LaravelData\Exceptions\CannotCreateData;

class MessageFactory
{
    public static function create(string $data): ?PageCreatedMessage
    {
        try {
            return PageCreatedMessage::from($data);
        } catch (CannotCreateData) {
            // Not valid JSON
            return null;
        } catch (ValidationException $exception) {
            // Valid JSON but invalid properties
            Log::error('Error validating message', ['exception' => $exception]);
            throw $exception;
        }
    }
}
