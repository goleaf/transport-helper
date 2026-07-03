# Audit And Security

## Audit Required For

* import started/completed/failed;
* calculation run;
* order proposal created;
* quantity approved;
* quantity adjusted;
* quantity rejected;
* supplier order created;
* supplier order exported;
* supplier email prepared;
* supplier email approved;
* supplier email sent;
* inbound email received;
* AI extraction created;
* AI extraction reviewed;
* form autofill created;
* form autofill field accepted/edited/rejected;
* form autofill applied;
* supplier confirmation applied;
* carrier quote created;
* carrier selected;
* logistics status changed;
* goods receipt recorded;
* settings changed;
* integration credentials changed.

## Roles

Minimum roles:

* admin;
* supply_manager;
* logistics_manager;
* accountant;
* viewer.

## Security Rules

* credentials encrypted at rest;
* no secrets in git;
* no real external calls in tests;
* external AI disabled by default;
* real integrations require approval;
* private storage for attachments/exports;
* backup plan required;
* health check required.
