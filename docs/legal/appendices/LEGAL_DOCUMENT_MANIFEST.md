# Legal Document Manifest

**Document:** LEGAL DOCUMENT MANIFEST  
**Version:** 1.0 Draft  
**Status:** Draft for Legal Review  
**Last Updated:** [LAST UPDATED DATE]

> **Import manifest only. Every listed source remains a Draft and must never be automatically published.**

## 1. Purpose

This manifest is the deliberate import boundary between the repository legal corpus and the Legal Governance Platform database.

## 2. Import rules

- Import only entries marked `yes`.
- Preserve repository-relative source paths.
- Create or reconcile Draft versions; never mutate a Published version.
- Treat `internal`, `restricted`, and `confidential` entries as ineligible for the public portal.
- Treat every unresolved square-bracket placeholder as release-blocking unless configuration explicitly permits that runtime variable.

## 3. Documents

| Application key | Slug | Title | Source path | Type | Category | Visibility | Default acceptance | Import |
|---|---|---|---|---|---|---|---|---|
| linewatt-library | legal-definitions | Legal Definitions | `docs/legal/LEGAL_DEFINITIONS.md` | appendix | foundation | public | acknowledgement | yes |
| linewatt-library | website-terms-of-use | Website Terms of Use | `docs/legal/user/WEBSITE_TERMS_OF_USE.md` | agreement | user | public | no_acceptance_required | yes |
| linewatt-library | terms-of-use | Terms of Use | `docs/legal/user/TERMS_OF_USE.md` | agreement | user | public | clickwrap_acceptance | yes |
| linewatt-library | registered-user-agreement | Registered User Agreement | `docs/legal/user/REGISTERED_USER_AGREEMENT.md` | agreement | user | authenticated | clickwrap_acceptance | yes |
| linewatt-library | subscriber-agreement | Subscriber Agreement | `docs/legal/user/SUBSCRIBER_AGREEMENT.md` | agreement | user | authenticated | clickwrap_acceptance | yes |
| linewatt-library | acceptable-use-policy | Acceptable Use Policy | `docs/legal/user/ACCEPTABLE_USE_POLICY.md` | policy | user | public | acknowledgement | yes |
| linewatt-library | engineering-disclaimer | Engineering Disclaimer | `docs/legal/user/ENGINEERING_DISCLAIMER.md` | disclosure | user | public | acknowledgement | yes |
| linewatt-library | billing-and-refund-policy | Billing and Refund Policy | `docs/legal/user/BILLING_AND_REFUND_POLICY.md` | policy | user | public | acknowledgement | yes |
| linewatt-library | privacy-policy | Privacy Policy | `docs/legal/privacy/PRIVACY_POLICY.md` | notice | privacy | public | acknowledgement | yes |
| linewatt-library | cookie-policy | Cookie Policy | `docs/legal/privacy/COOKIE_POLICY.md` | notice | privacy | public | acknowledgement | yes |
| linewatt-library | gdpr-privacy-notice | GDPR Privacy Notice | `docs/legal/privacy/GDPR_PRIVACY_NOTICE.md` | notice | privacy | public | acknowledgement | yes |
| linewatt-library | data-processing-addendum | Data Processing Addendum | `docs/legal/privacy/DATA_PROCESSING_ADDENDUM.md` | dpa | privacy | restricted | organisation_execution | yes |
| linewatt-library | ai-processing-policy | AI Processing Policy | `docs/legal/privacy/AI_PROCESSING_POLICY.md` | policy | privacy | public | acknowledgement | yes |
| linewatt-library | document-retention-policy | Document Retention Policy | `docs/legal/privacy/DOCUMENT_RETENTION_POLICY.md` | policy | privacy | internal | acknowledgement | yes |
| linewatt-library | manufacturer-agreement | Manufacturer Agreement | `docs/legal/publisher/MANUFACTURER_AGREEMENT.md` | agreement | publisher | restricted | organisation_execution | yes |
| linewatt-library | publisher-content-policy | Publisher Content Policy | `docs/legal/publisher/PUBLISHER_CONTENT_POLICY.md` | policy | publisher | authenticated | acknowledgement | yes |
| linewatt-library | verified-manufacturer-policy | Verified Manufacturer Policy | `docs/legal/publisher/VERIFIED_MANUFACTURER_POLICY.md` | policy | publisher | public | acknowledgement | yes |
| linewatt-library | content-review-policy | Content Review Policy | `docs/legal/publisher/CONTENT_REVIEW_POLICY.md` | policy | publisher | public | acknowledgement | yes |
| linewatt-library | enterprise-agreement | Enterprise Agreement | `docs/legal/enterprise/ENTERPRISE_AGREEMENT.md` | agreement | enterprise | restricted | organisation_execution | yes |
| linewatt-library | service-level-agreement | Service Level Agreement | `docs/legal/enterprise/SERVICE_LEVEL_AGREEMENT.md` | sla | enterprise | restricted | organisation_execution | yes |
| linewatt-library | api-and-mcp-terms | API and MCP Terms | `docs/legal/enterprise/API_AND_MCP_TERMS.md` | agreement | integration | authenticated | clickwrap_acceptance | yes |
| linewatt-library | copyright-and-takedown-policy | Copyright and Takedown Policy | `docs/legal/governance/COPYRIGHT_AND_TAKEDOWN_POLICY.md` | policy | governance | public | acknowledgement | yes |
| linewatt-library | intellectual-property-and-licensing-policy | Intellectual Property and Licensing Policy | `docs/legal/governance/INTELLECTUAL_PROPERTY_AND_LICENSING_POLICY.md` | policy | governance | public | acknowledgement | yes |
| linewatt-library | open-data-and-export-licensing-policy | Open Data and Export Licensing Policy | `docs/legal/governance/OPEN_DATA_AND_EXPORT_LICENSING_POLICY.md` | policy | governance | public | acknowledgement | yes |
| linewatt-library | security-policy | Security Policy | `docs/legal/governance/SECURITY_POLICY.md` | policy | governance | public | acknowledgement | yes |
| linewatt-library | responsible-disclosure-policy | Responsible Disclosure Policy | `docs/legal/governance/RESPONSIBLE_DISCLOSURE_POLICY.md` | policy | governance | public | acknowledgement | yes |
| linewatt-library | legal-architecture | Legal Architecture | `docs/legal/LEGAL_ARCHITECTURE.md` | governance | foundation | internal | internal_only | yes |
| linewatt-library | legal-compliance-architecture | Legal Compliance Architecture | `docs/legal/LEGAL_COMPLIANCE_ARCHITECTURE.md` | governance | foundation | internal | internal_only | yes |
| linewatt-library | legal-style-guide | Legal Style Guide | `docs/legal/LEGAL_STYLE_GUIDE.md` | governance | foundation | internal | internal_only | yes |
| linewatt-library | legal-versioning | Legal Versioning | `docs/legal/LEGAL_VERSIONING.md` | governance | foundation | internal | internal_only | yes |
| linewatt-library | legal-framework-changelog | Legal Framework Changelog | `docs/legal/appendices/CHANGELOG.md` | register | governance | internal | internal_only | yes |
| linewatt-library | compliance-matrix | Compliance Matrix | `docs/legal/appendices/COMPLIANCE_MATRIX.md` | register | compliance | internal | internal_only | yes |
| linewatt-library | cookie-register | Cookie Register | `docs/legal/appendices/COOKIE_REGISTER.md` | register | privacy | internal | internal_only | yes |
| linewatt-library | data-classification | Data Classification | `docs/legal/appendices/DATA_CLASSIFICATION.md` | standard | security | internal | internal_only | yes |
| linewatt-library | data-retention-schedule | Data Retention Schedule | `docs/legal/appendices/DATA_RETENTION_SCHEDULE.md` | register | privacy | internal | internal_only | yes |
| linewatt-library | document-precedence-matrix | Document Precedence Matrix | `docs/legal/appendices/DOCUMENT_PRECEDENCE_MATRIX.md` | register | governance | internal | internal_only | yes |
| linewatt-library | publication-and-acceptance-matrix | Publication and Acceptance Matrix | `docs/legal/appendices/PUBLICATION_AND_ACCEPTANCE_MATRIX.md` | register | governance | internal | internal_only | yes |
| linewatt-library | subprocessor-register | Subprocessor Register | `docs/legal/appendices/SUBPROCESSORS.md` | register | privacy | internal | internal_only | yes |
| linewatt-library | legal-version-history | Version History | `docs/legal/appendices/VERSION_HISTORY.md` | register | governance | internal | internal_only | yes |

