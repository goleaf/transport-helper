<?php

namespace App\Enums;

enum LogisticsStatus: string
{
    case Planned = 'planned';
    case OrderSent = 'order_sent';
    case Confirmed = 'confirmed';
    case WaitingForReadyDate = 'waiting_for_ready_date';
    case ReadyForPickup = 'ready_for_pickup';
    case PickupScheduled = 'pickup_scheduled';
    case InTransit = 'in_transit';
    case Delayed = 'delayed';
    case Arrived = 'arrived';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case NeedsReview = 'needs_review';
}
