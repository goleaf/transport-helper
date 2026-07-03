# Transport And Logistics Skill

Transport workflow:
1. Supplier order is confirmed or ready for transport.
2. System prepares carrier quote request.
3. Carrier replies are entered manually or extracted from email.
4. Carrier quote is stored.
5. Quote is scored by price, pickup date, delivery date and reliability.
6. Lowest price must not automatically win if dates are bad.
7. User selects carrier.
8. Selection writes audit log.
9. Logistics record is updated.

Carrier quote scoring inputs:
- price;
- currency;
- pickup_date;
- delivery_date;
- transit_days;
- reliability_score;
- conditions;
- required_pickup_date;
- required_delivery_date.

Default scoring:
- price_weight = 0.40;
- delivery_date_weight = 0.30;
- pickup_date_weight = 0.10;
- reliability_weight = 0.20;
- penalty_late_pickup = 20;
- penalty_late_delivery = 40;
- penalty_missing_price = 50;
- penalty_missing_date = 50.

Logistics record tracks:
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

Logistics statuses:
- planned;
- order_sent;
- confirmed;
- waiting_for_ready_date;
- ready_for_pickup;
- pickup_scheduled;
- in_transit;
- delayed;
- arrived;
- completed;
- cancelled;
- needs_review.

Notifications:
- order prepared;
- supplier confirmation received;
- missing ready date;
- date delay;
- quantity mismatch;
- carrier quote needed;
- carrier selected;
- goods expected soon;
- goods arrived;
- import failed;
- AI extraction needs review;
- form autofill needs review;
- form autofill applied.
