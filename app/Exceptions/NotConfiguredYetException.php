<?php

namespace App\Exceptions;

use RuntimeException;

class NotConfiguredYetException extends RuntimeException
{
    public static function forAdapter(string $adapter): self
    {
        return new self("Import adapter [{$adapter}] is not configured yet.");
    }
}
