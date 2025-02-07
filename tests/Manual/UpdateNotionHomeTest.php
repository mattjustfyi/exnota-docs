<?php

declare(strict_types=1);

// Dispatches an UpdateNotionHome job

use App\Models\Source;
use App\Models\SourceSync;
use Illuminate\Database\Eloquent\Collection;
use Webmozart\Assert\Assert;

$sourcePath = base_path('tests/Data/Docs/OneLevel');
echo '$path='.$sourcePath;
$files = \Illuminate\Support\Facades\File::allFiles($sourcePath);

$projectRelativePath = base_path();

$relativePaths = array_map(fn ($file) => str_replace($projectRelativePath, '', $file->getPathname()), $files);

$source = Source::find(1);
$documents = $source->documents;
Assert::count($documents, 30);
Assert::isInstanceOf($documents, Collection::class);
$documentIDs = array_map(fn ($document) => $document->id, $documents->all());
$sourceSync = SourceSync::factory()->create(['source_id' => $source->id, 'document_ids' => $documentIDs]);
App\Jobs\UpdateNotionHomeJob::dispatch($sourceSync, $sourceSync->source->notionDatabase);
