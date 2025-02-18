<?php

declare(strict_types=1);

namespace App\Jobs\PageCreator;

readonly class PageCreatedMessage
{
    public string $notionPageId;

    public function __construct(string $notionPageId)
    {
        $this->notionPageId = $notionPageId;
    }
}
