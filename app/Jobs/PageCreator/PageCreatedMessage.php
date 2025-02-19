<?php

declare(strict_types=1);

namespace App\Jobs\PageCreator;

use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Data;

class PageCreatedMessage extends Data
{
    public function __construct(
        #[In(['page-created'])]
        public string $type,
        public string $notionPageID,
        public string $notionURL,
    ) {}
}
