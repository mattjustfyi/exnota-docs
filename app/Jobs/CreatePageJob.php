<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Document;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CreatePageJob implements ShouldQueue
{
    use Batchable, Queueable;

    private Document $document;

    /**
     * Create a new job instance.
     */
    public function __construct(Document $document)
    {
        $this->document = $document;
    }

    public function handle(): void
    {
        // TODO: Uncomment when used as batch
        //        if ($this->batch()->cancelled()) {
        //            return;
        //        }

        // TODO:
        $source = $this->document->source;
        $repository = $source->repository;

        $token = $repository->token;
        $notionDatabase = $source->notionDatabase;

        if ($notionDatabase === null) {
            $this->fail();

            return;
        }

        $databaseId = $notionDatabase->notion_id;
        $content = $this->document->content;

        Log::info('CreatePageJob values', [
            'token' => $token,
            'databaseId' => $databaseId,
            'content' => $content,
        ]);
    }
}
