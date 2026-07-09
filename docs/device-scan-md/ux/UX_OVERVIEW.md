# LineWatt Library UX Overview

## Purpose

LineWatt Library is a professional engineering library for renewable energy equipment. It is not a datasheet scanner.

Core promise:

> Create, manage, search, compare and distribute trusted Engineering Records.

The UX separates consumption from operations:

- Library experiences are where users read, search, discover, compare and export Engineering Records.
- Workspace experiences are where authorized users create, compile, review, manage and publish Engineering Records.

## Actors

- Guest: Home, limited Engineering Search and limited Library discovery.
- Subscriber / Member: private workspace, Engineering Search, Library, comparison and exports according to subscription.
- Super Admin: full platform control.
- Admin: central engineering operations, publish/reject, partners, promotions and overrides.
- Librarian: engineering curation, review and publication role.
- Partner Admin: manufacturer profile, users, submissions, promotions, lifecycle and benchmarking.
- Partner User: drafts, uploads, submission status, Library search and comparison.

## Product Areas

- Home: premium discovery and entry point.
- Engineering Search: focused search experience for Engineering Records.
- Library: read/search/discover/compare published Engineering Records.
- My Library: subscriber private workspace.
- Central Engineering Workspace: platform engineering operations.
- Partner Portal: OEM/manufacturer workspace.

Do not create one generic upload/review workflow for all users.

## Engineering Comparison

Engineering Comparison is a first-class capability. It compares exactly two Engineering Records side by side in v1.

Supported comparisons:

- Module vs Module.
- Inverter vs Inverter.
- Private vs Central.
- Different revisions.
- Replacement products.

Comparison is always read-only and must never modify Engineering Records. Guests cannot compare. Future comparison workspaces and engineering data rooms are out of scope.

## Product Language

- Use "LineWatt Library" as the product name.
- Use "Engineering Record" in UI.
- Use "Engineering Search".
- Do not use internal technical object names in user-facing UI.
- Use "Compile" only when describing the engineering compiler action.
- Use "Validation" for engineering consistency checks.

## Route Ideas

- `/`
- `/search`
- `/library`
- `/library/records/{record}`
- `/library/compare`
- `/my-library`
- `/my-library/uploads`
- `/my-library/records/{record}`
- `/central-engineering`
- `/central-engineering/review`
- `/central-engineering/records/{record}`
- `/partner`
- `/partner/products`
- `/partner/submissions`

## Shared Components

- EngineeringSearchBar
- EngineeringRecordCard
- EngineeringRecordTable
- EngineeringComparisonTable
- EngineeringRecordStatusBadge
- ValidationBadge
- QualityGradeBadge
- DeviceTypeTabs
- TechnologyFilterPanel
- ManufacturerLogoStrip
- ArtifactSourcePanel
- DownloadMenu
- AdPromotionPlayer

## Status Behavior

Three status dimensions should be visible where relevant:

- `status`: lifecycle and publication state.
- `review_status`: engineering review state.
- `validation_status`: compiler validation state.

Clean records can publish directly. Records with warnings may publish with pending review. Records with errors should not publish unless Admin or Super Admin overrides.

## Permissions

Home and limited Engineering Search are open to guests. My Library is subscriber/member-only. Central Engineering Workspace is platform staff-only. Partner Portal is partner-only. Published Central Library records are readable by subscribers and partners according to permissions. Engineering review pages must not show promotions or ads.

## Edge Cases

- A datasheet can create many Engineering Records.
- A partner submission can be rejected without affecting partner-private source assets.
- A tenant-private upload must never become central unless explicitly promoted.
- A central record can be discontinued and replaced without deleting historical records.
- Validation warnings should guide review, not block all workflows.
- Partner users can search and compare published Central Library records, but cannot modify them or influence ranking.
