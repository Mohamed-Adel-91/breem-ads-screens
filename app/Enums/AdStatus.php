<?php

namespace App\Enums;

enum AdStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Active = 'active';
    case Expired = 'expired';
}
