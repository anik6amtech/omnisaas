<?php

namespace App\Domain\Messaging\Enums;

enum MessageAuthor: string
{
    case Customer = 'customer';
    case Ai = 'ai';
    case Agent = 'agent';
}
