<?php

declare(strict_types=1);

namespace App\Jobs;

readonly class File
{
    public function __construct(
        public string $name,
        public string $notionID,
        public string $notionURL) {}
}
