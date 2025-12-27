<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class EmailMismatchException extends Exception
{
    public function __construct(string $message = 'This booking does not belong to your email address.')
    {
        parent::__construct($message);
    }
}
