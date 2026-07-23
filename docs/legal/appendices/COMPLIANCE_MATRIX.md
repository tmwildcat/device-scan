# Compliance Matrix

**Document:** COMPLIANCE MATRIX  
**Version:** 1.0 Draft  
**Status:** Draft for Legal Review  
**Last Updated:** [LAST UPDATED DATE]

> **Internal evidence tracker. “Drafted” does not mean compliant.**

## 1. Purpose and scope

This matrix maps requirements to documents, systems, ownership, evidence, and open action.

## 2. Matrix

| Applicable Law / requirement | Document | System control | Owner | Evidence | Status/open action |
|---|---|---|---|---|---|
| UK GDPR | Privacy Policy; GDPR Privacy Notice; DPA; AI Policy; Security Policy | data inventory, rights, security, Processor controls | privacy/security | `[EVIDENCE]` | applicability and evidence open |
| EU GDPR | Privacy Policy; GDPR Privacy Notice; DPA; AI Policy; Security Policy | data inventory, rights, transfers, Processor controls | privacy/security | `[EVIDENCE]` | applicability and evidence open |
| UK Data Protection Act 2018 | Privacy Policy; GDPR Privacy Notice; DPA | UK legal-basis and exemption review | privacy/legal | `[EVIDENCE]` | open |
| ePrivacy and national implementations | Cookie Policy; Cookie Register | consent manager and verified scan | privacy/engineering | `[SCAN/CONFIG]` | open |
| consumer legislation | Terms; Subscriber Agreement; Billing and Refund Policy | checkout, cancellation, cooling-off and complaints | legal/finance/product | `[EVIDENCE]` | jurisdiction and implementation open |
| electronic commerce legislation | Terms; Privacy Policy; checkout notices | entity disclosures, ordering and electronic notices | legal/product | `[EVIDENCE]` | jurisdiction and implementation open |
| copyright and database rights | Copyright Policy; IP and Licensing Policy; Manufacturer Agreement | provenance, licence and takedown workflow | legal/library | `[EVIDENCE]` | rights analysis open |
| trade marks | IP and Licensing Policy; Manufacturer Agreement | brand permissions and attribution | legal/publisher | `[EVIDENCE]` | open |
| contract law | Terms and role/Enterprise agreements | notice, authority and versioned acceptance | legal/product | `[EVIDENCE]` | governing law open |
| tax | Billing Policy; Orders | Paddle/finance tax configuration and records | finance/legal | `[PADDLE CONTRACT/CONFIG]` | open |
| export controls and sanctions | Enterprise Agreement; API and MCP Terms | screening/restriction process `[IF APPLICABLE]` | legal/commercial | `[EVIDENCE]` | applicability open |
| terms notice and versioned acceptance | Terms; Publication and Acceptance Matrix | registration/checkout acceptance log | legal/product | `[EVIDENCE]` | open: implement/verify |
| private tenant isolation | Terms; Privacy; Classification | ownership/tenant authorisation | engineering/security | route/service tests `[CONFIRM]` | design evidenced; control audit open |
| manufacturer controlled onboarding | Manufacturer Agreement | invitation-token route and role checks | publisher/product | repository routes | product evidence; legal flow open |
| engineering status integrity | Review Policy | separate lifecycle/review/validation fields | engineering/library | status model/code `[CONFIRM]` | documented; implementation audit open |
| Processor obligations | DPA | TOMs/subprocessor controls | privacy/security | `[EVIDENCE]` | open |
| AI provider/data-use governance | AI Policy | approved provider/configuration | privacy/engineering | `[VENDOR TERMS]` | open |
| payment transparency | Billing Policy | Paddle checkout/webhooks | finance/product | `[PADDLE CONTRACT/CONFIG]` | open |
| takedown process | Copyright Policy | case intake/restriction/audit | legal/library | `[RUNBOOK]` | open |
| API/MCP scope and logging | API/MCP Terms | internal credentials, scopes, audit logs | security/engineering | routes/docs | foundation evidenced; execution controls open |
| retention/deletion | Retention Policy/Schedule | jobs, backups, holds | privacy/security | `[EVIDENCE]` | open |
| security governance | Security Policy | verified TOM inventory | security | `[CONTROL EVIDENCE]` | open |
| Enterprise SLA | SLA | monitoring/support | operations | `[EVIDENCE]` | no commitment approved |

## 3. Consolidated legal decisions

Open decisions include `[LINEWATT LEGAL ENTITY]`, office/registration/VAT, governing law/courts/disputes, age, contacts/DPO/representative/authority, Paddle capacity/tax/refunds, vendors/cookies/transfers, retention, SLA/support/credits, liability/indemnities/insurance, Enterprise exit, and publication/acceptance implementation.

## 4. Related documents

[README](../README.md); [Publication and Acceptance Matrix](./PUBLICATION_AND_ACCEPTANCE_MATRIX.md).

## 5. Revision history

| Version | Date | Change |
|---|---|---|
| 1.0 Draft | [LAST UPDATED DATE] | Initial evidence-based matrix. |
