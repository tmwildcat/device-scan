# Partner Portal Workspace

## Purpose

Partner Portal is the OEM/manufacturer workspace for product submissions, lifecycle management and promotions.

Partner users also have read-only access to published Central Library records for competitive benchmarking, engineering evaluation and product positioning.

Partner users cannot modify Central Library records, publish records or influence Engineering Search ranking.

## Actors

- Partner Admin
- Partner User

## Route Ideas

- `/partner`
- `/partner/products`
- `/partner/products/{product}`
- `/partner/products/{product}/datasheets`
- `/partner/submissions`
- `/partner/submissions/{submission}`
- `/partner/compare`
- `/partner/promotions`
- `/partner/analytics`
- `/partner/users`
- `/partner/profile`

## Dominant Action

Manage products and submissions.

## Screens

- Partner dashboard.
- Published Central Library search for benchmarking.
- Product list.
- Product lifecycle detail.
- Upload datasheet.
- Draft submissions.
- Submission status.
- Engineering Comparison.
- Promotions manager.
- Campaign analytics.
- Partner user management.

## Components

- ProductListTable.
- ProductLifecycleBadge.
- PartnerSubmissionTimeline.
- UploadDatasheetPanel.
- SubmissionStatusCard.
- DiscontinueProductAction.
- ReplacementProductSelector.
- PartnerEngineeringComparisonEntry.
- PromotionCampaignTable.
- CampaignAnalyticsCards.

## Status Behavior

Partner source records are `partner_submitted` and never publish directly to Central Library.

Submission lifecycle:

- uploaded
- compiled
- rejected
- discontinued
- replaced

Central promotion lifecycle:

- partner record is copied into Central Engineering Workspace.
- central record starts review-required.
- platform staff publish/reject from Central Engineering Workspace.

Partner users may mark products discontinued/replaced in their own product catalog. Central Library status changes require platform review.

## Permissions

Partner Admin can manage manufacturer profile, upload datasheets, submit updates, manage partner users, manage promotions, mark products discontinued/replaced, search published Engineering Records, compare products and export according to subscription.

Partner User can upload datasheets, manage drafts, submit for review, view submission status, search published Central Library records, compare two visible Engineering Records and export according to subscription.

Partner users may not publish, modify Central Library records, alter validation, manage search ranking or influence recommendations.

## Competitive Engineering Analysis

Partner users should be able to compare own products vs competitor products using Engineering Comparison.

Comparison is read-only and limited to two records in v1. Central Engineering Records remain immutable.

## Edge Cases

- A partner may submit a replacement for an existing central product; platform staff decide whether to link records.
- If a partner upload fails validation, show errors and allow resubmission, but do not publish.
- Sponsored promotions must be labeled and must not alter Engineering Search ranking.
