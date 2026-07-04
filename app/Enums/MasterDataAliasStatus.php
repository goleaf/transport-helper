<?php

namespace App\Enums;

enum MasterDataAliasStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Inactive = 'inactive';
    case Rejected = 'rejected';
    case Archived = 'archived';
}
