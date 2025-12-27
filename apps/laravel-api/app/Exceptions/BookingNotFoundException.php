<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class BookingNotFoundException extends Exception
{
    public function __construct(string $message = 'Booking not found.')
    {
        parent::__construct($message);
    }
}
