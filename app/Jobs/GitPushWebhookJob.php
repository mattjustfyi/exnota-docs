<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Repository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Spatie\WebhookClient\Jobs\ProcessWebhookJob as SpatieProcessWebhookJob;
use Webmozart\Assert\Assert;

class GitPushWebhookJob extends SpatieProcessWebhookJob
{
    public int $timeout = 600;

    public function handle(): void
    {
        Log::info('GitPushWebhookJob');
        $webhook = $this->webhookCall;
        $payload = $webhook->payload['payload'] ?? null;
        Log::debug('Payload', [
            'payload' => $payload,
        ]);
        // In local testing, payload is already an array
        Assert::string($payload);
        $parsedPayload = json_decode($payload, true);

        Assert::isArray($parsedPayload, 'Payload must be an array after decoding');

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Invalid JSON payload');

            return;
        }

        // Ensure we have the necessary structure
        if (! isset($parsedPayload['repository']) || ! is_array($parsedPayload['repository'])) {
            Log::error('Missing or invalid repository information in payload');

            return;
        }

        if (! isset($parsedPayload['repository']['contents_url']) || ! is_string($parsedPayload['repository']['contents_url'])) {
            Log::error('Missing or invalid contents_url in repository payload');

            return;
        }

        $contentsUrl = str_replace('/{+path}', '', $parsedPayload['repository']['contents_url']);

        // Search for repository with matching URL
        $repositories = Repository::where('url', $contentsUrl)->get();

        // Error if no repository found
        if ($repositories->isEmpty()) {
            Log::error('No repository found with URL', ['url' => $contentsUrl]);
            throw new ModelNotFoundException("No repository found with URL: $contentsUrl");
        }

        // Error if more than one repository found
        if ($repositories->count() > 1) {
            Log::error('Multiple repositories found with the same URL', ['url' => $contentsUrl, 'count' => $repositories->count()]);
            throw new RuntimeException("Multiple repositories found with URL: $contentsUrl");
        }

        $repository = $repositories->first();

        //        TODO: Create a source sync for all sources associated with the repository
        // TODO: Only sync sources that have changed files.

        Log::info('GitPushWebhookJob completed', [
            'parsedPayload' => $parsedPayload,
            'repository' => $repository->id,
            'repository_url' => $repository->url,
        ]);
    }
}
