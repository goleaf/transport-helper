<?php

namespace App\Enums;

enum ReportRunStatus: string
{
    case Running = 'running';
    case Completed = 'completed';
    case CompletedWithWarnings = 'completed_with_warnings';
    case Failed = 'failed';
}
