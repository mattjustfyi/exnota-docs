<?php

declare(strict_types=1);

// Dispatches an UpdateNotionHome job

use App\Jobs\UpdatePageJob;
use App\Models\Document;
use App\Models\Source;
use App\Models\SourceSync;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Webmozart\Assert\Assert;

// Data created in DatabaseSeeder.php
Log::info('Starting UpdatePageTest');
$source = Source::find(2);
Assert::notNull($source);
$documents = $source->documents;
Assert::isInstanceOf($documents, Collection::class);
$documentIDs = array_map(fn ($document) => $document->id, $documents->all());
$sourceSync = SourceSync::factory()->create(['source_id' => $source->id, 'document_ids' => $documentIDs]);

$updatePageJobs = array_map(function ($document_id) use ($sourceSync) {
    $document = Document::findOrFail($document_id);
    Assert::isInstanceOf($document, Document::class);

    return new UpdatePageJob($sourceSync, $document);
}, $documentIDs);

$pendingBatch = Bus::batch($updatePageJobs)->before(function () {
    Log::info('UpdatePageJob batch started');
})->progress(function () {
    Log::info('UpdatePageJob batch progress');
})->then(function () {
    Log::info('UpdatePageJob batch finished (then)');
})->catch(function () {
    Log::error('UpdatePageJob batch failed');
})->finally(function () {
    Log::info('UpdatePageJob batch finished (finally)');
});

/** @noinspection PhpUnhandledExceptionInspection */
$pendingBatch->dispatch();

Log::info('Dispatched batch for UpdatePageTest');
