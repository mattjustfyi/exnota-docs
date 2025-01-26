<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Document;
use App\Models\SourceSync;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

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

        // TODO: Re-enable

        //        $source = $this->document->source;
        //        $repository = $source->repository;
        //
        //        $token = $repository->token;
        //        $notionDatabase = $source->notionDatabase;
        //
        //        if ($notionDatabase === null) {
        //            $this->release();
        //
        //            return;
        //        }
        //
        //        $databaseId = $notionDatabase->notion_id;
        //        $content = $this->document->content;
        //
        //        Log::info('CreatePageJob values', [
        //            'token' => $token,
        //            'databaseId' => $databaseId,
        //            'content' => $content,
        //        ]);
        //
        //        $contentEncoded = str_replace('"', '\"', $content);
        //        $cmd = 'node cmd/notion-export/create.js --page '.$databaseId.' --content "'.$contentEncoded.'" --token '.$token;
        //        Process::fromShellCommandline($cmd)->run(function ($type, $data) {
        //            if ($type === Process::ERR) {
        //                Log::error($data);
        //                $this->release();
        //
        //                return;
        //            }
        //            Log::info($data);
        //        });
    }
}
