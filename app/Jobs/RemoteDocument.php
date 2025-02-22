<?php

declare(strict_types=1);

namespace App\Jobs;

readonly class RemoteDocument
{
    public function __construct(public string $path, public string $hash) {}
}
