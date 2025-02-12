<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Support\Facades\Log;
use Webmozart\Assert\Assert;

class LinkUpdater
{
    private string $content;

    public function __construct(string $content)
    {
        $this->content = $content;
    }

    public function update(): string
    {
        $this->removeSamePageAnchor();
        $this->removeRelativeNonNestedLinks();
        $this->removeNonHttpOrHttpsLinks();

        return $this->content;
    }

    private function removeSamePageAnchor(): void
    {
        $replaced = preg_replace('/\[([^]]+)]\(#[^)]+\)/', '\'$1\'', $this->content, -1, $count);
        Assert::string($replaced);
        $this->content = $replaced;
        Log::info('Made {count} replacements', ['count' => $count]);
    }

    private function removeRelativeNonNestedLinks(): void
    {
        // TODO: Include the path in brackets after
        $replaced = preg_replace('/\[([^]]+)]\(\.{1,2}\/[^)]+\)/', '\'$1\'', $this->content, -1, $count);
        Assert::string($replaced);
        $this->content = $replaced;
        Log::info('Made {count} replacements', ['count' => $count]);
    }

    private function removeNonHttpOrHttpsLinks(): void
    {
        $replaced = preg_replace('/\[([^]]+)]\(((?!https?:\/\/)[^)]+)\)/', '\'$1\' ($2)', $this->content, -1, $count);
        Assert::string($replaced);
        $this->content = $replaced;
        Log::info('Made {count} replacements', ['count' => $count]);
    }
}
