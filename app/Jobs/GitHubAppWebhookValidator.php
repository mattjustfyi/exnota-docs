<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Http\Request;
use Spatie\WebhookClient\WebhookConfig;
use Spatie\WebhookClient\SignatureValidator\SignatureValidator;

class GitHubAppWebhookValidator implements SignatureValidator
{
    public function isValid(Request $request, WebhookConfig $config): bool
    {
        $signature = $request->header($config->signatureHeaderName);
        if (! $signature) {
            return false;
        }

        $signingSecret = $config->signingSecret;

        $computedSignature = 'sha256=' . hash_hmac('sha256', $request->getContent(), $signingSecret);

        return hash_equals($signature, $computedSignature);
    }
}
