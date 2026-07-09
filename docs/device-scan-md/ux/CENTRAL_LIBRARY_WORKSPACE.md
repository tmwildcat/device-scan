# Central Engineering Workspace

## Purpose

Central Engineering Workspace is the operational workspace for the official LineWatt curated library. It is used by platform engineering staff to upload, compile, review, publish, reject, discontinue and replace Engineering Records.

The Library is where users consume published Engineering Records. Central Engineering Workspace is where platform staff manage them.

## Actors

- Super Admin
- Admin
- Librarian

## Route Ideas

- `/central-engineering`
- `/central-engineering/records`
- `/central-engineering/records/{record}`
- `/central-engineering/review`
- `/central-engineering/validation-warnings`
- `/central-engineering/published`
- `/central-engineering/discontinued`
- `/central-engineering/replacements`
- `/central-engineering/partner-submissions`
- `/central-engineering/manufacturers`
- `/central-engineering/compiler-stats`
- `/central-engineering/compare`

## Dominant Action

Review / publish Engineering Records.

## Screens

- Central Engineering dashboard.
- Review queue.
- Published records.
- Validation warning queue.
- Discontinued/replaced records.
- Partner submissions queue.
- Manufacturer management.
- Compiler statistics.
- Central Engineering Record detail.
- Internal Engineering Comparison.

## Components

- ReviewQueueTable.
- PublishActionBar.
- ValidationSummaryPanel.
- CompilerStatsCards.
- RecentlyPublishedList.
- ManufacturerManagementTable.
- ReplacementSuggestionPanel.
- PartnerSubmissionInbox.
- EngineeringRecordStatusBadge.
- ReviewStatusBadge.
- ValidationStatusBadge.
- EngineeringComparisonEntry.

## Status Behavior

Central status:

- uploaded
- compiled
- published
- rejected
- discontinued
- replaced

Review status:

- not_required
- pending_review
- reviewed
- flagged

Validation status:

- clean
- warnings
- errors

Rules:

- Clean records can publish directly.
- Records with warnings may publish with `review_status = pending_review`.
- Records with errors should not publish unless Admin or Super Admin overrides.
- Partner submissions copied into central must publish only after platform action.

## Permissions

Super Admin has full platform control.

Admin can operate central records, publish/reject, manage partners/promotions and override validation errors.

Librarian can upload, compile, review, approve/publish Engineering Records and mark discontinued/replaced. Librarian cannot manage subscription or system settings.

## Edge Cases

- A published record can later become discontinued and point to replacement records.
- A replacement suggestion should not automatically replace an existing published record.
- If validation errors are overridden, require a reason and audit metadata.
- Advertisement/promotion player must not appear inside engineering review pages.
- Published records are consumed through Library experiences, not this operational workspace.
