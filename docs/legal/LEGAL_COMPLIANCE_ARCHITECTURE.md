# Legal Compliance Architecture

**Document:** LEGAL COMPLIANCE ARCHITECTURE  
**Version:** 1.0 Draft  
**Status:** Draft for Legal Review  
**Proposed Effective Date:** [TO BE CONFIRMED]  
**Last Updated:** [LAST UPDATED DATE]

> **Internal traceability draft. It does not state or guarantee that LineWatt complies with any law. Applicability and implementation require qualified legal review and evidence.**

## 1. Purpose

This document maps areas of potentially Applicable Law to the legal documents intended to support review and governance. It supplements, but does not override, the [Legal Architecture](./LEGAL_ARCHITECTURE.md), [Legal Definitions](./LEGAL_DEFINITIONS.md), or controlling agreements.

## 2. Scope and method

2.1 Applicability depends on the contracting entity, establishment, target markets, Data Subjects, customer type, sales channel, processing, and features actually deployed.

2.2 A document may support transparency or governance without satisfying the underlying law by itself. The [Compliance Matrix](./appendices/COMPLIANCE_MATRIX.md) must identify system controls, owner, evidence, status, and open action.

## 3. Legal traceability

| Law or governance area | Supporting documents | Required review focus |
|---|---|---|
| UK GDPR | Privacy Policy; GDPR Privacy Notice; DPA; AI Processing Policy; Document Retention Policy; Security Policy | scope, roles, legal bases, rights, transfers, Processor terms, security, automated decision-making |
| EU GDPR | Privacy Policy; GDPR Privacy Notice; DPA; AI Processing Policy; Document Retention Policy; Security Policy | establishment/targeting, Articles 13/14, 22, 28, 32, 33 and 35 where applicable, transfers and representatives |
| UK Data Protection Act 2018 | Privacy Policy; GDPR Privacy Notice; DPA | UK-specific scope, exemptions, rights, enforcement, sensitive processing |
| ePrivacy and national implementations | Cookie Policy; Cookie Register; Privacy Policy | storage/access consent, communications, analytics and marketing rules |
| consumer protection | Terms of Use; Subscriber Agreement; Billing and Refund Policy | pre-contract information, fairness, renewals, digital content/services, cancellation, cooling-off and remedies |
| electronic commerce | Terms of Use; Billing and Refund Policy; Publication and Acceptance Matrix | entity information, ordering steps, electronic notices, contract formation and records |
| copyright and database rights | Copyright and Takedown Policy; IP and Licensing Policy; Manufacturer Agreement; Open Data and Export Licensing Policy | Source Document authority, database/compilation rights, exceptions, notices and exports |
| trade marks and passing off | IP and Licensing Policy; Manufacturer Agreement; Verified Manufacturer Policy | attribution, badge and brand permissions; non-endorsement |
| contract law | Terms of Use; role agreements; Enterprise Agreement; Orders; Document Precedence Matrix | authority, incorporation, fairness, precedence, termination, liability and dispute terms |
| tax and payment regulation | Billing and Refund Policy; Subscriber Agreement; Enterprise Agreement; Orders | seller identity, Paddle capacity, tax collection, invoicing and records |
| export controls and sanctions | Enterprise Agreement; API and MCP Terms | territorial scope, restricted parties, technology/data access and contractual suspension |
| security and incident governance | Security Policy; Responsible Disclosure Policy; DPA | verified controls, vulnerability intake, incidents, Personal Data Breaches and notifications |
| AI governance | AI Processing Policy; Engineering Disclaimer; Privacy Policy; DPA | provider terms, data use, human review, accuracy, Article 22 assessment and future AI regulation |
| engineering risk allocation | Engineering Disclaimer; Terms of Use; Content Review Policy | independent verification, professional responsibility, safety, corrections and non-guarantee language |
| enterprise procurement | Enterprise Agreement; DPA; SLA; API and MCP Terms | ordered scope, security schedules, service commitments, audit, exit and risk allocation |

## 4. Privacy framework relationship

The Privacy Policy provides the general Controller-facing notice. The GDPR Privacy Notice adds GDPR-specific transparency. The Cookie Policy governs browser storage and similar technologies. The DPA governs Processor commitments where executed. The AI Processing Policy describes AI-specific processing and limitations. The Document Retention Policy governs lifecycle principles. The Security Policy describes the public control framework. These documents must use consistent roles, purposes, data categories, transfers, retention, and incident terminology.

## 5. Governance and open decisions

Before approval, counsel and accountable owners must determine jurisdictional applicability, contracting entity, customer/consumer classification, Controller and Processor roles, lawful bases, transfer mechanisms, payment-provider capacity, tax model, retention, security evidence, AI-provider terms, dispute terms, and regulatory contacts. No matrix entry may be marked compliant without evidence.

## 6. Related documents

[Legal Architecture](./LEGAL_ARCHITECTURE.md); [Compliance Matrix](./appendices/COMPLIANCE_MATRIX.md); [Document Precedence Matrix](./appendices/DOCUMENT_PRECEDENCE_MATRIX.md); [Publication and Acceptance Matrix](./appendices/PUBLICATION_AND_ACCEPTANCE_MATRIX.md).

## 7. Revision history

| Version | Date | Change |
|---|---|---|
| 1.0 Draft | [LAST UPDATED DATE] | Initial cross-domain legal traceability architecture. |
