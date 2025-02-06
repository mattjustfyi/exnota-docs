<?php

declare(strict_types=1);

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
use Brd6\NotionSdkPhp\Resource\Block\Heading1Block;
use Brd6\NotionSdkPhp\Resource\Block\Heading2Block;
use Brd6\NotionSdkPhp\Resource\Block\ParagraphBlock;
use Brd6\NotionSdkPhp\Resource\Pagination\PaginationRequest;
use Brd6\NotionSdkPhp\Resource\Property\HeadingProperty;
use Brd6\NotionSdkPhp\Resource\Property\ParagraphProperty;
use Brd6\NotionSdkPhp\Resource\RichText\Text;
use Http\Client\Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Webmozart\Assert\Assert;

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
                        $id = $child->getId();
                        $blocksToDelete[] = $id;
                    }
                }
            } while ($homePageContent->isHasMore());
        } catch (ApiResponseException|HttpResponseException|InvalidPaginationResponseException|RequestTimeoutException|UnsupportedPaginationResponseTypeException|Exception $e) {
            Log::error('Error getting children', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString(), 'databaseId' => $this->database->id]);
            /** @noinspection PhpUnhandledExceptionInspection */
            throw $e;
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

        $documents = $this->sourceSync->getDocuments();

        // How to make into a tree
        // 1. Fix database seeder so the source path is the base of the documents' paths
        // - for each document
        // - subtract the source path from the file path
        // - convert to a basic tree structure with folders and files, the latter of which are leaf nodes
        // - Folder{files:string[], folders:Folder[]}

        $source = $this->sourceSync->source;
        Assert::notNull($source);

        $rootFolder = FolderBuilder::build($documents, $source);
        try {
            $blocks = [];

            // Spacing
            $emptyParagraph = new ParagraphBlock;
            $text = Text::fromContent(' ');
            $paragraphProperty = new ParagraphProperty;
            $paragraphProperty->setRichText([$text]);
            $emptyParagraph->setParagraph($paragraphProperty);
            $blocks[] = $emptyParagraph;

            // TODO: instead of a dividder use a heading, as dividers don't seem to work with the notion library
            $h1 = new Heading1Block;
            $text = Text::fromContent('📚 Documentation');
            $headingProperty = new HeadingProperty;
            $headingProperty->setRichText([$text]);
            $h1->setHeading1($headingProperty);
            $blocks[] = $h1;

            // Root level
            $rootH2 = new Heading2Block;
            $text = Text::fromContent($rootFolder->name);
            $headingProperty = new HeadingProperty;
            $headingProperty->setRichText([$text]);
            $rootH2->setHeading2($headingProperty);
            $blocks[] = $rootH2;

            foreach ($rootFolder->getFiles() as $file) {
                $block = new ParagraphBlock;
                $text = Text::fromContent($file);
                $paragraphProperty = new ParagraphProperty;
                $paragraphProperty->setRichText([$text]);
                $block->setParagraph($paragraphProperty);
                $blocks[] = $block;
            }

            // Nested grouped by first level
            $firstLevelFolders = $rootFolder->getFolders();
            foreach ($firstLevelFolders as $folder) {
                // Heading
                $heading = new Heading2Block;
                $text = Text::fromContent($folder->name);
                $headingProperty = new HeadingProperty;
                $headingProperty->setRichText([$text]);
                $heading->setHeading2($headingProperty);
                $blocks[] = $heading;

                // Files
                foreach ($folder->getFiles() as $file) {
                    $block = new ParagraphBlock;
                    $text = Text::fromContent($file);
                    $paragraphProperty = new ParagraphProperty;
                    $paragraphProperty->setRichText([$text]);
                    $block->setParagraph($paragraphProperty);
                    $blocks[] = $block;
                }
            }

            $notion->blocks()->children()->append($homePageId, $blocks);
        } catch (ApiResponseException|HttpResponseException|InvalidPaginationResponseException|RequestTimeoutException|UnsupportedPaginationResponseTypeException|Exception $e) {
            Log::error('Error appending block', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString(), 'databaseId' => $this->database->id]);
            /** @noinspection PhpUnhandledExceptionInspection Rethrow to retry job. */
            throw $e;
        }

        // TODO: How can I run this even faster
        // steps
        // - optional: pamfs -vvv (refresh db)
        // - exit tinker
        // - open tinker
        // - do the breakpoint/focus dance
        // - run 3 commands

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
