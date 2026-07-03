# Approval Thresholds

Approval thresholds are policy rules that require manager or admin sign-off when procurement risk is higher.

Supported threshold sources:

- order value;
- supplier-specific value;
- category or product value;
- missing price;
- budget overrun;
- supplier risk or exception type when represented in policy rules.

Approval requests are explicit records. They include subject, requester, amount, currency, required role or permission, reason, status and decision history.

## Self Approval

Requester self-approval is disabled by default. It is allowed only when `SUPPLY_PROCUREMENT_ALLOW_SELF_APPROVAL=true` and the user has admin authority.

Rejected approvals do not satisfy enforced gates.
