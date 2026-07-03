# Logistics Workflow

## Purpose

The logistics workflow tracks supplier orders from order creation to final goods receipt.

## Logistics Record

A logistics record stores:

- supplier;
- supplier order;
- order date;
- confirmation date;
- ready date;
- pickup date;
- delivery date;
- actual received date;
- carrier;
- transport price;
- currency;
- status;
- notes.

## Statuses

- planned
- order_sent
- confirmed
- waiting_for_ready_date
- ready_for_pickup
- pickup_scheduled
- in_transit
- delayed
- arrived
- completed
- cancelled
- needs_review

## Receiving

Receiving records actual received quantities. The system compares received quantities with confirmed quantities first, then ordered quantities when no confirmation exists.

Mismatches:

- received less than expected;
- received more than expected;
- missing item;
- unexpected item;
- damaged quantity.

Receiving updates `received_quantity` on supplier order items and linked inbound order items. It does not update `confirmed_quantity`.

## Delay Monitoring

The system checks:

- delivery date passed without receipt;
- ready date passed without pickup;
- missing ready date;
- pickup date passed;
- goods expected soon.

The monitoring command supports dry-run mode and creates database notifications without sending external email.

## Notifications

The system notifies responsible users about:

- delays;
- expected arrivals;
- receiving mismatches;
- missing data;
- actions requiring review.

Notifications are database-only in this workflow.

## Export

Logistics records can be exported to CSV and stored privately as `ExportFile` records. Google Sheets sync is a placeholder until configured.
