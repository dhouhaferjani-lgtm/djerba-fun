<?php

declare(strict_types=1);

namespace App\Enums;

enum EmailLogStatus: string
{
    case QUEUED = 'queued';
    case SENT = 'sent';
    case DELIVERED = 'delivered';
    case OPENED = 'opened';
    case BOUNCED = 'bounced';
    case FAILED = 'failed';
    case COMPLAINED = 'complained';

    public function label(): string
    {
        return match ($this) {
            self::QUEUED => 'Queued',
            self::SENT => 'Sent',
            self::DELIVERED => 'Delivered',
            self::OPENED => 'Opened',
            self::BOUNCED => 'Bounced',
            self::FAILED => 'Failed',
            self::COMPLAINED => 'Complained',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::QUEUED => 'gray',
            self::SENT => 'info',
            self::DELIVERED => 'success',
            self::OPENED => 'success',
            self::BOUNCED => 'warning',
            self::FAILED => 'danger',
            self::COMPLAINED => 'danger',
        };
    }
}
