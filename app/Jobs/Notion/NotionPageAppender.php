<?php

declare(strict_types=1);

namespace App\Jobs\Notion;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotionPageAppender
{
    /**
     * Notion API base URL
     */
    protected $baseUrl = 'https://api.notion.com/v1';

    /**
     * Notion API version
     */
    protected $notionVersion = '2022-06-28';

    /**
     * Notion API token
     */
    protected $apiToken;

    /**
     * Notion page ID
     */
    protected $pageId;

    /**
     * Pending blocks to be appended
     */
    protected $pendingBlocks = [];

    /**
     * Flag to track if any request has failed
     */
    protected $hasRequestFailed = false;

    /**
     * Constructor
     *
     * @param  string  $pageId  Notion page ID
     * @param  string  $apiToken  Notion API token
     */
    public function __construct($pageId, $apiToken = null)
    {
        $this->pageId = $pageId;
        $this->apiToken = $apiToken ?? config('services.notion.api_token');
    }

    /**
     * Legacy method for backwards compatibility
     *
     * @param  string  $pageId  The ID of the page to append blocks to (ignored, uses constructor value)
     * @param  string  $text  The text content for the paragraph block
     * @return Response
     */
    public function appendParagraphBlock($pageId = null, $text = 'test')
    {
        // Ignore the pageId parameter and use the one from constructor
        return $this->appendParagraph($text)->submit();
    }

    /**
     * Submit the pending blocks to the Notion API
     *
     * @return Response|null
     */
    public function submit()
    {
        if ($this->hasRequestFailed) {
            Log::warning('Skipping submit due to previous request failure', [
                'pageId' => $this->pageId,
                'pendingBlocksCount' => count($this->pendingBlocks)
            ]);
            return null;
        }

        $endpoint = "{$this->baseUrl}/blocks/{$this->pageId}/children";

        // Notion has a 100 limit for the number of blocks appended at once
        $blockChunks = array_chunk($this->pendingBlocks, 100);
        $lastResponse = null;

        foreach ($blockChunks as $index => $blockChunk) {
            $payload = [
                'children' => $blockChunk,
            ];

            $lastResponse = $this->makeRequest('PATCH', $endpoint, $payload);

            if (!$lastResponse->successful()) {
                $this->hasRequestFailed = true;
                Log::error('Failed to append blocks to Notion page', [
                    'pageId' => $this->pageId,
                    'chunkIndex' => $index,
                    'chunkSize' => count($blockChunk),
                    'statusCode' => $lastResponse->status(),
                    'response' => $lastResponse->json(),
                    'payload' => $payload
                ]);
                break;
            }

            Log::info('Successfully appended blocks to Notion page', [
                'pageId' => $this->pageId,
                'chunkIndex' => $index,
                'chunkSize' => count($blockChunk)
            ]);
        }

        // Clear the pending blocks after submitting
        $this->pendingBlocks = [];

        return $lastResponse;
    }

