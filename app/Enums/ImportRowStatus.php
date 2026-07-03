<?php

namespace App\Enums;

enum ImportRowStatus: string
{
    case Pending = 'pending';
    case Normalized = 'normalized';
    case Valid = 'valid';
    case Invalid = 'invalid';
    case Persisted = 'persisted';
    case Failed = 'failed';
    case Skipped = 'skipped';
}
