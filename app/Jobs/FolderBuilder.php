<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Document;
use App\Models\Source;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use ValueError;

class FolderBuilder
{
    /**
     * @param  Collection<int, Document>  $documents
     */
    public static function build(Collection $documents, Source $source): Folder
    {
        $rootFolder = new Folder;
        $rootFolder->name = 'root';

        foreach ($documents as $document) {
            $relativePath = FolderBuilder::getRelativePath($document, $source);
            $pathParts = explode('/', $relativePath);

            // TODO: replace with assert
            // Expect relative path to start with / and therefore there should be an
            // initial and blank path part.
            if (count($pathParts) < 2) {
                throw new ValueError('did not expect count less than 2');
            }

            if ($pathParts[0] !== '') {
                throw new ValueError('Expected first part of path parts to be an empty string');
            }

            // Remove initial ''
            array_shift($pathParts);

            if (count($pathParts) === 1) {
                $rootFolder->addFile($pathParts[0]);
            } else {
                // Loop through path parts and find the deepest folder that already exists
                $existingFolder = $rootFolder;
                $remainingPathParts = $pathParts;
                for ($i = 0; $i < count($pathParts) - 1; $i++) {
                    $folderName = $pathParts[$i];

                    //  Search subfolders
                    $nextSubfolder = array_find($existingFolder->getFolders(),
                        fn ($folder) => $folder->name === $folderName);
                    if ($nextSubfolder === null) {
                        break;
                    }

                    $existingFolder = $nextSubfolder;
                    $remainingPathParts = array_slice($remainingPathParts, 1);
                }

                if (count($remainingPathParts) > 1) {
                    for ($i = 0; $i < count($remainingPathParts) - 1; $i++) {
                        $folderName = $remainingPathParts[$i];

                        $folder = new Folder;
                        $folder->name = $folderName;
                        Log::info('Created folder with name '.$folder->name);
                        $existingFolder->addFolder($folder);
                        $existingFolder = $folder;
                    }
                }

                $existingFolder->addFile($pathParts[count($pathParts) - 1]);
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
}
