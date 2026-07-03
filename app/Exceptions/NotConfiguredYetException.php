<?php

namespace App\Exceptions;

use RuntimeException;

class NotConfiguredYetException extends RuntimeException
{
    public static function forAdapter(string $adapter): self
    {
        return new self("Integration or adapter [{$adapter}] is not configured yet.");
    }
}
