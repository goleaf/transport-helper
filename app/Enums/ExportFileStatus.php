<?php

namespace App\Enums;

enum ExportFileStatus: string
{
    case Created = 'created';
    case Stored = 'stored';
    case Failed = 'failed';
    case Downloaded = 'downloaded';
    case Sent = 'sent';
}
