<?php

namespace App\Enums;

enum ProductLifecycleStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Blocked = 'blocked';
    case Discontinued = 'discontinued';
    case Replaced = 'replaced';
    case Merged = 'merged';
    case Archived = 'archived';
}
