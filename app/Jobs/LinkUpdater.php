<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Support\Facades\Log;

class LinkUpdater
{
    private string $content;

    public function __construct(string $content)
    {
        $this->content = $content;
    }

    public function update(): string
    {
        $this->removeInternal();

        return $this->content;
    }

    private function removeInternal(): void
    {
        // Regex, with group for text: \[([^\]]+)\]\(#[^\)]+\)
        $this->content = preg_replace('/\[([^\]]+)\]\(#[^\)]+\)/', '\'$1\'', $this->content, -1, $count);
        Log::info('Made {count} replacements', ['count' => $count]);
        // TODO:
    }
}
