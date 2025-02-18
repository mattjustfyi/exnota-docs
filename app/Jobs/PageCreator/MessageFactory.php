<?php

declare(strict_types=1);

namespace App\Jobs\PageCreator;

use Illuminate\Support\Facades\Log;
use JsonException;
use Throwable;
use ValueError;
use Webmozart\Assert\Assert;

class MessageFactory
{
    /**
     * @throws JsonException
     */
    public static function create(string $data): ?PageCreatedMessage
    {
        try {
            $parsed = json_decode($data, associative: true, flags: JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            Log::error($exception->getMessage());
            throw $exception;
        }

        if (is_null($parsed)) {
            return null;
        }

        Assert::isArray($parsed);

        $type = $parsed['type'];
        if (is_null($type)) {
            return null;
        }

        Assert::string($type);

        switch ($type) {
            case 'page-created':
                $payload = $parsed['payload'];
                Assert::isArray($payload);

                $notionPageId = $payload['notionPageID'];
                Assert::string($notionPageId);

                return new PageCreatedMessage($notionPageId);

            default:
                throw new ValueError('Unsupported message type: '.$type);
        }
    }
}
