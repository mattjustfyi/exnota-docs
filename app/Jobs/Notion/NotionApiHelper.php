<?php

declare(strict_types=1);

namespace App\Jobs\Notion;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class NotionApiHelper
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
     * Pending paragraphs to be appended
     */
    protected $pendingParagraphs = [];

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
     * Submit the pending paragraphs to the Notion API
     *
     * @return Response
     */
    public function submit()
    {
        $endpoint = "{$this->baseUrl}/blocks/{$this->pageId}/children";

        $payload = [
            'children' => $this->pendingParagraphs,
        ];

        // Clear the pending paragraphs after submitting
        $response = $this->makeRequest('PATCH', $endpoint, $payload);
        $this->pendingParagraphs = [];

        return $response;
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
        return Http::withHeaders([
            'Authorization' => "Bearer {$this->apiToken}",
            'Content-Type' => 'application/json',
            'Notion-Version' => $this->notionVersion,
        ])->$method($endpoint, $payload);
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

        $this->pendingParagraphs[] = $paragraph;

        return $this;
    }
}
