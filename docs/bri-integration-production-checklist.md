# BRI SNAP BI Integration — Production Checklist

## Scope e-Madrasah

### Incoming student payments
- BRIVA customer/VA mapping per student.
- BRIVA inquiry/payment notification endpoints must be registered with BRI during onboarding.
- QRIS MPM Dynamic can be enabled when merchantId and terminalId are issued by BRI.
- Every callback must be authenticated, idempotent, amount-validated, and mapped to exactly one receivable/payment.
- A duplicate callback must return a successful acknowledgement without posting a second payment.
- Payment completion remains the source of truth for receipt generation and parent notification.

### Payroll
- Payroll calculation remains inside e-Madrasah.
- Maker creates a payment batch; a different checker approves it.
- Only approved batches may execute transfers.
- BRI accounts use SNAP BI intrabank transfer; other banks use interbank transfer.
- Each disbursement has a stable partner reference/idempotency key.
- Never retry a timed-out transfer as a new transfer until transaction status has been inquired.
- Transaction Status Inquiry serviceCode must be the official service code supplied/confirmed for the subscribed BRI transfer product. It is intentionally configuration, never guessed in code.

## Credentials/configuration required from BRI
- Production Client ID / X-CLIENT-KEY
- Client Secret
- Partner ID / X-PARTNER-ID
- Channel ID
- Registered client public key / application private key pair
- Production base URL / subscribed product URLs
- BRIVA Partner Service ID / institution configuration
- Payroll source account registered by BRI
- Transfer product serviceCode(s) for Transaction Status Inquiry
- For QRIS: merchantId and terminalId
- Registered callback URLs and BRI callback authentication/signature requirements

## Security
- Secrets and account numbers must never be written to application logs.
- Private key must be stored outside public storage and protected by filesystem permissions.
- Bank account numbers remain encrypted at rest.
- Validate callback timestamp/signature/partner identity according to the final BRI onboarding specification.
- Maintain audit trail for configuration changes, approvals, transfer submission, inquiry, callback and reconciliation.

## Go-live gates
1. Sandbox credentials complete.
2. Token/signature test passes.
3. BRIVA inquiry/payment callback UAT passes.
4. Duplicate callback/idempotency test passes.
5. QRIS generate + inquiry test passes if QRIS is subscribed.
6. Intrabank payroll transfer UAT passes.
7. Interbank payroll transfer UAT passes if subscribed.
8. Transaction Status Inquiry UAT passes using official BRI serviceCode.
9. Timeout/unknown-state test proves no double transfer.
10. Reconciliation totals match bank results.
11. Production credentials installed without committing secrets.
12. Production feature switches remain disabled until BRI confirms activation.

## Important
A production integration is not considered complete merely because HTTP calls compile. Final endpoint availability, source account entitlement, Partner Service ID, merchant/terminal identifiers, callback registration, and transfer inquiry serviceCode are contractual/onboarding values issued or confirmed by BRI. The application must fail closed when any required value is absent.
