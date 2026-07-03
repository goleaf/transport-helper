# Human Review And Audit Skill

Human review is required when:
- calculation has missing data;
- last year sales are missing;
- trend cannot be calculated safely;
- dates are invalid;
- AI confidence is low;
- AI finds unknown SKU;
- AI finds ambiguous date;
- supplier confirms different quantity;
- supplier adds unexpected item;
- ready date is delayed;
- carrier quote has missing price or date;
- form autofill required field is missing;
- user changes recommended quantity.

Audit log is required for:
- import started;
- import completed;
- import failed;
- calculation run;
- order proposal created;
- order quantity approved;
- order quantity adjusted;
- order quantity rejected;
- supplier order created;
- supplier order exported;
- supplier email prepared;
- supplier email approved;
- supplier email sent;
- inbound email received;
- AI extraction created;
- AI extraction accepted;
- AI extraction rejected;
- form autofill run created;
- form autofill field accepted;
- form autofill field edited;
- form autofill field rejected;
- form autofill run validated;
- form autofill run applied;
- supplier confirmation applied;
- carrier quote created;
- carrier selected;
- logistics status changed;
- settings changed;
- integration credentials changed.

Audit log should store:
- company_id;
- user_id;
- event_type;
- auditable_type;
- auditable_id;
- old values;
- new values;
- metadata;
- ip address when available;
- user agent when available;
- created_at.

Audit service must work in web requests, jobs and CLI.
