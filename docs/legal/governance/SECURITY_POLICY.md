# Security Policy

**Document:** SECURITY POLICY  
**Version:** 1.0 Draft  
**Status:** Draft for Legal Review  
**Proposed Effective Date:** [TO BE CONFIRMED]  
**Last Updated:** [LAST UPDATED DATE]  
**Contracting Entity:** [LINEWATT LEGAL ENTITY]  
**Contact:** [SECURITY CONTACT EMAIL]

> **Draft public overview. Controls must be verified; this document is not a certification or guarantee.**

## 1. Purpose and scope

This Policy describes security governance for Accounts, Services, engineering content, Private Datasets, and integrations without exposing sensitive implementation detail.

## 2. Applicable definitions

Capitalised terms use [Legal Definitions](../LEGAL_DEFINITIONS.md).

## 3. Control framework

LineWatt intends to apply risk-based governance; role and scope-based access; authentication and credential controls; encryption in transit and at rest where appropriate; environment and tenant separation; logging and monitoring; backup and recovery; secure development and change review; dependency and vulnerability management; provider review; incident response; business-continuity planning; and personnel confidentiality and access removal. Exact verified controls and evidence are `[TO BE CONFIRMED]`.

## 4. Private data and integrations

Private Datasets must not be exposed to public search, other tenants, Manufacturer portals, APIs, or MCP tools without an explicit authorised model. Internal application credentials use scoped access and sensitive events should generate audit evidence without unnecessary raw payloads.

## 5. Incidents and continuity

LineWatt will assess suspected incidents, contain and recover, preserve proportionate evidence, and notify affected parties and authorities when contract or law requires. Notification timing and channels depend on verified facts. Recovery objectives are `[TO BE CONFIRMED]` and are not an SLA.

## 6. Customer responsibilities and limitations

Users must protect credentials, configure access appropriately, maintain secure devices and integrations, report incidents, and independently back up exports where appropriate. No system is free from risk; this overview creates no unlisted warranty.

## 7. Contact and related documents

Report vulnerabilities under the [Responsible Disclosure Policy](./RESPONSIBLE_DISCLOSURE_POLICY.md) to `[SECURITY CONTACT EMAIL]`. See the [Data Classification](../appendices/DATA_CLASSIFICATION.md) and [Privacy Policy](../privacy/PRIVACY_POLICY.md).

## 8. Revision history

| Version | Date | Change |
|---|---|---|
| 1.0 Draft | [LAST UPDATED DATE] | Initial public security overview. |
