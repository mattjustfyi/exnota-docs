<?php

namespace App\Jobs;

use Spatie\WebhookClient\Jobs\ProcessWebhookJob as SpatieProcessWebhookJob;

class GitPushWebhookJob extends SpatieProcessWebhookJob
{
    public function handle()
    {
        dd('GitPushWebhookJob');
        // $this->webhookCall // contains an instance of `WebhookCall`

        // perform the work here
    }
}