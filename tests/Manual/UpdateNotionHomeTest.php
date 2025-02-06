<?php

declare(strict_types=1);

// Dispatches an UpdateNotionHome job

use App\Models\Source;
use App\Models\SourceSync;

$sourcePath = base_path('tests/Data/Docs/OneLevel');
echo '$path='.$sourcePath;
$files = \Illuminate\Support\Facades\File::allFiles($sourcePath);

$projectRelativePath = base_path();

$relativePaths = array_map(fn ($file) => str_replace($projectRelativePath, '', $file->getPathname()), $files);

foreach ($relativePaths as $relativePath) {
    echo $relativePath."\n";
}

$source = Source::find(1);
$sourceSync = SourceSync::factory()->create(['source_id' => $source->id, 'document_ids' => [1, 2]]);
App\Jobs\UpdateNotionHomeJob::dispatch($sourceSync, $sourceSync->source->notionDatabase);
