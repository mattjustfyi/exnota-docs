<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Document;
use App\Models\SourceSync;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Symfony\Component\Process\Process;

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
        if ($this->batch() !== null && $this->batch()->cancelled()) {
            return;
        }

        Log::info('CreatePageJob handled for document: '.$this->document->id);

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

        $filePath = $this->document->path;
        $content = $this->document->content;

        Log::info('CreatePageJob values', [
            'token' => $token,
            'databaseId' => $databaseId,
            'content' => $content,
            'filePath' => $filePath,
        ]);

        $cmd = 'node cmd/notion-export/create.js';
        $payload = json_encode([
            'token' => $token,
            'databaseId' => $databaseId,
            'content' => $content,
            'filePath' => $filePath,
        ]);
        if ($payload === false) {
            Log::error('Failed to encode payload');
            $this->release();
            throw new RuntimeException('Failed to encode payload');
        }

        $process = Process::fromShellCommandline($cmd);
        $process->setInput($payload);
        $process->run(function ($type, $data) {
            if ($type === Process::ERR) {
                Log::error('Error running create.js', [
                    'data' => $data,
                    'sourceSyncId' => $this->syncSource->id,
                    'documentId' => $this->document->id,
                ]);
                $this->release();
                throw new RuntimeException('Error running create.js');
            }
            Log::info('Received log from create.js', ['data' => $data]);
        });

        Log::info('CreatePageJob handled for document: '.$this->document->id);
    }
}
