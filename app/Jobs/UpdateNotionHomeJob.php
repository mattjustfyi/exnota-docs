<?php

namespace App\Jobs;

use App\Models\NotionDatabase;
use App\Models\SourceSync;
use Brd6\NotionSdkPhp\Client;
use Brd6\NotionSdkPhp\ClientOptions;
use Brd6\NotionSdkPhp\Exception\ApiResponseException;
use Brd6\NotionSdkPhp\Exception\HttpResponseException;
use Brd6\NotionSdkPhp\Exception\InvalidPaginationResponseException;
use Brd6\NotionSdkPhp\Exception\InvalidResourceException;
use Brd6\NotionSdkPhp\Exception\InvalidResourceTypeException;
use Brd6\NotionSdkPhp\Exception\RequestTimeoutException;
use Brd6\NotionSdkPhp\Exception\UnsupportedPaginationResponseTypeException;
use Brd6\NotionSdkPhp\Exception\UnsupportedUserTypeException;
use Brd6\NotionSdkPhp\Resource\Block\AbstractBlock;
use Brd6\NotionSdkPhp\Resource\Block\ChildDatabaseBlock;
use Brd6\NotionSdkPhp\Resource\Block\ChildPageBlock;
use Brd6\NotionSdkPhp\Resource\Pagination\PaginationRequest;
use Http\Client\Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class UpdateNotionHomeJob implements ShouldQueue
{
    use Queueable;

    private NotionDatabase $database;

    private SourceSync $sourceSync;

    /**
     * Create a new job instance.
     */
    public function __construct(SourceSync $sourceSync, NotionDatabase $database)
    {
        $this->sourceSync = $sourceSync;
        $this->database = $database;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Get notion details
        $token = $this->database->notionWorkspace?->token;
        if ($token === null) {
            Log::error('No token found for database '.$this->database->id);
            $this->release();

            return;
        }
        $homePageId = $this->database->notion_home_id;

        // Create notion client
        $options = (new ClientOptions)
            ->setAuth($token);
        $notion = new Client($options);

        // Find child blocks to delete
        $blocksToDelete = [];
        try {
            /** @var ?string $nextCursor */
            $nextCursor = null;
            $databaseFound = false;
            /** @var array<string> $blocksToDelete */
            do {
                // Request children and set new cursor, if there are more
                $request = new PaginationRequest;
                if ($nextCursor !== null) {
                    $request->setStartCursor($nextCursor);
                }
                $homePageContent = $notion->blocks()->children()->list($homePageId, $request);
                if ($homePageContent->isHasMore()) {
                    $nextCursor = $homePageContent->getNextCursor();
                }

                // Parse children, determining which to delete
                $children = $homePageContent->getResults();
                for ($i = 0; $i < count($children); $i++) {
                    // Start deleting once first database found
                    /** @var AbstractBlock $child */
                    $child = $children[$i];
                    if ((! $databaseFound) && $child instanceof ChildDatabaseBlock) {
                        $databaseFound = true;

                        continue;
                    }

                    // Delete if not a database or page
                    if ($databaseFound && (! ($child instanceof ChildPageBlock)) && (! ($child instanceof ChildDatabaseBlock))) {
                        // todo:
                        $id = $child->getId();
                        $blocksToDelete[] = $id;
                    }
                }
            } while ($homePageContent->isHasMore());
        } catch (ApiResponseException|HttpResponseException|InvalidPaginationResponseException|RequestTimeoutException|UnsupportedPaginationResponseTypeException|Exception $e) {
            Log::error('Error getting children', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString(), 'databaseId' => $this->database->id]);
            /** @noinspection PhpUnhandledExceptionInspection */
            throw $e;
        } catch (InvalidResourceException|InvalidResourceTypeException|UnsupportedUserTypeException $e) {
            Log::error('Error getting children', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString(), 'databaseId' => $this->database->id]);
        }

        // Delete child blocks
        if (count($blocksToDelete) > 0) {
            foreach ($blocksToDelete as $blockID) {
                try {
                    $notion->blocks()->delete($blockID);
                } catch (ApiResponseException|HttpResponseException|InvalidResourceException|InvalidResourceTypeException|RequestTimeoutException|UnsupportedUserTypeException|Exception $e) {
                    Log::error('Error deleting block', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString(), 'databaseId' => $this->database->id]);
                }
            }
        }

        // TODO:
        // - (first attempt: just submit names, not links)
        // - From sourcesync, get documents
        // - Arrange as a tree
        //   - Root pages first
        //   - Then alphabetical first folder
        //     - And inside that, root pages first
        //     - Then any subfolders with their pages
        // (second attempt: links!)
        // - Save notion page ID to `Document` when syncing
        // - Update the above to create links
        Log::info('UpdateNotionHomeJob handled');
    }
}
