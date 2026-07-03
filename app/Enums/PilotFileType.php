<?php

namespace App\Enums;

enum PilotFileType: string
{
    case SalesHistorySample = 'sales_history_sample';
    case StockSnapshotSample = 'stock_snapshot_sample';
    case InboundOrdersSample = 'inbound_orders_sample';
    case ReservationsSample = 'reservations_sample';
    case ProductRulesSample = 'product_rules_sample';
    case ManufacturerOrderForm = 'manufacturer_order_form';
    case SupplierConfirmationEmailSample = 'supplier_confirmation_email_sample';
    case CarrierQuoteEmailSample = 'carrier_quote_email_sample';
    case LogisticsSheetSample = 'logistics_sheet_sample';
    case Other = 'other';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $type): string => $type->value, self::cases());
    }

    /**
     * @return list<string>
     */
    public static function requiredValues(): array
    {
        return [
            self::SalesHistorySample->value,
            self::StockSnapshotSample->value,
            self::ManufacturerOrderForm->value,
            self::SupplierConfirmationEmailSample->value,
            self::CarrierQuoteEmailSample->value,
        ];
    }
}
