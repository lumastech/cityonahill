<?php

namespace App\Enums;

enum ApplicationStatusEnum: string
{
    case Pending   = 'pending';
    case NeedsInfo = 'needs_info';
    case Approved  = 'approved';
    case Rejected  = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending   => 'Pending Review',
            self::NeedsInfo => 'Needs More Information',
            self::Approved  => 'Approved',
            self::Rejected  => 'Rejected',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending   => 'yellow',
            self::NeedsInfo => 'orange',
            self::Approved  => 'green',
            self::Rejected  => 'red',
        };
    }
}
