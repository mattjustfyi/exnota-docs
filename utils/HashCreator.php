<?php

declare(strict_types=1);

namespace Utils;

class HashCreator
{
    public static function create(string $content, string $path): string
    {
        // See https://stackoverflow.com/a/28881708
        $size = filesize($path);
        $shaContent = 'blob '.$size."\0".$content;

        return sha1($shaContent);
    }
}
