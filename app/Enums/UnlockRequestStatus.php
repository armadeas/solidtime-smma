<?php

declare(strict_types=1);

namespace App\Enums;

enum UnlockRequestStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Expired = 'expired';
}

