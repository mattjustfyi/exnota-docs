<?php

declare(strict_types=1);

namespace App\Jobs;

use Webmozart\Assert\Assert;

class TableFixer
{
    private string $content;

    public function __construct(string $content)
    {
        $this->content = $content;
    }

    public function fix(): string
    {
        $lines = explode("\n", $this->content);

        foreach ($lines as $index => $line) {
            if ($this->isTableRow($line, $lines[$index - 1] ?? null, $lines[$index + 1] ?? null)) {
                // A standard str_replace won't work with adjacent matches.
                // E.g. str_replace('||', '| |', `||||`) will result in '| || |'
                while (preg_match('/\|\|/', $lines[$index] ?? '')) {
                    $result = preg_replace('/\|\|/', '| |', $lines[$index], 1);
                    Assert::string($result);
                    $lines[$index] = $result;
                }
            }
        }

        return implode("\n", $lines);
    }

    private function isTableRow(string $line, ?string $previous, ?string $next): bool
    {
        // https://github.com/micromark/micromark-extension-gfm-table?tab=readme-ov-file#syntax
        //
        // > basically anything can be a cell or a row. The main thing that makes something a row, is that it occurs directly before or after a delimiter row, or after another row.
        // >
        // > It is not required for a table to have a body: it can end right after the delimiter row.
        if ($previous === null && $next === null) {
            return false;
        }

        $thisLineIsARow = $this->isSingleLineARow($line);

        if (! $thisLineIsARow) {
            return false;
        }

        if ($previous === null) {
            return $this->isSingleLineARow($next);
        }

        if ($next === null) {
            return $this->isSingleLineARow($previous);
        }

        return $this->isSingleLineARow($previous) || $this->isSingleLineARow($next);
    }

    private function isSingleLineARow(string $row): bool
    {
        // This assumes at least one cell in the row is valid.
        // I.e. `||` may be intended as a row but this regex won't match it.
        return preg_match('/^\|.*\|$/', $row) === 1;
    }
}
