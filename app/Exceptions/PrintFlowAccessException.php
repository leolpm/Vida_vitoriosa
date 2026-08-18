<?php

namespace App\Exceptions;

use RuntimeException;

class PrintFlowAccessException extends RuntimeException
{
    public function __construct(string $message, public readonly int $status = 403)
    {
        parent::__construct($message);
    }
}
