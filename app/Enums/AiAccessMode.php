<?php

namespace App\Enums;

enum AiAccessMode: string
{
    case Selfhosted = 'selfhosted';
    case Byok = 'byok';
    case Credits = 'credits';
}
