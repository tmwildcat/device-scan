# Legal Document Lifecycle

## States

Draft → In Review → Changes Requested or Approved → Scheduled or Published → Superseded, Archived, or Withdrawn.

Drafts and Changes Requested versions may be edited. Editing regenerates HTML/plain text/checksum and invalidates the practical value of checksum-bound prior reviews. Approval can require configured review types with the current checksum.

Publication requires Approved status, no open release-blocking placeholder, an effective date, frozen artefacts, and a publication manifest. Corrections require a new version. Withdrawal and supersession preserve content and evidence.

Counsel submits a Draft for review, reviewers record a decision against the exact checksum, and required current-checksum approvals gate lifecycle approval. Scheduling requires an Approved version and valid future publication/effective dates. Publishing accepts only Approved or Scheduled versions, freezes configured artifacts, records a manifest and audit event, and supersedes the prior Published version without deleting history.

Counsel may cancel a Scheduled publication, which returns the immutable candidate to Approved without publishing it. Permission-separated Withdraw and Archive actions preserve history and emit audit events; Archive is restricted to non-operative Draft or Changes Requested versions.

## Source reconciliation

The importer never mutates Published content. Identical Draft content is ignored. A differing database Draft records a reconciliation conflict instead of being overwritten.
