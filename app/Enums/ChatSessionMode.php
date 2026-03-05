<?php

namespace App\Enums;

enum ChatSessionMode: string
{
    case General = 'general';
    case JobSpecific = 'job_specific';
}
