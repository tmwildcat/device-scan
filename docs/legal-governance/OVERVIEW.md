# Legal Governance Platform Overview

## Purpose

The Legal Governance Platform is a bounded Laravel context for legal document identities, versioned authoring, review, publication, workflow requirements, acceptance evidence, manifests, audit, and public delivery.

## Boundaries

- **Document Management:** documents, editable Draft versions, rendering, placeholders, reconciliation, artefacts.
- **Workflow and Acceptance:** declarative triggers, requirements, obligations, exact-version legal actions.
- **Evidence:** append-only acceptances, frozen artefacts, canonical manifests, checksums, exports.
- **Audit:** allow-listed append-only legal events.
- **Portals:** public approved documents and permission-scoped Legal Counsel administration.

The database is the operational authoring source after deliberate import. Repository Markdown remains an import/export source and is never automatically published.

## Dashboard access

`legal-governance.dashboard` (`/admin/legal-governance`) is the verified Legal Counsel landing page. Super Administrators retain `/admin/platform` as their higher-precedence landing page and enter Legal Governance from the Platform navigation. The dashboard requires `legal.dashboard.view`; it reports stored governance records without exposing acceptance evidence payloads.

## Current status

See [Project Status](./PROJECT_STATUS.md). All imported documents remain Drafts.

The Counsel workspace now includes operational review, publication scheduling, workflow configuration, evidence export, placeholder management, audit, and safe settings pages. Software publication capability does not constitute legal approval; no Draft becomes operative without authorised review and explicit publication.
