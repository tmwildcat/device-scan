# Implementation Sequence

## Purpose

Define a practical implementation order for LineWatt Library UX and workflow architecture.

## Recommended First Workflow

Build Central Engineering Workspace review and Engineering Record detail first.

Reason:

- It exercises Engineering Record language.
- It uses existing compiler/storage metadata.
- It validates status, review and validation models.
- It gives staff a place to inspect real compiled records before broader subscriber/partner workflows.

## Sequence

1. Shared UI vocabulary and layout shells:
   - product name.
   - workspace navigation.
   - Library vs Workspace separation.
   - status badges.
   - Engineering Record naming.

2. Engineering Record detail:
   - tabs.
   - source metadata.
   - validation panel.
   - downloads placeholder.
   - compare action.

3. Central Engineering Workspace:
   - dashboard.
   - review queue.
   - publish/reject/discontinue/replace action models.

4. Engineering Search:
   - central published search.
   - filters for device type, manufacturer, model, technology, validation grade.
   - authenticated two-record compare selection.

5. Library and Engineering Comparison:
   - published Engineering Record browsing.
   - two-record read-only comparison.
   - private vs central comparison for subscribers.
   - partner benchmarking against published central records.

6. Home:
   - premium engineering portal hero.
   - Engineering Search.
   - featured manufacturers/technologies.
   - recently published Engineering Records.
   - LineWatt promotional player.

7. My Library:
   - private dashboard.
   - quick upload.
   - private Engineering Records.
   - quota and exports.

8. Partner Portal:
   - product list.
   - submissions.
   - lifecycle.
   - read-only Central Library access.
   - promotions.

9. Promotion player management:
   - partner campaigns.
   - sponsored labeling.
   - analytics.

## Components To Build Early

- EngineeringRecordStatusBadge.
- ReviewStatusBadge.
- ValidationStatusBadge.
- EngineeringSearchBar.
- EngineeringRecordTable.
- EngineeringComparisonTable.
- EngineeringRecordDetailTabs.
- SourceTracePanel.
- ValidationSummaryPanel.
- WorkspaceSwitcher.

## Permissions

Implement route groups and policies before write actions. Read-only prototypes can exist first, but upload/review/publish actions must enforce workspace scope.

## Edge Cases

- Do not show private tenant data in central/public search.
- Do not allow partner publishing.
- Do not show ads in review pages.
- Do not let validation warnings silently block every workflow.
- Do not implement future Engineering Data Rooms, project collaboration, tendering or AI assistant features in v1.
