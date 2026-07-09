# Role Permission Model

## Purpose

Define role boundaries for LineWatt Library and prevent accidental mixing of private, central and partner workflows.

## Actors

- Super Admin
- Admin
- Librarian
- Subscriber / Member
- Guest
- Partner Admin
- Partner User

## Permission Summary

| Role | Areas | Key Permissions | Key Restrictions |
|---|---|---|---|
| Super Admin | Central/platform | Full platform control, compare | None |
| Admin | Central/platform | Publish/reject, partners, promotions, validation override, compare | No private tenant access unless policy allows support mode |
| Librarian | Central Engineering Workspace | Upload, compile, review, approve/publish, discontinue/replace, compare | No subscription/system settings |
| Subscriber / Member | My Library + Library | Private upload, compile, review own records, search, compare, export/download by plan | Cannot publish central |
| Guest | Home + limited Library | Marketing and limited discovery | No upload, compile, download, compare |
| Partner Admin | Partner Portal + Library | Manufacturer profile, users, submissions, promotions, lifecycle, search, compare, export by plan | Cannot publish central or influence ranking |
| Partner User | Partner Portal + Library | Upload, drafts, submit, view status, search, compare, export by plan | Cannot publish, manage users, manage promotions |

## Route Ideas

- Platform roles: `/central-engineering/*`
- Customer roles: `/my-library/*`
- Partner roles: `/partner/*`
- Shared read/search: `/library/*`, `/search`

## Screens

- Role assignment screens later for Admin/Super Admin.
- Partner user management.
- Account workspace switcher.

## Components

- PermissionGate.
- WorkspaceSwitcher.
- RoleBadge.
- RestrictedActionTooltip.
- OverrideReasonModal.

## Status Behavior

- Subscriber can move own record from uploaded to compiled, reviewed, rejected, discontinued, replaced.
- Librarian/Admin can move central records through publish/reject/discontinue/replace.
- Partner can submit but not publish.
- Admin/Super Admin can override validation errors.
- Comparison is read-only and available to authenticated non-guest roles.

## Edge Cases

- Multi-role users need explicit workspace context.
- Validation override requires audit metadata.
- Partner promotions require labeling and cannot influence validation, ranking or recommendations.
- Partner Central Library access is read-only for benchmarking and product positioning.
