<?php

declare(strict_types=1);

namespace App\Jobs\Notion;

use Brd6\NotionSdkPhp\Client;
use Brd6\NotionSdkPhp\Exception\ApiResponseException;
use Brd6\NotionSdkPhp\Exception\HttpResponseException;
use Brd6\NotionSdkPhp\Exception\InvalidPaginationResponseException;
use Brd6\NotionSdkPhp\Exception\InvalidResourceException;
use Brd6\NotionSdkPhp\Exception\InvalidResourceTypeException;
use Brd6\NotionSdkPhp\Exception\RequestTimeoutException;
use Brd6\NotionSdkPhp\Exception\UnsupportedPaginationResponseTypeException;
use Brd6\NotionSdkPhp\Exception\UnsupportedUserTypeException;
use Brd6\NotionSdkPhp\Resource\Block\AbstractBlock;
use Brd6\NotionSdkPhp\Resource\Pagination\PaginationRequest;
use Http\Client\Exception;
use Illuminate\Support\Facades\Log;
use Webmozart\Assert\Assert;

readonly class PageContentMutator
{
    public function __construct(private string $pageID, private Client $notion) {}

    public function deleteAll(): void
    {

        // Find child blocks to delete
        $blockIDs = [];
        try {
            /** @var ?string $nextCursor */
            $nextCursor = null;
            do {
                // Request children and set new cursor, if there are more
                $request = new PaginationRequest;
                if ($nextCursor !== null) {
                    $request->setStartCursor($nextCursor);
                }
                $pageContent = $this->notion->blocks()->children()->list($this->pageID, $request);
                if ($pageContent->isHasMore()) {
                    $nextCursor = $pageContent->getNextCursor();
                }

                $paginatedBlockIDs = array_map(function ($child) {
                    Assert::isInstanceOf($child, AbstractBlock::class);

                    return $child->getId();
                }, $pageContent->getResults());

                $blockIDs = array_merge($blockIDs, $paginatedBlockIDs);
            } while ($pageContent->isHasMore());
        } catch (ApiResponseException|HttpResponseException|InvalidPaginationResponseException|RequestTimeoutException|UnsupportedPaginationResponseTypeException|Exception $e) {
            Log::error('Error getting children', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString(), 'pageID' => $this->pageID]);
            /** @noinspection PhpUnhandledExceptionInspection */
            throw $e;
        }

        // Delete child blocks
        if (count($blockIDs) > 0) {
            foreach ($blockIDs as $blockID) {
                try {
                    $this->notion->blocks()->delete($blockID);
                } catch (ApiResponseException|HttpResponseException|InvalidResourceException|InvalidResourceTypeException|RequestTimeoutException|UnsupportedUserTypeException|Exception $e) {
                    Log::error('Error deleting block', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString(), 'pageID' => $this->pageID]);
                }
            }
        }
    }
}
