<?php

declare(strict_types=1);

namespace Tests\Jobs\PageCreator;

use App\Jobs\PageCreator\PageCreatedMessage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PageCreatedMessageTest extends TestCase
{
    public function test_page_created_message_creation(): void
    {
        $json = <<<'EOD'
{
  "type": "page-created",
  "notionPageID": "page id",
  "notionURL": "notion url"
}
EOD;
        $message = PageCreatedMessage::from($json);
        $this->assertInstanceOf(PageCreatedMessage::class, $message);
    }

    public function test_type_must_be_page_created(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('The selected type is invalid.');
        $json = <<<'EOD'
{
  "type": "invalid type",
  "notionPageID": "page id",
  "notionURL": "notion url"
}
EOD;
        PageCreatedMessage::from($json);
    }

    public function test_type_required(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('The type field is required.');
        $json = <<<'EOD'
{
  "notionPageID": "page id",
  "notionURL": "notion url"
}
EOD;
        PageCreatedMessage::from($json);
    }

    public function test_notion_page_id_required(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('The notion page i d field is required.');
        $json = <<<'EOD'
{
  "type": "page-created",
  "notionURL": "notion url"
}
EOD;
        PageCreatedMessage::from($json);
    }

    public function test_notion_url_required(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('The notion u r l field is required.');
        $json = <<<'EOD'
{
  "type": "page-created",
  "notionPageID": "page id"
}
EOD;
        PageCreatedMessage::from($json);
    }
}
