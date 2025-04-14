<?php

declare(strict_types=1);

// Dispatches an UpdateNotionHome job

use App\Models\Source;
use App\Models\SourceSync;
use Illuminate\Database\Eloquent\Collection;
use Webmozart\Assert\Assert;

// Option 1: Create new source sync
// $source = Source::find(2);
// $documents = $source->documents;
// Assert::isInstanceOf($documents, Collection::class);
// $documentIDs = array_map(fn ($document) => $document->id, $documents->all());
// $sourceSync = SourceSync::factory()->create(['source_id' => $source->id, 'document_ids' => $documentIDs]);

// Option 2: Use existing source sync
$sourceSync = SourceSync::find(1);

// Dispatch
App\Jobs\UpdateNotionHomeJob::dispatch($sourceSync, $sourceSync->source->notionDatabase, true /* forceUpdate */);
