<?php

namespace App\Enums;

enum GapClassification: string
{
    case Reframable = 'reframable';
    case Promptable = 'promptable';
    case Genuine = 'genuine';
}
