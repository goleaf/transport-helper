# Supply Domain Skill

The system supports a full supply workflow:

1. Import sales, stock, inbound orders, reservations and supplier product rules.
2. Calculate replenishment need by SKU and supplier.
3. Show order proposal with full explanation.
4. Let user approve, adjust or reject quantities.
5. Convert approved proposal to supplier order.
6. Export supplier order to CSV/JSON/Excel-compatible format and later PDF/custom supplier form.
7. Prepare supplier email with attachments.
8. Send supplier email only after user approval.
9. Read supplier replies.
10. Extract confirmations, quantities, dates and discrepancies.
11. Autofill forms from email content.
12. Apply validated confirmations.
13. Request and compare carrier quotes.
14. Select carrier only after user confirmation.
15. Update logistics records.
16. Notify responsible users.
17. Store audit logs.

Critical human approval points:
- order quantity approval;
- user adjustment with reason;
- sending supplier email;
- accepting AI email extraction;
- applying form autofill;
- applying supplier confirmation;
- selecting carrier;
- resolving mismatch or delay.
