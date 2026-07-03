<?php

namespace App\Enums;

enum DiscrepancyType: string
{
    case QuantityLowerThanOrdered = 'quantity_lower_than_ordered';
    case QuantityHigherThanOrdered = 'quantity_higher_than_ordered';
    case UnknownSku = 'unknown_sku';
    case AmbiguousSku = 'ambiguous_sku';
    case MissingItem = 'missing_item';
    case AdditionalItem = 'additional_item';
    case MissingConfirmedQuantity = 'missing_confirmed_quantity';
    case DateMissing = 'date_missing';
    case DateChanged = 'date_changed';
    case DateConflict = 'date_conflict';
    case AmbiguousDate = 'ambiguous_date';
    case InvalidDate = 'invalid_date';
    case DelayedReadyDate = 'delayed_ready_date';
    case DelayedArrivalDate = 'delayed_arrival_date';
    case MissingSupplierOrder = 'missing_supplier_order';
    case DuplicateApplication = 'duplicate_application';
}
