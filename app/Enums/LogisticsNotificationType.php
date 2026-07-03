<?php

namespace App\Enums;

enum LogisticsNotificationType: string
{
    case SupplierConfirmationReceived = 'supplier_confirmation_received';
    case SupplierConfirmationNeedsReview = 'supplier_confirmation_needs_review';
    case MissingReadyDate = 'missing_ready_date';
    case DateDelay = 'date_delay';
    case QuantityMismatch = 'quantity_mismatch';
    case CarrierQuoteNeeded = 'carrier_quote_needed';
    case CarrierSelected = 'carrier_selected';
    case PickupScheduled = 'pickup_scheduled';
    case GoodsExpectedSoon = 'goods_expected_soon';
    case GoodsArrived = 'goods_arrived';
    case GoodsReceivedCompleted = 'goods_received_completed';
    case ReceivingMismatch = 'receiving_mismatch';
    case LogisticsNeedsReview = 'logistics_needs_review';
    case HealthCheckWarning = 'health_check_warning';
}
