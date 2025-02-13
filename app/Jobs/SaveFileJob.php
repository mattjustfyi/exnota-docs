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

class SaveFileJob implements ShouldQueue
{
    use Batchable, Queueable;

    private SourceSync $sourceSync;

    private string $filePath;

    public function __construct(SourceSync $sourceSync, string $filePath)
    {
        $this->sourceSync = $sourceSync;
        $this->filePath = $filePath;
    }

    /**
     * Execute the job.
     *
     * @throws ConnectionException
     */
    public function handle(): void
    {
        if ($this->batch()->cancelled()) {
            $this->fail('Batch cancelled');

            return;
        }

        // Get file download link
        $source = $this->sourceSync->source;
        $githubToken = $source->repository->token; // Replace this with your actual GitHub token
        $path = $source->repository->url.'/'.$this->filePath;
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
        $downloadUrl = $json['download_url'];
        Log::info('file contents:'.$downloadUrl);

        // Download file
        $content = file_get_contents($downloadUrl);
        Log::info('file contents:'.$content);

        // Save to document and update source sync
        $document = DB::transaction(function () use ($source, $content) {
            $doc = Document::create([
                'path' => $this->filePath,
                'content' => $content,
                'source_id' => $source->id,
            ]);

            $documentIds = $this->sourceSync->document_ids;
            $documentIds[] = $doc->id;
            $this->sourceSync->update([
                'document_ids' => $documentIds,
            ]);

            return $doc;
        });

        Log::info('Document created', ['document' => $document]);
    }
}
