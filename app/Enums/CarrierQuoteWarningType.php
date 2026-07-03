<?php

namespace App\Enums;

enum CarrierQuoteWarningType: string
{
    case MissingPrice = 'missing_price';
    case MissingCurrency = 'missing_currency';
    case MissingDeliveryDate = 'missing_delivery_date';
    case InvalidDateOrder = 'invalid_date_order';
    case LatePickup = 'late_pickup';
    case LateDelivery = 'late_delivery';
    case UnknownCarrier = 'unknown_carrier';
    case ZeroPrice = 'zero_price';
    case NeedsReviewSource = 'needs_review_source';
    case LowConfidenceSource = 'low_confidence_source';
}
