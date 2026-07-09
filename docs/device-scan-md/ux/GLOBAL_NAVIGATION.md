# Global Navigation

## Purpose

Global navigation separates Home, Engineering Search, Library consumption and operational workspaces while allowing users with multiple roles to switch intentionally.

## Actors

- Guest
- Subscriber / Member
- Super Admin
- Admin
- Librarian
- Partner Admin
- Partner User

## Preferred Navigation

- Home
- Engineering Search
- My Library
- Central Engineering Workspace
- Partner Portal
- Upload
- Exports
- Settings

Visibility depends on permissions.

## Route Ideas

- `/`
- `/search`
- `/library`
- `/my-library`
- `/central-engineering`
- `/partner`
- `/account`

## Navigation Model

Public nav:

- Home
- Engineering Search
- Library
- Sign in

Subscriber nav:

- My Library
- Engineering Search
- Library
- Upload
- Exports
- Settings

Platform staff nav:

- Central Engineering Workspace
- Review Queue
- Published
- Partner Submissions
- Manufacturers
- Compiler Stats
- Settings

Partner nav:

- Engineering Search
- Library
- Partner Portal
- Products
- Submissions
- Promotions
- Analytics
- Settings

## Dominant Action

- Home: Engineering Search.
- My Library: Quick upload / continue working.
- Central Engineering Workspace: Review / publish.
- Partner Portal: Manage products and submissions.

## Components

- WorkspaceSwitcher.
- RoleAwareNavigation.
- EngineeringSearchEntry.
- AccountMenu.
- NotificationBell.
- StatusQueueBadges.

## Status Behavior

Navigation badges can show My Library needs review, Central Engineering pending review, partner submissions requiring attention and validation errors.

## Permissions

Do not show inaccessible workspaces. Users with multiple roles can switch, but the current workspace must remain visually clear.

## Edge Cases

- A platform Admin who is also a Subscriber should not accidentally upload private files into Central Engineering Workspace.
- A Partner Admin should see Partner Portal, not Central Engineering Workspace, unless they also have a platform role.
- Guest navigation must not imply upload/download/compare access.
- Upload should resolve to the current workspace scope, not a generic upload page.
