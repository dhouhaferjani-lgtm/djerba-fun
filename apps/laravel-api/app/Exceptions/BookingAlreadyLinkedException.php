<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class BookingAlreadyLinkedException extends Exception
{
    public function __construct(string $message = 'This booking is already linked to an account.')
    {
        parent::__construct($message);
    }
}
