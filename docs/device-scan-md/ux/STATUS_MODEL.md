# Status Model

## Purpose

LineWatt Library separates lifecycle status, review status and validation status so engineering quality and publication state are not mixed.

## Actors

- Subscriber / Member
- Librarian
- Admin
- Super Admin
- Partner Admin
- Partner User

## Fields

### status

- uploaded
- compiled
- published
- rejected
- discontinued
- replaced

### review_status

- not_required
- pending_review
- reviewed
- flagged

### validation_status

- clean
- warnings
- errors

## Route Ideas

- `/my-library/records?status=compiled`
- `/central-engineering/review?review_status=pending_review`
- `/central-engineering/records?validation_status=errors`
- `/partner/submissions?status=compiled`

## Screens

- Review queue.
- Validation warnings queue.
- Published records.
- Discontinued/replaced records.
- Partner submission status.

## Components

- LifecycleStatusBadge.
- ReviewStatusBadge.
- ValidationStatusBadge.
- StatusTransitionMenu.
- OverrideReasonModal.
- ReplacementLinkPanel.

## Status Behavior

Publishing rules:

- `validation_status = clean`: can publish directly.
- `validation_status = warnings`: may publish with `review_status = pending_review`.
- `validation_status = errors`: should not publish unless Admin/Super Admin overrides.

Partner rules:

- Partner submissions cannot publish directly.
- Promotion copies partner assets into Central Engineering Workspace for review.
- Central copy starts review-required.

Tenant rules:

- Tenant/private records remain private.
- Tenant/private assets never automatically become central records.

Replacement rules:

- `discontinued`: no longer active but still searchable when included.
- `replaced`: points to one or more replacement Engineering Records.

## Permissions

Librarians can transition central records except validation-error override if policy reserves that for Admin/Super Admin. Subscribers can transition only own private records. Partners can update partner product lifecycle but not central publication status.

## Edge Cases

- A record can be `published` with `review_status = pending_review` if warnings are accepted.
- A record can be discontinued without having a replacement.
- Rejected partner submissions should keep source files for audit unless retention policy later says otherwise.
- Comparison does not change any status field.
