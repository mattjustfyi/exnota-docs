<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Source;
use App\Models\SourceSync;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GetFilesJob implements ShouldQueue
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
        $source = $this->sourceSync->source;
        try {
            $filePaths = $this->getFilesInDir($source->repository->url, $source->path, 2);
        } catch (GitHubException|ConnectionException) {
            $this->release();

            return;
        }
        Log::info('filePaths', $filePaths);

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

            // TODO: Create a batch of CreatePageJobs, one for each $this->sourceSync->document_ids value
            // and pass to the job the document and source sync
            $documentIds = $sourceSync->document_ids;
            Log::info('documentIds', [
                'documentIds' => $documentIds,
            ]);

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
     *
     * @throws ConnectionException
     * @throws GitHubException
     */
    private function getFilesInDir(string $base, string $dirPath, int $depth, array $filePaths = []): array
    {
        $source = $this->sourceSync->source;
        $githubToken = $source->repository->token; // Replace this with your actual GitHub token

        $response = Http::withHeaders([
            'Accept' => 'application/vnd.github+json',
            'Authorization' => 'Bearer '.$githubToken,
            'X-GitHub-Api-Version' => '2022-11-28',
        ])->get($base.$dirPath);

        if (! $response->successful()) {
            Log::error('Failed to get files in dir: '.$dirPath);
            throw new GitHubException($response->reason());
        }

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
}
