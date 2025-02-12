<?php

declare(strict_types=1);

// Dispatches an UpdateNotionHome job

use App\Jobs\CreatePageJob;
use App\Models\Document;
use App\Models\Source;
use App\Models\SourceSync;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Webmozart\Assert\Assert;

// Data created in DatabaseSeeder.php
Log::info('Starting CreatePageTest');
$source = Source::find(2);
Assert::notNull($source);
$documents = $source->documents;
Assert::isInstanceOf($documents, Collection::class);
$documentIDs = array_map(fn ($document) => $document->id, $documents->all());
$sourceSync = SourceSync::factory()->create(['source_id' => $source->id, 'document_ids' => $documentIDs]);

$createPageJobs = array_map(function ($document_id) use ($sourceSync) {
    /** @var Document $document */
    $document = Document::findOrFail($document_id);

    return new CreatePageJob($sourceSync, $document);
}, $documentIDs);

$pendingBatch = Bus::batch($createPageJobs)->before(function () {
    Log::info('CreatePageJob batch started');
})->progress(function () {
    Log::info('CreatePageJob batch progress');
})->then(function () {
    Log::info('CreatePageJob batch finished (then)');
})->catch(function () {
    Log::error('CreatePageJob batch failed');
})->finally(function () {
    Log::info('CreatePageJob batch finished (finally)');
});

/** @noinspection PhpUnhandledExceptionInspection */
$pendingBatch->dispatch();

Log::info('Dispatched batch for CreatePageTest');