    /**
     * Make a request to the Notion API
     *
     * @param  string  $method  HTTP method
     * @param  string  $endpoint  API endpoint
     * @param  array  $payload  Request payload
     * @return Response
     */
    protected function makeRequest($method, $endpoint, $payload = [])
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiToken}",
                'Content-Type' => 'application/json',
                'Notion-Version' => $this->notionVersion,
            ])->$method($endpoint, $payload);

            if (!$response->successful()) {
                Log::error('Notion API request failed', [
                    'method' => $method,
                    'endpoint' => $endpoint,
                    'statusCode' => $response->status(),
                    'response' => $response->json()
                ]);
            }

            return $response;
        } catch (\Exception $e) {
            Log::error('Exception occurred during Notion API request', [
                'method' => $method,
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->hasRequestFailed = true;
            throw $e;
        }
    }

    /**
     * Add a paragraph to the pending list
     *
     * @param  string  $text  The text content for the paragraph block
     * @return $this
     */
    public function appendParagraph($text)
    {
        $paragraph = [
            'object' => 'block',
            'type' => 'paragraph',
            'paragraph' => [
                'rich_text' => [
                    [
                        'type' => 'text',
                        'text' => [
                            'content' => $text,
                        ],
                    ],
                ],
            ],
        ];

        $this->pendingBlocks[] = $paragraph;

        return $this;
    }

    /**
     * Add an H1 heading to the pending list
     *
     * @param  string  $text  The text content for the heading block
     * @return $this
     */
    public function appendHeading1($text)
    {
        $heading = [
            'object' => 'block',
            'type' => 'heading_1',
            'heading_1' => [
                'rich_text' => [
                    [
                        'type' => 'text',
                        'text' => [
                            'content' => $text,
                        ],
                    ],
                ],
            ],
        ];

        $this->pendingBlocks[] = $heading;

        return $this;
    }

    /**
     * Add an H2 heading to the pending list
     *
     * @param  string  $text  The text content for the heading block
     * @return $this
     */
    public function appendHeading2($text)
    {
        $heading = [
            'object' => 'block',
            'type' => 'heading_2',
            'heading_2' => [
                'rich_text' => [
                    [
                        'type' => 'text',
                        'text' => [
                            'content' => $text,
                        ],
                    ],
                ],
            ],
        ];

        $this->pendingBlocks[] = $heading;

        return $this;
    }

    /**
     * Add an H3 heading to the pending list
     *
     * @param  string  $text  The text content for the heading block
     * @return $this
     */
    public function appendHeading3($text)
    {
        $heading = [
            'object' => 'block',
            'type' => 'heading_3',
            'heading_3' => [
                'rich_text' => [
                    [
                        'type' => 'text',
                        'text' => [
                            'content' => $text,
                        ],
                    ],
                ],
            ],
        ];

        $this->pendingBlocks[] = $heading;

        return $this;
    }

    /**
     * Start a column list block
     * 
     * @return $this
     */
    public function startColumnList()
    {
        $this->pendingBlocks[] = [
            'object' => 'block',
            'type' => 'column_list',
            'column_list' => [
                'children' => []
            ]
        ];

        return $this;
    }

    /**
     * Add a column to the most recent column list
     * 
     * @return $this
     */
    public function addColumn()
    {
        // Find the most recent column_list
        $lastIndex = count($this->pendingBlocks) - 1;

        if ($lastIndex < 0 || $this->pendingBlocks[$lastIndex]['type'] !== 'column_list') {
            throw new \Exception('Cannot add column without a column_list');
        }

        $this->pendingBlocks[$lastIndex]['column_list']['children'][] = [
            'object' => 'block',
            'type' => 'column',
            'column' => [
                'children' => []
            ]
        ];

        return $this;
    }

    /**
     * Add a paragraph to the most recent column
     * 
     * @param string $text The text content for the paragraph block
     * @return $this
     */
    public function appendParagraphToColumn($text)
    {
        $this->appendBlockToColumn('paragraph', [
            'rich_text' => [
                [
                    'type' => 'text',
                    'text' => [
                        'content' => $text,
                    ],
                ],
            ],
        ]);

        return $this;
    }

    /**
     * Add an H1 heading to the most recent column
     * 
     * @param string $text The text content for the heading block
     * @return $this
     */
    public function appendHeading1ToColumn($text)
    {
        $this->appendBlockToColumn('heading_1', [
            'rich_text' => [
                [
                    'type' => 'text',
                    'text' => [
                        'content' => $text,
                    ],
                ],
            ],
        ]);

        return $this;
    }

    /**
     * Add an H2 heading to the most recent column
     * 
     * @param string $text The text content for the heading block
     * @return $this
     */
    public function appendHeading2ToColumn($text)
    {
        $this->appendBlockToColumn('heading_2', [
            'rich_text' => [
                [
                    'type' => 'text',
                    'text' => [
                        'content' => $text,
                    ],
                ],
            ],
        ]);

        return $this;
    }

    /**
     * Add an H3 heading to the most recent column
     * 
     * @param string $text The text content for the heading block
     * @return $this
     */
    public function appendHeading3ToColumn($text)
    {
        $this->appendBlockToColumn('heading_3', [
            'rich_text' => [
                [
                    'type' => 'text',
                    'text' => [
                        'content' => $text,
                    ],
                ],
            ],
        ]);

        return $this;
    }

    /**
     * Helper method to append a block to the most recent column
     * 
     * @param string $type The block type
     * @param array $content The block content
     * @return void
     */
    protected function appendBlockToColumn($type, $content)
    {
        $lastIndex = count($this->pendingBlocks) - 1;

        if ($lastIndex < 0 || $this->pendingBlocks[$lastIndex]['type'] !== 'column_list') {
            throw new \Exception('Cannot append to column without a column_list');
        }

        $columnList = &$this->pendingBlocks[$lastIndex]['column_list'];
        $columnCount = count($columnList['children']);

        if ($columnCount === 0) {
            throw new \Exception('No columns available to append to');
        }

        $lastColumnIndex = $columnCount - 1;

        $columnList['children'][$lastColumnIndex]['column']['children'][] = [
            'object' => 'block',
            'type' => $type,
            $type => $content
        ];
    }

    /**
     * Add a link_to_page block to the pending list
     *
     * @param  string  $pageId  The ID of the page to link to
     * @return $this
     */
    public function appendLinkToPage($pageId)
    {
        $linkToPage = [
            'object' => 'block',
            'type' => 'link_to_page',
            'link_to_page' => [
                'type' => 'page_id',
                'page_id' => $pageId,
            ],
        ];

        $this->pendingBlocks[] = $linkToPage;

        return $this;
    }

    /**
     * Add a link_to_page block to the most recent column
     * 
     * @param string $pageId The ID of the page to link to
     * @return $this
     */
    public function appendLinkToPageToColumn($pageId)
    {
        $this->appendBlockToColumn('link_to_page', [
            'type' => 'page_id',
            'page_id' => $pageId,
        ]);

        return $this;
    }
}
