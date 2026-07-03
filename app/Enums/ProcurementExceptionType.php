<?php

namespace App\Enums;

enum ProcurementExceptionType: string
{
    case BudgetOverrun = 'budget_overrun';
    case MissingPrice = 'missing_price';
    case SupplierMinimumNotMet = 'supplier_minimum_not_met';
    case SupplierMaximumExceeded = 'supplier_maximum_exceeded';
    case OrderFrequencyViolation = 'order_frequency_violation';
    case UrgentPurchase = 'urgent_purchase';
    case ManualOverride = 'manual_override';
    case Other = 'other';
}
