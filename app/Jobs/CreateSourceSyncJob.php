<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Document;
use App\Models\NotionDatabase;
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
use Webmozart\Assert\Assert;

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
            $allRemoteDocuments = $this->getFilesInDir($repository->url, $source->path, 2);
        } catch (GitHubException|ConnectionException) {
            $this->release();

            return;
        }
        Log::info('Got remote documents', ['remoteDocuments' => $allRemoteDocuments]);

        $changedRemoteDocuments = array_filter(array: $allRemoteDocuments, callback: function ($remoteDocument) use ($source) {
            $existingDocument = Document::where('path', $remoteDocument->path)
                ->where('source_id', $source->id)
                ->orderBy('created_at', 'desc')
                ->first();
            if ($existingDocument === null) {
                return true;
            }
            if ($existingDocument->notion_id === null) {
                return true;
            }

            return $existingDocument->hash !== $remoteDocument->hash;
        });

        $this->sourceSync->update([
            'file_paths' => array_map(function ($remoteDocument) {
                return $remoteDocument->path;
            }, $changedRemoteDocuments),
        ]);

        // Create save file job for each path
        $saveFileJobs = array_map(function ($remoteDocument) {
            return new SaveFileJob(sourceSync: $this->sourceSync, filePath: $remoteDocument->path, fileSHA: $remoteDocument->hash);
        }, $changedRemoteDocuments);

        $sourceSync = $this->sourceSync;
        $pendingSaveFileBatch = Bus::batch($saveFileJobs)->before(function () {
            Log::info('SaveFileJob batch started');
        })->progress(function () {
            Log::info('SaveFileJob batch progress');
        })->then(function () use ($sourceSync) {
            Log::info('SaveFileJob batch finished (then)');
            // Can't reference $this as this code is serialized into a job in the database
            // so call a static function.
            CreateSourceSyncJob::dispatchUpdateNotionJobs($sourceSync->id);
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
     * @param  RemoteDocument[]  $remoteDocuments
     * @return RemoteDocument[]
     *
     * @throws ConnectionException
     * @throws GitHubException
     */
    private function getFilesInDir(string $base, string $dirPath, int $depth, array $remoteDocuments = []): array
    {
        $source = $this->sourceSync->source;
        Assert::isInstanceOf($source, Source::class);
        $repository = $source->repository;
        Assert::isInstanceOf($repository, Repository::class);
        $githubToken = $repository->token; // Replace this with your actual GitHub token

        $response = Http::withHeaders([
            'Accept' => 'application/vnd.github+json',
            'Authorization' => 'Bearer '.$githubToken,
            'X-GitHub-Api-Version' => '2022-11-28',
        ])->get($base.$dirPath);

        if (! $response->successful()) {
            Log::error('Failed to get files in dir: '.$dirPath);
            throw new GitHubException($response->reason());
        }

        /** @var array<string, array{type:string, path:string, sha:string}> $data */
        $data = $response->json();

        $subDirPaths = [];
        foreach ($data as $item) {
            if ($item['type'] === 'dir') {
                $subDirPaths[] = $item['path'];
            } elseif ($item['type'] === 'file') {
                $remoteDocuments[] = new RemoteDocument(path: $item['path'], hash: $item['sha']);
            } else {
                Log::error('Unknown item type: '.$item['type']);
                throw new GitHubException('Unknown item type: '.$item['type']);
            }
        }

        if ($depth === 0) {
            return $remoteDocuments;
        }

        foreach ($subDirPaths as $subDirPath) {
            $remoteDocuments = $this->getFilesInDir($base, '/'.$subDirPath, $depth - 1, $remoteDocuments);
        }

        return $remoteDocuments;
    }

    /**
     * @throws NoDocumentsException
     * @throws Throwable
     */
    private static function dispatchUpdateNotionJobs(int $sourceSyncId): void
    {
        /** @var SourceSync $sourceSync */
        $sourceSync = SourceSync::findOrFail($sourceSyncId);
        /** @var int[] $documentIds */
        $documentIds = $sourceSync->document_ids;

        if (count($documentIds) === 0) {
            Log::error('No document ids');
            throw new NoDocumentsException($sourceSync->id);
        }

        $jobs = array_map(function ($document_id) use ($sourceSync) {
            /** @var Document $document */
            $document = Document::findOrFail($document_id);
            $sourceId = $sourceSync->source_id;

            // Create an update job if document already synced to Notion
            $previousDocument = Document::where('source_id', $sourceId)
                ->where('path', $document->path)
                ->whereNot('id', $document->id)
                ->orderBy('created_at', 'desc')->first();
            if ($previousDocument !== null && $previousDocument->notion_id != null) {
                $document->update([
                    'notion_id' => $previousDocument->notion_id,
                    'notion_url' => $previousDocument->notion_url,
                ]);

                Log::info('Creating an update page job for document', [
                    'document_id' => $document->id,
                    'source_id' => $sourceId,
                    'source_sync_id' => $sourceSync->id,
                    'notion_id' => $document->notion_id,
                ]);

                return new UpdatePageJob($sourceSync, $document);
            }

            // Create a create job if first time syncing
            Log::info('Creating a create page job for document', [
                'document_id' => $document->id,
                'source_id' => $sourceId,
                'source_sync_id' => $sourceSync->id,
            ]);

            return new CreatePageJob($sourceSync, $document);
        }, $documentIds);

        $updatePagesBatch = Bus::batch($jobs)->before(function () {
            Log::info('Update Notion Page batch started');
        })->progress(function () {
            Log::info('Update Notion Page batch progress');
        })->then(function () {
            Log::info('Update Notion Page batch finished (then)');
        })->catch(function () {
            Log::error('Update Notion Page batch errored');
        })->finally(function () {
            Log::info('Update Notion Page batch finished (finally)');
        });

        $notionDatabase = $sourceSync->source?->notionDatabase;
        Assert::isInstanceOf($notionDatabase, NotionDatabase::class);

        Bus::chain([
            $updatePagesBatch,
            new UpdateNotionHomeJob(sourceSync: $sourceSync, database: $notionDatabase),
        ])
            ->catch(function (Throwable $e) {
                Log::error('Update Notion chain errored', ['exception' => $e]);
            })
            ->dispatch();
    }
}
