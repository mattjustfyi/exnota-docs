<?php

declare(strict_types=1);

namespace Tests\Jobs;

use App\Jobs\TableFixer;
use Tests\TestCase;

class TableFixerTest extends TestCase
{
    public function test_fix_single_column(): void
    {
        $content = <<<'EOD'
a paragraph

|heading|
||
EOD;
        $expected = <<<'EOD'
a paragraph

|heading|
| |
EOD;
        $fixer = new TableFixer($content);
        $result = $fixer->fix();
        $this->assertEquals($expected, $result);

    }

    public function test_fix_multi_column(): void
    {
        $content = <<<'EOD'
a paragraph

|heading 1| heading 2 ||heading 4|
|content||||
|a|b|c|d|
EOD;
        $expected = <<<'EOD'
a paragraph

|heading 1| heading 2 | |heading 4|
|content| | | |
|a|b|c|d|
EOD;
        $fixer = new TableFixer($content);
        $result = $fixer->fix();
        $this->assertEquals($expected, $result);

    }

    public function test_fix_with_delimiter_row(): void
    {
        $content = <<<'EOD'
| Action Item | Owner | Issue | Notes |
|-------------|-------|-------|-------|
| item || ||
EOD;
        $expected = <<<'EOD'
| Action Item | Owner | Issue | Notes |
|-------------|-------|-------|-------|
| item | | | |
EOD;
        $fixer = new TableFixer($content);
        $result = $fixer->fix();
        $this->assertEquals($expected, $result);
    }
}
