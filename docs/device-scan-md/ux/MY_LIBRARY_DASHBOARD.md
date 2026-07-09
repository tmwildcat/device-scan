# My Library Dashboard

## Purpose

My Library is the subscriber private workspace. It is where members upload datasheets, compile private Engineering Records, review their own records and export/download according to subscription limits.

The Library is where members consume published central Engineering Records. My Library is where they create and manage private records.

## Actors

- Subscriber / Member

## Route Ideas

- `/my-library`
- `/my-library/uploads`
- `/my-library/uploads/new`
- `/my-library/records`
- `/my-library/records/{record}`
- `/my-library/compare`
- `/my-library/exports`
- `/my-library/activity`
- `/my-library/settings/storage`

## Dominant Action

Continue working / Quick upload.

## Screens

- Dashboard.
- Quick upload.
- Private Engineering Records list.
- Needs review queue.
- Private Engineering Record detail.
- Engineering Comparison.
- Export history.
- Storage usage and quota.

## Components

- StorageUsageMeter.
- QuickUploadPanel.
- PrivateUploadsTable.
- CompiledEngineeringRecordsTable.
- NeedsReviewList.
- RecentExportsList.
- ModuleInverterCounters.
- PrivateEngineeringSearchBar.
- EngineeringComparisonEntry.
- RecentActivityFeed.
- Compact AdPromotionPlayer.

## Status Behavior

Tenant records are private and quota-limited.

Allowed private lifecycle:

- uploaded
- compiled
- rejected
- discontinued
- replaced

Private records can have:

- `review_status = not_required | pending_review | reviewed | flagged`
- `validation_status = clean | warnings | errors`

Subscriber records cannot become central records unless an explicit platform promotion/review workflow is introduced.

## Engineering Comparison

Subscribers can compare exactly two visible records:

- Private vs private.
- Central vs central.
- Private vs Central.
- Different revisions.
- Replacement products.

Comparison is read-only and must clearly label private vs central scope.

## Permissions

Subscriber / Member can:

- upload datasheets to private storage.
- compile private Engineering Records.
- review own records.
- search private records plus central published records.
- compare two visible Engineering Records.
- export/download according to subscription.

Subscriber / Member cannot:

- publish to Central Library.
- review partner submissions.
- change central records.
- bypass subscription quota.

## Edge Cases

- If quota is full, quick upload becomes disabled and links to storage management.
- If validation errors exist, export can be allowed with warning labels depending on plan policy, but should not imply official quality.
- Private uploads must never appear in public search or partner portals.
