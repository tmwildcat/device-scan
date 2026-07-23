# Legal Architecture

**Document:** LEGAL ARCHITECTURE  
**Version:** 1.0 Draft  
**Status:** Draft for Legal Review  
**Proposed Effective Date:** [TO BE CONFIRMED]  
**Last Updated:** [LAST UPDATED DATE]

> **Internal governance draft. Not approved for publication or reliance.**

## 1. Purpose

This document governs the structure, ownership, incorporation, precedence, publication, acceptance, and change control of the LineWatt Library legal framework.

## 2. Scope and principles

2.1 The framework covers LineWatt Library, DeviceScan, the Public Library, Private Workspaces, manufacturer and publisher workflows, subscriptions, enterprise arrangements, and authorised API or MCP access.

2.2 Documents must distinguish Source Documents from structured Engineering Records, public from private data, user roles from paid entitlements, and factual data from protectable compilation, software, branding, and other rights.

2.3 Engineering assistance is not professional certification. Review, validation, approval, and verification labels never guarantee accuracy or suitability.

## 3. Document hierarchy and boundaries

3.1 Foundation documents govern vocabulary, drafting, architecture, and version control. Legal Architecture, Legal Style Guide, and Legal Versioning are internal governance documents. Legal Definitions is a public candidate when incorporated into a public agreement; unpublished internal drafting vocabulary must be maintained separately and cannot control Users' contractual rights.

3.2 Agreements create contractual duties: Terms of Use apply to all Users; role-specific agreements supplement them; an Enterprise Agreement and Order govern an Enterprise Customer.

3.3 Incorporated policies set operational rules. Privacy notices describe processing and do not create paid feature entitlements. Registers evidence decisions and controls and are internal unless expressly approved for publication.

3.4 The Terms of Use must not duplicate detailed subscription, manufacturer, enterprise, privacy, or engineering provisions. Supplemental documents incorporate common terms by link.

## 4. Applicability by role

| Role | Primary documents |
|---|---|
| Visitor | Terms of Use; Engineering Disclaimer; applicable public policies/notices |
| Registered User | Terms of Use; Registered User Agreement; applicable policies/notices |
| Subscriber | Terms of Use; Subscriber Agreement; Billing and Refund Policy; applicable policies/notices |
| Manufacturer/Publisher | Terms of Use; Manufacturer Agreement; publisher policies |
| Enterprise Customer | Enterprise Agreement, Order, DPA where applicable, SLA if purchased, API and MCP Terms if enabled |

Manufacturer onboarding is controlled or invitation-based. An Account does not make a User a Subscriber, Manufacturer, or Enterprise Customer.

## 5. Incorporation and precedence

5.1 A document is incorporated only when the controlling agreement or acceptance flow identifies it with a stable link and version.

5.2 Mandatory law prevails wherever it cannot lawfully be varied by contract. Subject to that rule, a negotiated amendment controls within its express scope. An Order controls only the Services, commercial terms, and scope it specifically covers. A DPA controls only conflicts concerning the processing or protection of Personal Data. An SLA controls only availability measurement, service levels, service credits, and related remedies. Other conflicts follow the applicable role agreement, Terms of Use, incorporated policy, and operational appendix in that order.

5.3 A specific provision controls over a general provision on the same subject. Privacy notices remain authoritative descriptions of privacy processing and cannot be displaced by a commercial Order in a manner that breaches law.

5.4 The detailed mapping is in the [Document Precedence Matrix](./appendices/DOCUMENT_PRECEDENCE_MATRIX.md).

## 6. Public and internal documents

6.1 Public candidates include Legal Definitions when incorporated into a public agreement, user agreements, public privacy notices, publisher terms, copyright, security overview, disclosure policy, and approved commercial integration terms.

6.2 Internal documents include this architecture, Legal Style Guide, Legal Versioning, compliance and retention working registers, Publication and Acceptance Matrix, and unpublished DPA/SLA schedules. A public version may be produced only after review for confidentiality and security.

## 7. Privacy and data protection architecture

7.1 The LineWatt privacy framework shall be designed to support compliance with applicable Data Protection Law, including the UK GDPR, EU GDPR, UK Data Protection Act 2018, ePrivacy rules, and applicable national implementing legislation where relevant.

7.2 The privacy architecture consists of the Privacy Policy, GDPR Privacy Notice, Cookie Policy, Data Processing Addendum, AI Processing Policy, Document Retention Policy, and Security Policy. These documents collectively describe the Platform's intended governance of Personal Data and must be interpreted consistently.

## 8. Acceptance mechanisms

8.1 Browsewrap links may provide notice for Visitors but must not replace affirmative acceptance where contract formation, payment, upload licensing, manufacturer participation, API credentials, or a Material Change requires it.

8.2 Acceptance records should include User or organisation, document identifier and version, timestamp, method, locale, and available technical evidence. Enterprise execution may occur through an Order or signature.

8.3 The [Publication and Acceptance Matrix](./appendices/PUBLICATION_AND_ACCEPTANCE_MATRIX.md) controls implementation requirements.

## 9. Versioning, publication, and notice

9.1 All documents follow [Legal Versioning](./LEGAL_VERSIONING.md). Draft documents have no operative Effective Date and use a Proposed Effective Date placeholder.

9.2 Publication requires approval by the legal owner and relevant product, privacy, security, finance, or engineering owner; resolution of release-blocking placeholders; archived prior text; updated matrices; and link validation.

9.3 Material Changes require advance or contemporaneous notice as law and contract require and may require re-acceptance. Non-material corrections may be notified through a changelog.

## 10. Ownership and review

10.1 [LEGAL DOCUMENTATION OWNER] administers the framework. [QUALIFIED LEGAL COUNSEL] approves legal substance. Product owners confirm feature accuracy; engineering and security confirm technical statements; privacy confirms data maps; finance confirms billing.

10.2 No contributor may mark a document Approved or Published without recorded authority. Marketing wording does not override this framework.

## 11. Related documents

[Legal Definitions](./LEGAL_DEFINITIONS.md), [Legal Style Guide](./LEGAL_STYLE_GUIDE.md), [Legal Versioning](./LEGAL_VERSIONING.md), [Compliance Matrix](./appendices/COMPLIANCE_MATRIX.md), and [Legal Compliance Architecture](./LEGAL_COMPLIANCE_ARCHITECTURE.md).

## 12. Operational lifecycle

12.1 The platform represents Draft, In Review, Changes Requested, Approved, Scheduled, Published, Superseded, Withdrawn, and Archived states as distinct lifecycle states.

12.2 Legal Publisher may author and submit but cannot make legal-review decisions or perform publication transitions. Legal Counsel may author and self-approve. Super Administrator acts through the existing platform override.

12.3 Submission, approval, and publication are separate checksum-bound and audited actions. Approved content and governed metadata are immutable until a governed Return to Draft invalidates current approval evidence.

12.4 Only a valid Published and effective version may resolve publicly or satisfy a new acceptance requirement. Withdrawal removes the version from public resolution and may cause protected workflows to fail closed.

## 13. Revision history

| Version | Date | Change |
|---|---|---|
| 1.0 Draft | [LAST UPDATED DATE] | Reconciled architecture and governance model. |
| 1.0 Draft | 2026-07-22 | Documented operational authoring, approval, publication, withdrawal, and acceptance lifecycle. |
