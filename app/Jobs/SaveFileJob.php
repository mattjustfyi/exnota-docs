<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Document;
use App\Models\SourceSync;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;
use Webmozart\Assert\Assert;

class SaveFileJob implements ShouldQueue
{
    use Batchable, Queueable;

    public function __construct(private readonly SourceSync $sourceSync, private readonly string $filePath, private readonly string $fileSHA) {}

    /**
     * Execute the job.
     *
     * @throws ConnectionException|Throwable
     */
    public function handle(): void
    {
        if ($this->batch()->cancelled()) {
            $this->fail('Batch cancelled');

            return;
        }

        // Get file download link
        $source = $this->sourceSync->source;
        Assert::notNull($source);
        $repository = $source->repository;
        Assert::notNull($repository);
        $githubToken = $repository->token; // Replace this with your actual GitHub token
        $path = $repository->url.'/'.$this->filePath;
        $response = Http::withHeaders([
            'Accept' => 'application/vnd.github+json',
            'Authorization' => 'Bearer '.$githubToken,
            'X-GitHub-Api-Version' => '2022-11-28',
        ])->get($path);

        if (! $response->successful()) {
            Log::error('Failed to get file: '.$path);
            $this->release();

            return;
        }

        $json = $response->json();
        Assert::isArray($json);
        $downloadUrl = $json['download_url'];
        Assert::string($downloadUrl);
        Log::info('download url:'.$downloadUrl);

        // Download file
        $content = file_get_contents($downloadUrl);
        Assert::string($content);

        // Save to document and update source sync
        $document = DB::transaction(function () use ($source, $content) {
            $doc = Document::create([
                'path' => $this->filePath,
                'content' => $content,
                'hash' => $this->fileSHA,
                'source_id' => $source->id,
            ]);

            $documentIds = $this->sourceSync->document_ids;
            $documentIds[] = $doc->id;
            $this->sourceSync->update([
                'document_ids' => $documentIds,
            ]);

            return $doc;
        });

        Log::info('Document created', [
            'id' => $document->id,
            'source_id' => $document->source_id,
            'path' => $document->path,
        ]);
    }
}
