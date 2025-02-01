<?php

namespace App\Jobs;

use App\Models\NotionDatabase;
use App\Models\SourceSync;
use Brd6\NotionSdkPhp\Client;
use Brd6\NotionSdkPhp\ClientOptions;
use Brd6\NotionSdkPhp\Exception\ApiResponseException;
use Brd6\NotionSdkPhp\Exception\HttpResponseException;
use Brd6\NotionSdkPhp\Exception\InvalidPaginationResponseException;
use Brd6\NotionSdkPhp\Exception\RequestTimeoutException;
use Brd6\NotionSdkPhp\Exception\UnsupportedPaginationResponseTypeException;
use Brd6\NotionSdkPhp\Resource\Block\ParagraphBlock;
use Brd6\NotionSdkPhp\Resource\Pagination\AbstractPaginationResults;
use Brd6\NotionSdkPhp\Resource\Pagination\PaginationRequest;
use Http\Client\Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use ValueError;

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
        $token = $this->database->notionWorkspace?->token;
        if ($token === null) {
            Log::error('No token found for database '.$this->database->id);
            $this->release();

            return;
        }
        $homePageId = $this->database->notion_home_id;

        // Paginate through children
        $options = (new ClientOptions)
            ->setAuth($token);

        $notion = new Client($options);

        // TODO: FIrst attempt: Paginate through all children and print them out
        // make sure it works for more than 100
        $children = [];
        try {
            $homePageContent = $notion->blocks()->children()->list($homePageId);
            $newChildren = $this->getNewChildren($homePageContent);
            $children = array_merge($children, $newChildren);

            while ($homePageContent->isHasMore()) {
                $request = new PaginationRequest;
                $request->setStartCursor($homePageContent->getNextCursor());
                $homePageContent = $notion->blocks()->children()->list($homePageId, $request);
                $newChildren = $this->getNewChildren($homePageContent);
                $children = array_merge($children, $newChildren);
            }
        } catch (ApiResponseException|HttpResponseException|InvalidPaginationResponseException|RequestTimeoutException|UnsupportedPaginationResponseTypeException|Exception $e) {
            Log::error('Error getting children', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString(), 'databaseId' => $this->database->id]);
            /** @noinspection PhpUnhandledExceptionInspection */
            throw $e;
        }

        for ($i = 0; $i < count($children); $i++) {
            Log::info('Got children', ['children' => $children[$i]]);
        }

        //        foreach ($children->getResults() as $child) {
        //            $notion->blocks()->delete($child->getId());
        //        }

        // - Delete everything after the database (see database ID in NotionDatabase model)
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

    /**
     * @return array|array[]
     */
    public function getNewChildren(AbstractPaginationResults $homePageContent
    ): array {
        return array_map(function ($child) {
            if (! is_object($child)) {
                Log::error('Child is not an object', ['child' => $child]);
                throw new ValueError('$child is not an object');
            }

            $textContent = 'Unsupported block';
            if ($child instanceof ParagraphBlock) {
                $richText = $child->getParagraph()->getRichText();
                $textContent = count($richText) > 0 ? $richText[0]->getPlainText() : '(empty)';
            }

            return [
                'id' => $child->getId(),
                'textContent' => $textContent,
            ];
        }, $homePageContent->getResults());
    }
}
