<?php

namespace App\Enums;

enum OrderType: string
{
    case Package = 'package';
    case Custom = 'custom';
}
