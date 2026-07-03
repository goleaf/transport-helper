<?php

namespace App\Enums;

enum CarrierQuoteRequestStatus: string
{
    case Draft = 'draft';
    case Prepared = 'prepared';
    case Sent = 'sent';
    case Replied = 'replied';
    case Cancelled = 'cancelled';
}
