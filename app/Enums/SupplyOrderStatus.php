<?php

namespace App\Enums;

enum SupplyOrderStatus: string
{
    case Draft = 'draft';
    case EmailQueued = 'email_queued';
    case FormReady = 'form_ready';
    case Submitted = 'submitted';
    case Confirmed = 'confirmed';
    case LogisticsPlanned = 'logistics_planned';
}
