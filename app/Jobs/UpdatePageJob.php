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
use Throwable;
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

        // Create payload
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

        // Write payload to temporary file instead of piping directly.
        // Piping directly failed in production, for unknown reasons.
        $tempFile = tempnam(sys_get_temp_dir(), 'notion_payload_');
        if ($tempFile === false) {
            Log::error('Failed to create temporary file');
            throw new RuntimeException('Failed to create temporary file');
        }

        if (file_put_contents($tempFile, $payload) === false) {
            Log::error('Failed to write payload to temporary file');
            unlink($tempFile);
            throw new RuntimeException('Failed to write payload to temporary file');
        }

        // Modify command to read from file instead of stdin
        // Try to find node executable from different possible locations
        /** @var string[] $possibleNodePaths */
        $possibleNodePaths = [
            config('app.node_path'),
            '/usr/local/bin/node',
            '/usr/bin/node',
            // 2025-04-24: This finds node on manual EC2 install.
            '/home/exnota-docs/.nvm/versions/node/*/bin/node',
            '/opt/node/bin/node',
            // This is used locally
            'node',
        ];

        $nodePath = null;
        foreach ($possibleNodePaths as $path) {
            if (empty($path)) {
                continue;
            }

            // Handle wildcard paths (like NVM)
            if (str_contains($path, '*')) {
                $matchedPaths = glob($path);
                if (! empty($matchedPaths)) {
                    // Use the latest version if multiple matches

                    // Known phpstan issue: https://github.com/phpstan/phpstan/issues/6492
                    /* @phpstan-ignore argument.type */
                    usort($matchedPaths, 'version_compare');
                    $path = end($matchedPaths);
                } else {
                    continue;
                }
            }

            // Skip if not an absolute path unless it's the default 'node'
            if ($path !== 'node' && ! str_starts_with($path, '/')) {
                continue;
            }

            // Check if executable exists and is executable
            if ($path === 'node' || (file_exists($path) && is_executable($path))) {
                $nodePath = $path;
                Log::info('Using Node.js at: ' . $nodePath);
                break;
            }
        }

        if ($nodePath === null) {
            Log::error('Node.js not found in any expected location', [
                'PATH' => getenv('PATH'),
                'tried_paths' => $possibleNodePaths,
            ]);
            throw new RuntimeException('Node.js not found in any expected location');
        }

        // .js file paths need to be absolute in production, I guess because node is not running from the working dir.
        $cmd = $debugNode
            ? $nodePath . ' --inspect-brk ' . base_path('cmd/notion-export/update.js') . ' ' . escapeshellarg($tempFile)
            : $nodePath . ' ' . base_path('cmd/notion-export/update.js') . ' ' . escapeshellarg($tempFile);

        $process = Process::fromShellCommandline($cmd);
        $process->setTimeout($debugNode ? 600 : 120);

        try {
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
        } catch (Throwable $e) {
            Log::error('Exception in UpdatePageJob', [
                'message' => $e->getMessage(),
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'documentId' => $this->document->id,
                'syncSourceId' => $this->syncSource->id,
            ]);
            /** @noinspection PhpUnhandledExceptionInspection queue should handle */
            throw $e;
        } finally {
            // Clean up the temporary file
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }

        Log::info('UpdatePageJob handled for document: ' . $this->document->id);
    }
}
