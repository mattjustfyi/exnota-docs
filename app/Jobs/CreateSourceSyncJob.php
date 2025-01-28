<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Document;
use App\Models\Repository;
use App\Models\Source;
use App\Models\SourceSync;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class CreateSourceSyncJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    private SourceSync $sourceSync;

    /**
     * Create a new job instance.
     */
    public function __construct(Source $source)
    {
        $this->sourceSync = SourceSync::create([
            'source_id' => $source->id,
        ]);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Fetch and save file paths
        /** @var Source $source */
        $source = $this->sourceSync->source;
        /** @var Repository $repository */
        $repository = $source->repository;
        try {
            $filePaths = $this->getFilesInDir($repository->url, $source->path, 2);
        } catch (GitHubException|ConnectionException) {
            $this->release();

            return;
        }
        Log::info('filePaths', ['filePaths' => $filePaths]);

        $this->sourceSync->update([
            'file_paths' => $filePaths,
        ]);

        // Create save file job for each path
        $saveFileJobs = array_map(function ($filePath) {
            return new SaveFileJob($this->sourceSync, $filePath);
        }, $filePaths);

        $sourceSync = $this->sourceSync;
        $pendingSaveFileBatch = Bus::batch($saveFileJobs)->before(function () {
            Log::info('SaveFileJob batch started');
        })->progress(function () {
            Log::info('SaveFileJob batch progress');
        })->then(function () use ($sourceSync) {
            Log::info('SaveFileJob batch finished (then)');
            // Can't reference $this as this code is serialized into a job in the database
            // so call a static function.
            CreateSourceSyncJob::createCreatePageJobs($sourceSync->id);
        })->catch(function () {
            Log::error('SaveFileJob batch failed');
        })->finally(function () {
            Log::info('SaveFileJob batch finished (finally)');
        });

        /** @noinspection PhpUnhandledExceptionInspection */
        $batch = $pendingSaveFileBatch->dispatch();

        Log::info('Dispatched batch ID: '.$batch->id);
    }

    /**
     * @param  string[]  $filePaths
     * @return string[]
     *
     * @throws ConnectionException
     * @throws GitHubException
     */
    private function getFilesInDir(string $base, string $dirPath, int $depth, array $filePaths = []): array
    {
        $source = $this->sourceSync->source;
        $githubToken = $source?->repository?->token; // Replace this with your actual GitHub token
        if (! is_string($githubToken)) {
            throw new GitHubException('GitHub token not set');
        }

        $response = Http::withHeaders([
            'Accept' => 'application/vnd.github+json',
            'Authorization' => 'Bearer '.$githubToken,
            'X-GitHub-Api-Version' => '2022-11-28',
        ])->get($base.$dirPath);

        if (! $response->successful()) {
            Log::error('Failed to get files in dir: '.$dirPath);
            throw new GitHubException($response->reason());
        }

        /** @var array<string, array{type:string, path:string}> $data */
        $data = $response->json();

        $subDirPaths = [];
        foreach ($data as $item) {
            if ($item['type'] === 'dir') {
                $subDirPaths[] = $item['path'];
            } elseif ($item['type'] === 'file') {
                $filePaths[] = $item['path'];
            } else {
                Log::error('Unknown item type: '.$item['type']);
                throw new GitHubException('Unknown item type: '.$item['type']);
            }
        }

        if ($depth === 0) {
            return $filePaths;
        }

        foreach ($subDirPaths as $subDirPath) {
            $filePaths = $this->getFilesInDir($base, '/'.$subDirPath, $depth - 1, $filePaths);
        }

        return $filePaths;
    }

    /**
     * @throws NoDocumentsException
     * @throws Throwable
     */
    private static function createCreatePageJobs(int $sourceSyncId): void
    {
        /** @var SourceSync $sourceSync */
        $sourceSync = SourceSync::findOrFail($sourceSyncId);
        /** @var int[] $documentIds */
        $documentIds = $sourceSync->document_ids;

        if (count($documentIds) === 0) {
            Log::error('No document ids');
            throw new NoDocumentsException($sourceSync->id);
        }

        $createPageJobs = array_map(function ($document_id) use ($sourceSync) {
            /** @var Document $document */
            $document = Document::findOrFail($document_id);

            return new CreatePageJob($sourceSync, $document);
        }, $documentIds);

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

        $pendingBatch->dispatch();
    }
}
