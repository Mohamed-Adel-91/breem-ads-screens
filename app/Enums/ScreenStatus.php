<?php

namespace App\Enums;

enum ScreenStatus: string
{
    case Online = 'online';
    case Offline = 'offline';
    case Maintenance = 'maintenance';
}
