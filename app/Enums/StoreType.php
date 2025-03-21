<?php

namespace App\Enums;

enum StoreType: string
{
    case TAKEAWAY = 'takeaway';
    case SHOP = 'shop';
    case RESTAURANT = 'restaurant';
}
