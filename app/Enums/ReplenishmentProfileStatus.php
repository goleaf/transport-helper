<?php

namespace App\Enums;

enum ReplenishmentProfileStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Archived = 'archived';
}
