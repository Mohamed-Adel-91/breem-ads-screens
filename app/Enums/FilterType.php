<?php

namespace App\Enums;

enum FilterType: int
{
    case EXACT = 1;
    case DATE_FROM = 2;
    case DATE_TO = 3;
    case RELATED = 4;
    case LIKE = 5;
    case RANGE = 6;
}
