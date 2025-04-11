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
     * Constructor
     *
     * @param  string  $apiToken  Notion API token
     */
    public function __construct($apiToken = null)
    {
        $this->apiToken = $apiToken ?? config('services.notion.api_token');
    }

    /**
     * Append a paragraph block with text to a Notion page
     *
     * @param  string  $pageId  The ID of the page to append blocks to
     * @param  string  $text  The text content for the paragraph block
     * @return Response
     */
    public function appendParagraphBlock($pageId, $text = 'test')
    {
        $endpoint = "{$this->baseUrl}/blocks/{$pageId}/children";

        $payload = [
            'children' => [
                [
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
                ],
            ],
        ];

        return $this->makeRequest('PATCH', $endpoint, $payload);
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
}
