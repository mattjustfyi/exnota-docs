<?php

declare(strict_types=1);

// Test creating columns using notion-sdk-php since the other
// sdk doesn't work.

use Notion\Blocks\Column;
use Notion\Blocks\ColumnList;
use Notion\Blocks\Paragraph;
use Notion\Notion;
use Webmozart\Assert\Assert;

$token = config('services.notion.token');
Assert::string($token);
$notion = Notion::create($token);

$paragraph = Paragraph::fromString('hi!');
$columnLeft = Column::create($paragraph);
$columnRight = Column::create($paragraph);
$columnMiddle = Column::create($paragraph);
$columnList = ColumnList::create($columnLeft, $columnMiddle, $columnRight);

$pageId = '183944fbb2bc805695b5d38657cb4231';

$notion->blocks()->append($pageId, [$columnList]);
