<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Document;
use App\Models\Source;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use ValueError;
use Webmozart\Assert\Assert;

class FolderBuilder
{
    /**
     * @param  Collection<int, Document>  $documents
     */
    public static function build(Collection $documents, Source $source): Folder
    {
        $rootFolder = new Folder;
        $rootFolder->name = 'Root';

        foreach ($documents as $document) {
            $relativePath = FolderBuilder::getRelativePath($document, $source);
            $pathParts = explode('/', $relativePath);

            // Expect relative path to start with / and therefore there should be an
            // initial and blank path part.
            Assert::minCount($pathParts, 2, 'did not expect count less than 2');
            Assert::eq($pathParts[0], '', 'did not expect first part of path parts to be an empty string');

            // Remove initial ''
            array_shift($pathParts);

            if (count($pathParts) === 1) {
                $notionID = $document->notion_id;
                $notionURL = $document->notion_url;
                Assert::notNull($notionID);
                Assert::notNull($notionURL);

                $file = new File(name: $pathParts[0], notionID: $notionID, notionURL: $notionURL);
                $rootFolder->addFile($file);
            } else {
                // Loop through path parts and find the deepest folder that already exists
                $existingFolder = $rootFolder;
                $remainingPathParts = $pathParts;
                for ($i = 0; $i < count($pathParts) - 1; $i++) {
                    $folderName = FolderBuilder::formatFolderName($pathParts[$i]);

                    //  Search subfolders
                    $nextSubfolder = collect($existingFolder->getFolders())
                        ->first(fn ($folder) => $folder->name === $folderName);

                    if ($nextSubfolder === null) {
                        break;
                    }

                    $existingFolder = $nextSubfolder;
                    $remainingPathParts = array_slice($remainingPathParts, 1);
                }

                if (count($remainingPathParts) > 1) {
                    for ($i = 0; $i < count($remainingPathParts) - 1; $i++) {
                        $folderName = FolderBuilder::formatFolderName($remainingPathParts[$i]);

                        $folder = new Folder;
                        $folder->name = $folderName;
                        Log::info('Created folder with name '.$folder->name);
                        $existingFolder->addFolder($folder);
                        $existingFolder = $folder;
                    }
                }

                $notionID = $document->notion_id;
                $notionURL = $document->notion_url;
                Assert::notNull($notionID);
                Assert::notNull($notionURL);

                $file = new File(name: $pathParts[count($pathParts) - 1], notionID: $notionID, notionURL: $notionURL);
                $existingFolder->addFile($file);
            }
        }

        return $rootFolder;
    }

    public static function getRelativePath(Document $document, Source $source): string
    {
        // Verify document path starts with source path
        $sourcePath = $source->path;
        if (! str_starts_with($document->path, $sourcePath)) {
            throw new ValueError('Document path does not start with source path: '.$document->path.' ('.$sourcePath.')');
        }

        return substr($document->path, strlen($sourcePath));
    }

    private static function formatFolderName(string $name): string
    {
        return ucwords(str_replace('_', ' ', $name));
    }
}