## 4. Required operational metadata

Every row above has the following controlled import metadata. Application-specific workflow requirements may narrow these defaults but must not broaden visibility.

| Field | Rule |
|---|---|
| `slug`, `title`, `relative_path`, `category`, `visibility`, `document_type`, `acceptance_type` | values are the corresponding row fields above |
| `audience` | `public` for public documents; `authenticated` for authenticated documents; the applicable contracting organisation for restricted agreements; `legal_governance` for internal documents |
| `requires_acceptance` | yes for clickwrap, organisation execution, or electronic signature; no for acknowledgement or internal-only records unless a workflow expressly requires acknowledgement |
| `workflow_triggers` | assigned only through a validated workflow; no document becomes operative through this manifest |
| `requires_reacceptance_on_material_change` | yes only where the Publication and Acceptance Matrix and approved workflow require it |
| `publication_status` | `draft` for every entry |
| `contains_placeholders` | determined by the placeholder scanner at import |
| `release_blocking_placeholders` | every unresolved square-bracket placeholder unless explicitly allow-listed |
| `related_documents` | repository-relative links in the source document |
| `precedence_level` | foundation/register, policy/notice, role agreement, or negotiated agreement as defined by the Document Precedence Matrix |
| `legal_review_status` | `pending` for every entry |

## 5. Per-document workflow metadata

