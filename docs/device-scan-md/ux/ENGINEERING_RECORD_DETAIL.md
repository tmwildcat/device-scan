# Engineering Record Detail

## Purpose

The Engineering Record detail page presents compiled engineering data with source traceability, validation, comparison entry points and downloads.

## Actors

- Guest for limited public published records.
- Subscriber / Member for own private records and central records according to plan.
- Librarian/Admin/Super Admin for central records.
- Partner users for their submissions and published Central Library records.

## Route Ideas

- `/library/records/{record}`
- `/my-library/records/{record}`
- `/central-engineering/records/{record}`
- `/partner/submissions/{submission}/records/{record}`

## Dominant Action

Understand, verify, compare and use an Engineering Record.

## Tabs

- Overview
- Electrical
- Mechanical / General
- Operating
- Protection
- Warranty
- Validation
- Source
- Downloads

## Screens

- Public record detail.
- Private record detail.
- Central review record detail.
- Partner submission record detail.

## Components

- EngineeringRecordHeader.
- DeviceIdentityPanel.
- ValidationSummaryPanel.
- SourceTracePanel.
- ElectricalTables.
- MechanicalGeneralPanel.
- OperatingConditionsPanel.
- ProtectionPanel.
- WarrantyPanel.
- SourceDatasheetViewerLink.
- DownloadMenu.
- CompareAction.
- ReviewActionBar for review contexts.
- ReplacementRecordPanel.

## Status Behavior

Detail pages show lifecycle status, review status, validation status, validation grade/score, source datasheet metadata and replacement/discontinued state.

Central review detail can expose status transitions. Public detail should show only published records unless the user has staff permissions.

## Permissions

Guests can view limited published central details. Subscribers can view full central details according to plan and own private records. Partners can view their own submissions and published Central Library records. Staff can view central records and review controls.

## Edge Cases

- If a field has low confidence or validation warning, show it inline without hiding the data.
- If source PDF is missing, show source metadata and artifact warning.
- If a record is replaced, show replacement prominently.
- Ads must not appear inside engineering review pages.
- Compare is read-only and limited to two records.
