<?php

namespace App\Enums;

enum ChatSessionStatus: string
{
    case Active = 'active';
    case Archived = 'archived';
}
