<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Source;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GetFilesJob implements ShouldQueue
{
    use Queueable;

    private Source $source;

    private int $timeout = 600;

    /**
     * Create a new job instance.
     */
    public function __construct(Source $source)
    {
        $this->source = $source;
    }

    /**
     * Execute the job.
     *
     * @throws ConnectionException
     */
    public function handle(): void
    {
        $filePaths = $this->getFilesInDir($this->source->repository->url, $this->source->path, 2);
        Log::info('filePaths', $filePaths);
    }

    /**
     * @param  string[]  $filePaths
     *
     * @throws ConnectionException
     */
    private function getFilesInDir(string $base, string $dirPath, int $depth, array $filePaths = []): array
    {
        $githubToken = $this->source->repository->token; // Replace this with your actual GitHub token

        $response = Http::withHeaders([
            'Accept' => 'application/vnd.github+json',
            'Authorization' => 'Bearer '.$githubToken,
            'X-GitHub-Api-Version' => '2022-11-28',
        ])->get($base.$dirPath);

        if (! $response->successful()) {
            Log::error('Failed to get files in dir: '.$dirPath);
            $this->fail();
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
                $this->fail();
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
