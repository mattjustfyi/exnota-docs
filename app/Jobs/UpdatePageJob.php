<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Jobs\Notion\PageContentMutator;
use App\Models\Document;
use App\Models\SourceSync;
use Brd6\NotionSdkPhp\Client;
use Brd6\NotionSdkPhp\ClientOptions;
use Brd6\NotionSdkPhp\Exception\ApiResponseException;
use Brd6\NotionSdkPhp\Exception\HttpResponseException;
use Brd6\NotionSdkPhp\Exception\InvalidPaginationResponseException;
use Brd6\NotionSdkPhp\Exception\RequestTimeoutException;
use Brd6\NotionSdkPhp\Exception\UnsupportedPaginationResponseTypeException;
use Http\Client\Exception;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Symfony\Component\Process\Process;
use Webmozart\Assert\Assert;

class UpdatePageJob implements ShouldQueue
{
    use Batchable, Queueable;

    private Document $document;

    private SourceSync $syncSource;

    /**
     * Create a new job instance.
     */
    public function __construct(SourceSync $sourceSync, Document $document)
    {
        $this->syncSource = $sourceSync;
        $this->document = $document;
    }

    public function handle(): void
    {
        Log::info('UpdatePageJob started for document: ' . $this->document->id);
        if ($this->batch() !== null && $this->batch()->cancelled()) {
            Log::info('Batch cancelled. Failing job.');
            $this->fail('Batch cancelled');

            return;
        }

        // Get models
        $source = $this->document->source;
        $notionDatabase = $source?->notionDatabase;

        $token = $notionDatabase?->notionWorkspace?->token;
        $databaseId = $notionDatabase?->notion_id;

        if ($token === null) {
            Log::error('No token found for source ' . $source?->id);
            $this->release();

            return;
        }
        if ($databaseId === null) {
            Log::error('No database id found for source ' . $source?->id);
            $this->release();

            return;
        }

        // Create notion client
        $options = (new ClientOptions)
            ->setAuth($token);
        $notion = new Client($options);

        // Delete existing content
        $notionPageID = $this->document->notion_id;
        Assert::string($notionPageID);
        $pageContentMutator = new PageContentMutator(pageID: $notionPageID, notion: $notion);
        try {
            $pageContentMutator->deleteAll();
        } catch (ApiResponseException | HttpResponseException | InvalidPaginationResponseException | RequestTimeoutException | UnsupportedPaginationResponseTypeException | Exception $e) {
            Log::error('Error while deleting the page content', ['exception' => $e]);
        }

        // Apply fixes to content
        $linkUpdater = new LinkUpdater($this->document->content);
        $content = $linkUpdater->update();

        $tableFixer = new TableFixer($content);
        $content = $tableFixer->fix();

        // Append new content
        $filePath = $this->document->path;
        $debugNode = config('app.debug_node');
        $cmd = $debugNode
            ? 'node --inspect-brk ' . base_path('cmd/notion-export/update.js')
            : 'node ' . base_path('cmd/notion-export/update.js');
        $payload = json_encode([
            'token' => $token,
            'pageID' => $notionPageID,
            'content' => $content,
            'filePath' => $filePath,
        ]);
        if ($payload === false) {
            Log::error('Failed to encode payload');
            throw new RuntimeException('Failed to encode payload');
        }

        $process = Process::fromShellCommandline($cmd);
        $process->setInput($payload);
        $process->setTimeout($debugNode ? 600 : 120);
        $process->run(function ($type, $data) {
            if ($type === Process::ERR) {
                if (! is_string($data) || (
                    ! str_starts_with($data, 'Debugger listening on ') &&
                    ! str_starts_with($data, 'Debugger attached') &&
                    ! str_starts_with($data, 'Waiting for the debugger to disconnect')
                )) {
                    Log::error('Error running update.js', [
                        'data' => $data,
                        'sourceSyncId' => $this->syncSource->id,
                        'documentId' => $this->document->id,
                    ]);
                    throw new RuntimeException('Error running update.js');
                }
            }
            Assert::string($data);
            Log::info($data);
        });

        Log::info('UpdatePageJob handled for document: ' . $this->document->id);
    }
}
