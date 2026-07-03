<?php

namespace App\Services\Supply\Analytics;

class KpiDefinitionService
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public function definitions(): array
    {
        return [
            'supplier_on_time_confirmation_rate' => $this->definition(
                'Supplier on-time confirmation rate',
                'Measures how often suppliers confirm orders before the expected confirmation window.',
                'confirmed_on_time_supplier_orders / sent_supplier_orders * 100',
                ['supplier_orders.sent_at', 'supplier_confirmations.confirmation_date'],
                ['Requires sent orders and confirmation dates. Missing dates reduce confidence.'],
                true,
            ),
            'supplier_quantity_match_rate' => $this->definition(
                'Supplier quantity match rate',
                'Measures how often confirmed quantities match ordered quantities.',
                'matched_confirmation_items / confirmation_items * 100',
                ['supplier_confirmation_items.ordered_quantity', 'supplier_confirmation_items.confirmed_quantity'],
                ['Unknown SKU and missing item rows are counted as mismatches.'],
                true,
            ),
            'average_supplier_lead_time' => $this->definition(
                'Average supplier lead time',
                'Average days from order date to ready date.',
                'average(ready_date - order_date)',
                ['logistics_records.order_date', 'logistics_records.ready_date'],
                ['Only records with both dates are included.'],
                false,
            ),
            'forecast_accuracy' => $this->definition(
                'Forecast accuracy',
                'Compares recommended or approved quantity to later actual sales.',
                '100 - abs(approved_quantity - actual_sales) / max(actual_sales, 1) * 100',
                ['order_proposal_items.approved_quantity', 'sales_history.quantity'],
                ['Actual sales must exist after the proposal coverage period; otherwise accuracy is unavailable.'],
                true,
            ),
            'order_adjustment_rate' => $this->definition(
                'Order adjustment rate',
                'Share of proposal items manually adjusted by users.',
                'adjusted_proposal_items / total_proposal_items * 100',
                ['order_proposal_items.status', 'order_proposal_items.user_adjusted_quantity'],
                ['High adjustment rate can indicate formula inputs or rules need review.'],
                false,
            ),
            'human_review_rate' => $this->definition(
                'Human review rate',
                'Share of items or suggestions requiring human review.',
                'needs_review_items / total_items * 100',
                ['requires_human_review fields', 'status fields'],
                ['Different workflow stages have different review triggers.'],
                false,
            ),
            'email_approval_cycle_time' => $this->definition(
                'Email approval cycle time',
                'Average time from email prepared to sent.',
                'average(supplier_order.sent_at - supplier_order.email_approved_at)',
                ['supplier_orders.email_approved_at', 'supplier_orders.sent_at'],
                ['Unavailable for unsent or unapproved drafts.'],
                false,
            ),
            'ai_extraction_acceptance_rate' => $this->definition(
                'AI extraction acceptance rate',
                'Share of AI extractions accepted after human review.',
                'accepted_ai_extractions / total_ai_extractions * 100',
                ['ai_email_extractions.accepted_at', 'ai_email_extractions.rejected_at'],
                ['Acceptance is not business application. AI remains advisory.'],
                true,
            ),
            'form_autofill_correction_rate' => $this->definition(
                'Form autofill correction rate',
                'Share of autofill fields where final value differs from extracted or normalized value.',
                'corrected_fields / total_fields * 100',
                ['form_autofill_field_values.extracted_value', 'form_autofill_field_values.final_value'],
                ['Requires stored field values. Low confidence fields may be intentionally edited.'],
                false,
            ),
            'carrier_quote_response_coverage' => $this->definition(
                'Carrier quote response coverage',
                'Average quote count per supplier order needing transport.',
                'carrier_quotes / supplier_orders_with_transport_need',
                ['carrier_quotes.supplier_order_id', 'supplier_orders.id'],
                ['Manual quote entry completeness affects this metric.'],
                true,
            ),
            'carrier_selection_override_rate' => $this->definition(
                'Carrier selection override rate',
                'Share of selected quotes that are not the lowest price.',
                'non_lowest_selected_quotes / selected_quotes * 100',
                ['carrier_quotes.price', 'carrier_quotes.selected_at'],
                ['Non-lowest selection can be correct when delivery date or reliability is better.'],
                false,
            ),
            'transport_on_time_rate' => $this->definition(
                'Transport on-time rate',
                'Share of logistics deliveries received on or before planned delivery date.',
                'on_time_received_records / received_records * 100',
                ['logistics_records.delivery_date', 'logistics_records.actual_received_date'],
                ['Only received records are included.'],
                true,
            ),
            'receiving_match_rate' => $this->definition(
                'Receiving match rate',
                'Share of received order items matching confirmed or ordered quantity.',
                'matched_receipt_items / received_items * 100',
                ['supplier_order_items.received_quantity', 'confirmed_quantity', 'ordered_quantity'],
                ['Confirmed quantity is not changed by receiving.'],
                true,
            ),
            'logistics_delay_rate' => $this->definition(
                'Logistics delay rate',
                'Share of open logistics records in delayed status.',
                'delayed_logistics_records / open_logistics_records * 100',
                ['logistics_records.status'],
                ['Missing dates may hide operational delays until monitored.'],
                false,
            ),
            'import_row_error_rate' => $this->definition(
                'Import row error rate',
                'Share of import rows or batch rows that failed validation.',
                'failed_rows / total_rows * 100',
                ['import_batches.total_rows', 'import_batches.failed_rows'],
                ['Dry-runs and real imports should be interpreted separately.'],
                false,
            ),
            'audit_coverage_indicator' => $this->definition(
                'Audit coverage indicator',
                'Shows whether critical workflow actions have audit events.',
                'critical_action_events_present / expected_critical_action_events * 100',
                ['audit_logs.event_type'],
                ['Detecting missing audit events is approximate without a full workflow trace.'],
                true,
            ),
            'stockout_risk_skus' => $this->definition(
                'Stockout risk SKUs',
                'Count of SKUs with critical or high replenishment risk.',
                'count(products where days_of_stock_left < lead_time_days or free_stock <= 0)',
                ['stock_snapshots.free_stock', 'sales_history.quantity', 'supplier_product_rules.lead_time_days'],
                ['No stock snapshot or sales history produces unknown risk, not a fabricated score.'],
                false,
            ),
        ];
    }

    /**
     * @param  list<string>  $requiredData
     * @param  list<string>  $limitations
     * @return array<string, mixed>
     */
    private function definition(string $name, string $description, string $formula, array $requiredData, array $limitations, bool $higherIsBetter): array
    {
        return [
            'name' => $name,
            'description' => $description,
            'formula' => $formula,
            'required_data' => $requiredData,
            'limitations' => $limitations,
            'higher_is_better' => $higherIsBetter,
        ];
    }
}
