<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class NotionColumnException extends Exception
{
    public function __construct(string $message = "Cannot add column without a column_list", int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
