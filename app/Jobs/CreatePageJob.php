<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Jobs\PageCreator\MessageFactory;
use App\Models\Document;
use App\Models\SourceSync;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Symfony\Component\Process\Process;
use Webmozart\Assert\Assert;

class CreatePageJob implements ShouldQueue
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
        Log::info('CreatePageJob started for document: '.$this->document->id);
        if ($this->batch() !== null && $this->batch()->cancelled()) {
            Log::info('Batch cancelled. Failing job.');
            $this->fail('Batch cancelled');

            return;
        }

        $source = $this->document->source;
        $notionDatabase = $source?->notionDatabase;

        $token = $notionDatabase?->notionWorkspace?->token;
        $databaseId = $notionDatabase?->notion_id;

        if ($token === null) {
            Log::error('No token found for source '.$source?->id);
            $this->release();

            return;
        }
        if ($databaseId === null) {
            Log::error('No database id found for source '.$source?->id);
            $this->release();

            return;
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
            ? 'node --inspect-brk cmd/notion-export/create.js'
            : 'node cmd/notion-export/create.js';
        $payload = json_encode([
            'token' => $token,
            'databaseId' => $databaseId,
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
                    Log::error('Error running create.js', [
                        'data' => $data,
                        'sourceSyncId' => $this->syncSource->id,
                        'documentId' => $this->document->id,
                    ]);
                    throw new RuntimeException('Error running create.js');
                }
            }
            Assert::string($data);
            $this->parseStdout($data);
        });

        Log::info('CreatePageJob handled for document: '.$this->document->id);
    }

    public function parseStdout(string $stdout): void
    {
        $lines = explode("\n", $stdout);
        foreach ($lines as $line) {
            $message = MessageFactory::create($line);
            if (is_null($message)) {
                Log::info('Received log from create.js', ['message' => $line]);

                continue;
            }

            $this->document->update([
                'notion_id' => $message->notionPageID,
                'notion_url' => $message->notionURL,
            ]);

            Log::info('Saved Notion page ID and URL');
        }
    }
}
