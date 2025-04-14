<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Exceptions\NotionColumnException;
use App\Jobs\Notion\NotionPageAppender;
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
            Log::error('No token found for database ' . $this->database->id);
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
        } catch (ApiResponseException | HttpResponseException | InvalidPaginationResponseException | RequestTimeoutException | UnsupportedPaginationResponseTypeException | Exception $e) {
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
                } catch (ApiResponseException | HttpResponseException | InvalidResourceException | InvalidResourceTypeException | RequestTimeoutException | UnsupportedUserTypeException | Exception $e) {
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
            // In a controller or command
            $notionAppender = new NotionPageAppender($homePageId, $token);

            // Add empty paragraph for spacing and documentation heading
            $notionAppender
                ->appendParagraph(' ')
                ->appendHeading1('📚 Documentation')
                ->submit();

            // Root level - [h2] added temporarily to aid testing; it's hard to distinguish in Notion
            // Add root heading (with temporary [h2] marker) and its files
            $notionAppender->appendHeading2('[h2] ' . $rootFolder->name);
            foreach ($rootFolder->getFiles() as $file) {
                $notionAppender->appendLinkToPage($file->notionID);
            }
            $notionAppender->submit();

            // Column lists for first level folders (two columns per row)
            $firstLevelFolders = $rootFolder->getFolders();
            $totalFolders = count($firstLevelFolders);
            $lastFolderIndex = $totalFolders - 1;

            // Handle pairs of folders in columns
            for ($i = 0; $i < $totalFolders - ($totalFolders % 2); $i += 2) {
                // Get left and right folders for columns
                $folderLeft = $firstLevelFolders[$i];
                $folderRight = $firstLevelFolders[$i + 1];

                // Start a new column list
                $notionAppender->startColumnList();

                // Create each column
                foreach ([$folderLeft, $folderRight] as $folder) {
                    // Add a column
                    $notionAppender->addColumn();
                    $this->appendFolderContent($notionAppender, $folder, true);
                }

                // Submit the column list
                $notionAppender->submit();
            }

            // Handle last folder if odd number of folders
            if ($totalFolders % 2 === 1) {
                $lastFolder = $firstLevelFolders[$lastFolderIndex];
                $this->appendFolderContent($notionAppender, $lastFolder);
                $notionAppender->submit();
            }

            // The NotionPageAppender handles chunking internally (Notion has a 100 block limit per request)
        } catch (ApiResponseException | HttpResponseException | InvalidPaginationResponseException | RequestTimeoutException | UnsupportedPaginationResponseTypeException | NotionColumnException $e) {
            Log::error('Error appending block', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString(), 'databaseId' => $this->database->id]);
            /** @noinspection PhpUnhandledExceptionInspection Rethrow to retry job. */
            throw $e;
        }

        Log::info('UpdateNotionHomeJob handled');
    }

    private function appendFolderContent(NotionPageAppender $notionAppender, Folder $folder, bool $inColumn = false): void
    {
        // Level 1 name
        if ($inColumn) {
            $notionAppender->appendHeading2ToColumn('[h2] ' . $folder->name);
        } else {
            $notionAppender->appendHeading2('[h2] ' . $folder->name);
        }

        // Level 1 files
        foreach ($folder->getFiles() as $file) {
            if ($inColumn) {
                $notionAppender->appendLinkToPageToColumn($file->notionID);
            } else {
                $notionAppender->appendLinkToPage($file->notionID);
            }
        }

        // Level 2 folders
        $secondLevelFolders = $folder->getFolders();
        foreach ($secondLevelFolders as $secondLevelFolder) {
            // Level 2 name
            if ($inColumn) {
                $notionAppender->appendHeading3ToColumn('[h3] ' . $secondLevelFolder->name);
            } else {
                $notionAppender->appendHeading3('[h3] ' . $secondLevelFolder->name);
            }

            // Level 2 files
            foreach ($secondLevelFolder->getFiles() as $file) {
                if ($inColumn) {
                    $notionAppender->appendLinkToPageToColumn($file->notionID);
                } else {
                    $notionAppender->appendLinkToPage($file->notionID);
                }
            }
        }
    }
}
