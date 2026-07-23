# API and MCP Terms

**Document:** API AND MCP TERMS  
**Version:** 1.0 Draft  
**Status:** Draft for Legal Review  
**Proposed Effective Date:** [TO BE CONFIRMED]  
**Last Updated:** [LAST UPDATED DATE]  
**Contracting Entity:** [LINEWATT LEGAL ENTITY]  
**Contact:** [API CONTACT EMAIL]

> **Draft only. Current v1 API and MCP foundations are internal/authenticated and not a public developer service.**

## 1. Purpose and scope

These Terms govern API or MCP access expressly enabled for a trusted first-party application or under an Enterprise Order. They do not independently grant access.

## 2. Applicable definitions

Capitalised terms use [Legal Definitions](../LEGAL_DEFINITIONS.md).

## 3. Credentials, scopes, and applications

Credentials are confidential, application-specific, shown only as operationally configured, and limited to approved domains, environment, status, scopes, User context, and entitlements. The holder must rotate compromised credentials, use least privilege, and not transfer or embed secrets insecurely.

## 4. Current MCP boundary

Current MCP routes require trusted Internal App Access and the `mcp.tools` scope. The curated registry is designed for published central records; tools are presently internal/placeholders unless separately enabled. MCP must use authorised service/API layers, not direct database access, and must not expose unpublished central or tenant Private Datasets without an explicit future entitlement and identity model.

## 5. Use restrictions

The caller must comply with rate, concurrency, payload, export, caching, retention, and fair-use limits; validate automated-agent actions; preserve provenance and required attribution; and prevent tenant crossover. No unauthorised scraping, credential sharing, model or dataset reconstruction, resale, redistribution, safety-critical sole reliance, or attempts to infer hidden records are allowed.

## 6. Data, outputs, and privacy

API/MCP outputs may be mixed-rights and remain subject to the [Open Data and Export Licensing Policy](../governance/OPEN_DATA_AND_EXPORT_LICENSING_POLICY.md), Source Document rights, plan entitlements, and any Order. Caching and derived products require `[APPROVED TERMS]`. Call, scope, input-summary, response-summary, IP, user-agent, and status audit metadata may be logged; raw sensitive payloads should not be placed in summaries.

## 7. Security, support, and audit

Callers must maintain reasonable application security, report incidents, and cooperate with proportionate usage/security audits. Support applies only as ordered. Beta or placeholder tools may change and carry no production commitment.

## 8. Changes, deprecation, and suspension

LineWatt may version, change, deprecate, rate-limit, or suspend interfaces for security, breach, excessive load, law, or product evolution. Production notice periods and migration support are `[TO BE SPECIFIED IN ORDER]`.

## 9. Related documents and contact

[Acceptable Use Policy](../user/ACCEPTABLE_USE_POLICY.md); [Engineering Disclaimer](../user/ENGINEERING_DISCLAIMER.md); [Privacy Policy](../privacy/PRIVACY_POLICY.md). Contact `[API CONTACT EMAIL]`.

## 10. Revision history

| Version | Date | Change |
|---|---|---|
| 1.0 Draft | [LAST UPDATED DATE] | Aligned to internal API and MCP foundation. |
