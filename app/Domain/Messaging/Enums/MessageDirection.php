<?php

namespace App\Domain\Messaging\Enums;

enum MessageDirection: string
{
    case In = 'in';
    case Out = 'out';
}
