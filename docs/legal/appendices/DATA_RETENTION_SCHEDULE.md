# Data Retention Schedule

**Document:** DATA RETENTION SCHEDULE  
**Version:** 1.0 Draft  
**Status:** Draft for Legal Review  
**Last Updated:** [LAST UPDATED DATE]

> **Operational template. Placeholder periods are not approved retention authority.**

## 1. Purpose and scope

This schedule implements the Document Retention Policy. Periods require legal, privacy, security, finance, support, and engineering confirmation.

## 2. Schedule

| Record | Trigger | Active/retention period | Rationale | Disposal/exception | Owner |
|---|---|---|---|---|---|
| active Account data | Account creation | while active + `[PERIOD]` | contract/security | delete or de-identify; legal hold | privacy/product |
| closed Account data | closure | `[PERIOD]` | claims/fraud | staged deletion | privacy |
| Private Datasets and uploaded PDFs | deletion/closure/Subscription end | export window `[PERIOD]`; deletion `[PERIOD]` | service/exit | backups and holds | product/privacy |
| public Engineering Records | withdrawal/replacement | `[PERIOD OR EVENT-BASED]` | provenance/safety | unpublish source; retain history where lawful | library governance |
| compilation artefacts | job completion | `[PERIOD]` | support/debug | minimise intermediate data | engineering |
| backups | backup creation | `[BACKUP CYCLE]` | resilience | isolated expiry; no ordinary reuse | security |
| billing/tax records | transaction/fiscal close | `[STATUTORY PERIOD]` | finance law | restricted archive | finance |
| security and API/MCP audit logs | event | `[PERIOD]` | security/abuse | avoid raw payloads | security |
| consent/acceptance records | acceptance/withdrawal | `[LEGAL RECORD PERIOD]` | evidence | legal hold | legal/privacy |
| support tickets | closure | `[PERIOD]` | support/claims | redact unnecessary uploads | support |
| Manufacturer Content | withdrawal/termination | `[PERIOD]` | provenance/rights/safety | licence survival limits apply | publisher/legal |
| legal holds and disputes | hold issued | until release + `[PERIOD]` | legal obligation | controlled release | legal |

## 3. Related documents

[Document Retention Policy](../privacy/DOCUMENT_RETENTION_POLICY.md); [Data Classification](./DATA_CLASSIFICATION.md).

## 4. Revision history

| Version | Date | Change |
|---|---|---|
| 1.0 Draft | [LAST UPDATED DATE] | Initial schedule template. |
