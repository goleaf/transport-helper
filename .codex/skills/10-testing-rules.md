# Testing Rules Skill

Every module must include tests.

Tests must not:
- call real AI providers;
- send real emails;
- call real external APIs;
- depend on production data;
- require real credentials.

AI contracts must be mocked in tests.

Required tests:
- migrations work;
- seeders work;
- relationships work;
- calculation 150 -> 156 works;
- calculation has no AI dependency;
- raw need negative returns zero;
- MOQ is applied correctly;
- pack multiple is applied correctly;
- pallet show_only does not change quantity;
- enforce_full_pallet changes quantity;
- CSV import works;
- dry run does not persist domain records;
- order proposal approval works;
- adjustment requires reason;
- supplier order export works;
- supplier email requires approval before send;
- manual inbound email can be stored;
- AI extraction low confidence requires review;
- AI extraction does not mutate business records directly;
- email form autofill run is created;
- source excerpts are stored;
- low confidence autofill field requires review;
- missing required autofill field requires review;
- user can edit autofill field;
- editing autofill field writes audit log;
- validated autofill can create supplier confirmation;
- validated autofill can create carrier quote;
- rejected autofill run does not mutate records;
- carrier quote scoring works;
- carrier selection requires user confirmation;
- logistics updates after confirmation and carrier selection;
- permissions work;
- audit logs are written;
- no DTO classes exist;
- app/Data directory does not exist.

Before commit:
- run tests;
- run formatter if configured;
- verify no secrets;
- verify no DTOs.
