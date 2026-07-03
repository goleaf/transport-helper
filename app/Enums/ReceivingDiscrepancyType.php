<?php

namespace App\Enums;

enum ReceivingDiscrepancyType: string
{
    case ReceivedLessThanExpected = 'received_less_than_expected';
    case ReceivedMoreThanExpected = 'received_more_than_expected';
    case MissingItem = 'missing_item';
    case UnexpectedItem = 'unexpected_item';
    case DamagedQuantity = 'damaged_quantity';
    case ReceivedWithoutConfirmation = 'received_without_confirmation';
    case DateLate = 'date_late';
    case DateMissing = 'date_missing';
}
