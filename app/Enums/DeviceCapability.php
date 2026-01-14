<?php

namespace App\Enums;

enum DeviceCapability: string
{
    case COUNTER = 'counter';
    case WIFI = 'wifi';
    case BLUETOOTH = 'bluetooth';
}
