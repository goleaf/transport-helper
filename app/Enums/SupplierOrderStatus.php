<?php

namespace App\Enums;

enum SupplierOrderStatus: string
{
    case Draft = 'draft';
    case AwaitingApproval = 'awaiting_approval';
    case Approved = 'approved';
    case EmailPrepared = 'email_prepared';
    case Sent = 'sent';
    case Confirmed = 'confirmed';
    case PartiallyConfirmed = 'partially_confirmed';
    case Delayed = 'delayed';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case NeedsReview = 'needs_review';
}
