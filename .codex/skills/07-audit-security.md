# Audit Security Skill

Every critical action must write audit log:
- import started/completed/failed
- calculation run
- order proposal created
- quantity approved
- quantity adjusted
- quantity rejected
- supplier order created
- supplier email prepared
- supplier email sent
- inbound email received
- AI extraction created
- AI extraction reviewed
- form autofill created
- form autofill field accepted/edited/rejected
- form autofill applied
- supplier confirmation applied
- carrier quote created
- carrier selected
- logistics status changed
- settings changed
- integration credentials changed

Roles:
- admin
- supply_manager
- logistics_manager
- accountant
- viewer

Security:
- credentials encrypted at rest
- policies on main models
- no secrets in git
- no hardcoded API keys
- no external AI calls unless configured
- human review for uncertainty
- backups documented
- health check command exists
