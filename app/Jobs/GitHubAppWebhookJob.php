<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\WebhookEvent;
use Illuminate\Support\Facades\Log;
use Spatie\WebhookClient\Jobs\ProcessWebhookJob;

class GitHubAppWebhookJob extends ProcessWebhookJob
{
    public function handle(): void
    {
        $webhook = $this->webhookCall;

        // Create webhook event record
        WebhookEvent::create([
            'url' => $webhook->url,
            'headers' => $webhook->headers,
            'body' => $webhook->payload,
        ]);

        Log::info('GitHub App Webhook received', [
            'headers' => $webhook->headers,
            'payload' => $webhook->payload,
            'request_url' => $webhook->url,
            'github_event' => $webhook->headers['x-github-event'] ?? 'unknown',
            'github_delivery' => $webhook->headers['x-github-delivery'] ?? 'unknown',
        ]);
    }
}
