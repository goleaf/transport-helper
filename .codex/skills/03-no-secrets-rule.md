# No Secrets Rule

Never commit:

* .env
* real API keys
* SMTP passwords
* OAuth client secrets
* refresh tokens
* private keys
* real supplier files
* real customer data
* real email samples
* generated exports
* email attachments
* backup archives

Use placeholders only:

* your-key-here
* changeme
* example
* null

Before commit:

* run ./scripts/check-no-secrets.sh
* run git status
* review git diff --stat
