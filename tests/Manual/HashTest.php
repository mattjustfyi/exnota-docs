<?php

declare(strict_types=1);

namespace Tests\Manual;

use App\Models\Document;
use Illuminate\Support\Facades\Log;
use Webmozart\Assert\Assert;

$path = 'tests/Data/Docs/OneLevel/Flutter-Self-Service-Index.md';

$size = filesize($path);

$document = Document::where('path', $path)->first();
Assert::isInstanceOf($document, Document::class);
$content = $document->content;

$shaContent = 'blob '.$size."\0".$content;

$sha = sha1($shaContent);
Log::info('sha', ['sha' => $sha]);
