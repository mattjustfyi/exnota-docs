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
use Throwable;
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
        Log::info('CreatePageJob started for document: ' . $this->document->id);
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
            Log::error('No token found for source ' . $source?->id);
            $this->release();

            return;
        }
        if ($databaseId === null) {
            Log::error('No database id found for source ' . $source?->id);
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

        // Create payload
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

        // Write payload to temporary file instead of piping directly
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
        $possibleNodePaths = [
            env('NODE_PATH'), // First try environment variable if set
            '/usr/local/bin/node',
            '/usr/bin/node',
            '/home/exnota-docs/.nvm/versions/node/*/bin/node', // NVM installations
            '/opt/node/bin/node',
            'node', // Last resort, try PATH
        ];

        $nodePath = null;
        foreach ($possibleNodePaths as $path) {
            if (empty($path)) {
                continue;
            }

            // Handle wildcard paths (like NVM)
            if (strpos($path, '*') !== false) {
                $matchedPaths = glob($path);
                if (!empty($matchedPaths)) {
                    // Use the latest version if multiple matches
                    usort($matchedPaths, 'version_compare');
                    $path = end($matchedPaths);
                } else {
                    continue;
                }
            }

            // Skip if not an absolute path unless it's the default 'node'
            if ($path !== 'node' && !str_starts_with($path, '/')) {
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
                'tried_paths' => $possibleNodePaths
            ]);
            throw new RuntimeException('Node.js not found in any expected location');
        }

        $cmd = $debugNode
            ? $nodePath . ' --inspect-brk cmd/notion-export/create.js ' . escapeshellarg($tempFile)
            : $nodePath . ' cmd/notion-export/create.js ' . escapeshellarg($tempFile);

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
        } catch (Throwable $e) {
            Log::error('Exception in CreatePageJob', [
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

        Log::info('CreatePageJob handled for document: ' . $this->document->id);
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
