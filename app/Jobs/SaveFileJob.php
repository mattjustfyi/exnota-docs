<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Document;
use App\Models\Source;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SaveFileJob implements ShouldQueue
{
    use Batchable, Queueable;

    private Source $source;

    private string $filePath;

    /**
     * Create a new job instance.
     */
    public function __construct(Source $source, string $filePath)
    {
        $this->source = $source;
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
            return;
        }

        $githubToken = $this->source->repository->token; // Replace this with your actual GitHub token

        $path = $this->source->repository->url.'/'.$this->filePath;

        $response = Http::withHeaders([
            'Accept' => 'application/vnd.github+json',
            'Authorization' => 'Bearer '.$githubToken,
            'X-GitHub-Api-Version' => '2022-11-28',
        ])->get($path);

        if (! $response->successful()) {
            Log::error('Failed to get file: '.$path);
            $this->fail();
        }

        $json = $response->json();
        $downloadUrl = $json['download_url'];
        Log::info('file contents:'.$downloadUrl);

        $content = file_get_contents($downloadUrl);
        Log::info('file contents:'.$content);

        $document = Document::create([
            'path' => $this->filePath,
            'content' => $content,
            'source_id' => $this->source->id,
        ]);

        Log::info('Document created', ['document' => $document]);
    }
}
