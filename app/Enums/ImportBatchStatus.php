<?php

namespace App\Enums;

enum ImportBatchStatus: string
{
    case Draft = 'draft';
    case DryRun = 'dry_run';
    case Processing = 'processing';
    case Completed = 'completed';
    case CompletedWithErrors = 'completed_with_errors';
    case Failed = 'failed';
    case RolledBack = 'rolled_back';
}
