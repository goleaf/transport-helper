# Global Rules

This repository uses strict agent rules.

Laravel owns business logic.
AI is only an assistant for email/text/form extraction and draft replies.
AI must never mutate business records directly.

Never create DTO classes.
Never create app/Data.
Never call real external services in tests.
Never commit secrets.
Never claim success without tests/checks.

Every future task must be implemented through:

* task file;
* progress checklist;
* tests;
* guard scripts;
* final report;
* commit;
* push attempt.