`source-links` means the importer derives `related_documents` only from validated relative links in that source. `scan` means placeholder values are computed from source content; every unresolved value is release-blocking unless expressly allow-listed after legal review. An empty trigger (`none`) creates no acceptance workflow.

| Slug | Audience | Requires acceptance | Workflow triggers | Re-accept on Material Change | Publication status | Contains placeholders | Release blockers | Related documents | Precedence | Legal review |
|---|---|---:|---|---:|---|---|---|---|---|---|
| legal-definitions | public | no | none | no | draft | scan | scan | source-links | foundation | pending |
| website-terms-of-use | visitors | no | none | no | draft | scan | scan | source-links | public terms | pending |
| terms-of-use | visitors, registered users | yes | registration, material_change | yes | draft | scan | scan | source-links | role agreement | pending |
| registered-user-agreement | registered users | yes | registration, material_change | yes | draft | scan | scan | source-links | role agreement | pending |
| subscriber-agreement | subscribers | yes | subscriber_checkout, subscriber_upgrade, material_change | yes | draft | scan | scan | source-links | role agreement | pending |
| acceptable-use-policy | users | no | none | no | draft | scan | scan | source-links | policy | pending |
| engineering-disclaimer | visitors, users | no | none | no | draft | scan | scan | source-links | notice | pending |
| billing-and-refund-policy | subscribers | no | none | no | draft | scan | scan | source-links | policy | pending |
| privacy-policy | visitors, users | no | registration, material_change | no | draft | scan | scan | source-links | notice | pending |
| cookie-policy | visitors | no | cookie_preference | no | draft | scan | scan | source-links | notice | pending |
| gdpr-privacy-notice | data subjects | no | registration, material_change | no | draft | scan | scan | source-links | notice | pending |
| data-processing-addendum | enterprise customers | yes | enterprise_onboarding, order_execution | yes | draft | scan | scan | source-links | negotiated agreement | pending |
| ai-processing-policy | users | no | none | no | draft | scan | scan | source-links | policy | pending |
| document-retention-policy | legal governance | no | none | no | draft | scan | scan | source-links | internal policy | pending |
| manufacturer-agreement | manufacturers | yes | manufacturer_activation, material_change | yes | draft | scan | scan | source-links | role agreement | pending |
| publisher-content-policy | publishers | no | publisher_submission | no | draft | scan | scan | source-links | policy | pending |
| verified-manufacturer-policy | manufacturers | no | none | no | draft | scan | scan | source-links | policy | pending |
| content-review-policy | publishers | no | publisher_submission | no | draft | scan | scan | source-links | policy | pending |
| enterprise-agreement | enterprise customers | yes | enterprise_onboarding, order_execution | yes | draft | scan | scan | source-links | negotiated agreement | pending |
| service-level-agreement | enterprise customers | yes | order_execution | yes | draft | scan | scan | source-links | negotiated agreement | pending |
| api-and-mcp-terms | API users, MCP users | yes | api_credential_issuance, mcp_access, material_change | yes | draft | scan | scan | source-links | role agreement | pending |
| copyright-and-takedown-policy | public | no | none | no | draft | scan | scan | source-links | policy | pending |
| intellectual-property-and-licensing-policy | public | no | none | no | draft | scan | scan | source-links | policy | pending |
| open-data-and-export-licensing-policy | public | no | none | no | draft | scan | scan | source-links | policy | pending |
| security-policy | public | no | none | no | draft | scan | scan | source-links | policy | pending |
| responsible-disclosure-policy | public | no | none | no | draft | scan | scan | source-links | policy | pending |
| legal-architecture | legal governance | no | none | no | draft | scan | scan | source-links | foundation | pending |
| legal-compliance-architecture | legal governance | no | none | no | draft | scan | scan | source-links | foundation | pending |
| legal-style-guide | legal governance | no | none | no | draft | scan | scan | source-links | foundation | pending |
| legal-versioning | legal governance | no | none | no | draft | scan | scan | source-links | foundation | pending |
| legal-framework-changelog | legal governance | no | none | no | draft | scan | scan | source-links | register | pending |
| compliance-matrix | legal governance | no | none | no | draft | scan | scan | source-links | register | pending |
| cookie-register | legal governance | no | none | no | draft | scan | scan | source-links | register | pending |
| data-classification | legal governance | no | none | no | draft | scan | scan | source-links | internal standard | pending |
| data-retention-schedule | legal governance | no | none | no | draft | scan | scan | source-links | register | pending |
| document-precedence-matrix | legal governance | no | none | no | draft | scan | scan | source-links | register | pending |
| publication-and-acceptance-matrix | legal governance | no | none | no | draft | scan | scan | source-links | register | pending |
| subprocessor-register | legal governance | no | none | no | draft | scan | scan | source-links | register | pending |
| legal-version-history | legal governance | no | none | no | draft | scan | scan | source-links | register | pending |

## 6. Revision history

| Version | Date | Change |
|---|---|---|
| 1.0 Draft | [LAST UPDATED DATE] | Initial deliberate-import manifest. |
