<?php
declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Support\Facades\Log;
use Spatie\WebhookClient\Jobs\ProcessWebhookJob as SpatieProcessWebhookJob;

class GitPushWebhookJob extends SpatieProcessWebhookJob
{
    public $timeout = 600;

    public function handle(): void
    {
        Log::info('GitPushWebhookJob');
        $webhook = $this->webhookCall;
        $payload = $webhook->payload['payload'] ?? null;

        $parsedPayload = json_decode($payload, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Invalid JSON payload');

            return;
        }

        Log::info($parsedPayload);
    }
}
