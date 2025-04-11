<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Jobs\Notion\NotionApiHelper;
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
use Brd6\NotionSdkPhp\Resource\Block\ColumnBlock;
use Brd6\NotionSdkPhp\Resource\Block\ColumnListBlock;
use Brd6\NotionSdkPhp\Resource\Block\Heading2Block;
use Brd6\NotionSdkPhp\Resource\Block\Heading3Block;
use Brd6\NotionSdkPhp\Resource\Block\LinkToPageBlock;
use Brd6\NotionSdkPhp\Resource\Pagination\PaginationRequest;
use Brd6\NotionSdkPhp\Resource\Property\HeadingProperty;
use Brd6\NotionSdkPhp\Resource\Property\LinkToPageProperty;
use Brd6\NotionSdkPhp\Resource\RichText\Text;
use Http\Client\Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Notion\Blocks\Heading1;
use Notion\Blocks\Paragraph;
use Notion\Notion;
use Webmozart\Assert\Assert;

class UpdateNotionHomeJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly SourceSync $sourceSync,
        private readonly NotionDatabase $database,
        private readonly bool $forceUpdate = false
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $filePaths = $this->sourceSync->file_paths;
        Assert::notNull($filePaths);
        if (! $this->forceUpdate && count($filePaths) === 0) {
            Log::info('No file paths updated: Not changing Notion home.');

            return;
        }

        // Don't update if there are no new pages
        if (! $this->forceUpdate && ! $this->sourceSync->hasNewDocuments()) {
            Log::info('No new documents: Not changing Notion home.');

            return;
        }

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
        $bernNotion = new Client($options);

        // Find child blocks to delete
        Log::info('Finding child blocks');
        /** @var array<string> $rootChildrenToDelete */
        $rootChildrenToDelete = [];
        try {
            /** @var ?string $nextCursor */
            $nextCursor = null;
            $databaseFound = false;
            do {
                // Request children and set new cursor, if there are more
                $request = new PaginationRequest;
                if ($nextCursor !== null) {
                    $request->setStartCursor($nextCursor);
                }
                $homePageContent = $bernNotion->blocks()->children()->list($homePageId, $request);
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
                        $rootChildrenToDelete[] = $id;
                    }
                }
            } while ($homePageContent->isHasMore());
        } catch (ApiResponseException|HttpResponseException|InvalidPaginationResponseException|RequestTimeoutException|UnsupportedPaginationResponseTypeException|Exception $e) {
            Log::error('Error getting children', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString(), 'databaseId' => $this->database->id]);
            /** @noinspection PhpUnhandledExceptionInspection */
            throw $e;
        }

        // Delete child blocks
        Log::info('Deleting child blocks');
        if (count($rootChildrenToDelete) > 0) {
            foreach ($rootChildrenToDelete as $blockID) {
                try {
                    $bernNotion->blocks()->delete($blockID);
                } catch (ApiResponseException|HttpResponseException|InvalidResourceException|InvalidResourceTypeException|RequestTimeoutException|UnsupportedUserTypeException|Exception $e) {
                    Log::error('Error deleting block', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString(), 'databaseId' => $this->database->id]);
                }
            }
        }

        // Build folder structure
        $source = $this->sourceSync->source;
        Assert::notNull($source);
        $documents = $source->latestDocuments();
        $rootFolder = FolderBuilder::build($documents, $source);

        // Add home page blocks
        Log::info('Creating new child blocks');
        try {
            $newRootChildren = [];

            // TODO: Create a $marioNotion client and refactor below to use that
            // because it supports columns
            $marioNotion = Notion::create($token);

            // Spacing
            $emptyParagraph = Paragraph::fromString(' ');
            $newRootChildren[] = $emptyParagraph;

            $h1 = Heading1::fromString('📚 Documentation');
            $newRootChildren[] = $h1;

            // Root level
            //            $rootH2 = new Heading2Block;
            // [h2] added temporarily to aid testing; it's hard to distinguish in Notion
            //            $text = Text::fromContent('[h2] '.$rootFolder->name);
            //            $headingProperty = new HeadingProperty;
            //            $headingProperty->setRichText([$text]);
            //            $rootH2->setHeading2($headingProperty);
            //            $blocks[] = $rootH2;

            foreach ($rootFolder->getFiles() as $file) {
                // TODO: 'LinkToPage' not supported by marioNotion -_-
                // Attempt 1: Create my own LinkToPage class
                // - first just copy the bookmark class and try using that
                // - then rename it and ensure it works
                // - then modify to be link_to_page and see if that works.
                //
                // If not, maybe just do this request as a direct http request and
                // get AI to write it.

                // In a controller or command
                $notionHelper = new NotionApiHelper($token);
                $response = $notionHelper->appendParagraphBlock($homePageId);

                // To add custom text
                $response = $notionHelper->appendParagraphBlock($homePageId, 'Your custom paragraph text here');

                // Check the response
                if ($response->successful()) {
                    // Block was added successfully
                    Log::info('Paragraph created');
                } else {
                    // Handle error
                    $error = $response->json();
                    Log::error('Paragraph created', ['error' => $error]);
                }

                //                $linkToPage = LinkToPage::fromPageId('1a6944fb-b2bc-81ec-93af-d178b3cab8d9');
                //                $newRootChildren[] = $linkToPage;

                //                $link = new LinkToPageBlock;
                //                $linkProperty = new LinkToPageProperty;
                //                $linkProperty->setPageId($file->notionID);
                //                $link->setLinkToPage($linkProperty);
                //                $newRootChildren[] = $link;
            }
            //
            //            // Column lists
            //            $firstLevelFolders = $rootFolder->getFolders();
            //            for ($i = 0; $i < count($firstLevelFolders); $i += 2) {
            //                // Create columns
            //                /** @var ColumnBlock[] $columns */
            //                $columns = [];
            //
            //                // Get left and right folders for columns
            //                $folderLeft = $firstLevelFolders[$i];
            //                $folderRight = $i + 1 < count($firstLevelFolders) ? $firstLevelFolders[$i + 1] : null;
            //                // array_filter should remove falsey values.
            //                $columnFolders = array_filter([$folderLeft, $folderRight]);
            //
            //                // Columns
            //                foreach ($columnFolders as $folder) {
            //                    // Create column children
            //                    /** @var array<int, AbstractBlock> $columnChildren */
            //                    $columnChildren = [];
            //
            //                    // Level 1 name
            //                    $heading = new Heading2Block;
            //                    $text = Text::fromContent('[h2] '.$folder->name);
            //                    $headingProperty = new HeadingProperty;
            //                    $headingProperty->setRichText([$text]);
            //                    $heading->setHeading2($headingProperty);
            //                    $columnChildren[] = $heading;
            //
            //                    // Level 1 files
            //                    foreach ($folder->getFiles() as $file) {
            //                        $link = new LinkToPageBlock;
            //                        $linkProperty = new LinkToPageProperty;
            //                        $linkProperty->setPageId($file->notionID);
            //                        $link->setLinkToPage($linkProperty);
            //                        $columnChildren[] = $link;
            //                    }
            //
            //                    // Level 2 folders
            //                    $secondLevelFolders = $folder->getFolders();
            //                    foreach ($secondLevelFolders as $secondLevelFolder) {
            //                        // Level 2 name
            //                        $heading = new Heading3Block;
            //                        $text = Text::fromContent('[h3] '.$secondLevelFolder->name);
            //                        $headingProperty = new HeadingProperty;
            //                        $headingProperty->setRichText([$text]);
            //                        $heading->setHeading3($headingProperty);
            //                        $columnChildren[] = $heading;
            //
            //                        // Level 2 files
            //                        foreach ($secondLevelFolder->getFiles() as $file) {
            //                            $link = new LinkToPageBlock;
            //                            $linkProperty = new LinkToPageProperty;
            //                            $linkProperty->setPageId($file->notionID);
            //                            $link->setLinkToPage($linkProperty);
            //                            $columnChildren[] = $link;
            //                        }
            //                    }
            //
            //                    // Add children to column, and column to list
            //                    $column = new ColumnBlock;
            //                    $column->setChildren($columnChildren);
            //                    $columns[] = $column;
            //                }
            //
            //                // Add columns to list, and list to root
            //                $columnList = new ColumnListBlock;
            //                $columnList->setChildren($columns);
            //                $newRootChildren[] = $columnList;
            //            }

            // Notion has a 100 limit for the number of blocks appended at once
            //            $blockChunks = array_chunk($newRootChildren, 100);
            //            foreach ($blockChunks as $blockChunk) {
            //                $marioNotion->blocks()->append($homePageId, $blockChunk);
            //            }
        } catch (ApiResponseException|HttpResponseException|InvalidPaginationResponseException|RequestTimeoutException|UnsupportedPaginationResponseTypeException|Exception $e) {
            Log::error('Error appending block', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString(), 'databaseId' => $this->database->id]);
            /** @noinspection PhpUnhandledExceptionInspection Rethrow to retry job. */
            throw $e;
        }

        Log::info('UpdateNotionHomeJob handled');
    }
}
