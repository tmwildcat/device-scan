# Legal Governance Domain Model

## Aggregates

- `LegalDocument` is the persistent identity.
- `LegalDocumentVersion` owns text, rendering, checksum, dates, and lifecycle.
- `LegalArtifact`, `LegalReview`, and `LegalPlaceholder` bind to an exact version.
- `LegalWorkflow` owns ordered `LegalWorkflowRequirement` records.
- `LegalObligation` binds an actor or organisation to an exact version.
- `LegalAcceptance`, `LegalManifest`, and `LegalAuditEvent` are append-only evidence.

Public UUIDs are separate from internal integer keys. Flexible metadata uses JSON; statuses, dates, identities, audiences, types, checksums, and search keys use indexed columns.

No destructive cascade can remove a version referenced by acceptance evidence.
