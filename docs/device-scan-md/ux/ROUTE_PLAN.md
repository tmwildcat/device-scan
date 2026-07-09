# Route Plan

## Purpose

Define route structure for LineWatt Library without implementing controllers yet.

## Home / Public Discovery

- `GET /`
- `GET /search`
- `GET /search/modules`
- `GET /search/inverters`
- `GET /library`
- `GET /library/records/{record}`
- `GET /technologies`
- `GET /manufacturers`
- `GET /pricing`

Guests cannot compare.

## My Library

- `GET /my-library`
- `GET /my-library/search`
- `GET /my-library/uploads`
- `GET /my-library/uploads/new`
- `GET /my-library/records`
- `GET /my-library/records/{record}`
- `GET /my-library/compare`
- `GET /my-library/exports`
- `GET /my-library/activity`
- `GET /my-library/settings/storage`

## Central Engineering Workspace

- `GET /central-engineering`
- `GET /central-engineering/search`
- `GET /central-engineering/records`
- `GET /central-engineering/records/{record}`
- `GET /central-engineering/review`
- `GET /central-engineering/review/{record}`
- `GET /central-engineering/published`
- `GET /central-engineering/validation-warnings`
- `GET /central-engineering/discontinued`
- `GET /central-engineering/replacements`
- `GET /central-engineering/partner-submissions`
- `GET /central-engineering/manufacturers`
- `GET /central-engineering/compiler-stats`
- `GET /central-engineering/compare`

## Partner Portal

- `GET /partner`
- `GET /partner/products`
- `GET /partner/products/{product}`
- `GET /partner/products/{product}/datasheets`
- `GET /partner/submissions`
- `GET /partner/submissions/{submission}`
- `GET /partner/compare`
- `GET /partner/promotions`
- `GET /partner/analytics`
- `GET /partner/users`
- `GET /partner/profile`

## Dominant Actions

- Home: Engineering Search.
- Engineering Search: find Engineering Records.
- Library: read, compare and export Engineering Records.
- My Library: Quick upload / continue working.
- Central Engineering Workspace: Review / publish.
- Partner Portal: Manage products and submissions.

## Permissions

Each route group should have its own middleware/policy layer. Avoid one generic workflow that mixes central, tenant and partner records.

Engineering Comparison routes require authentication and exactly two selected records in v1.

## Edge Cases

- A user with multiple roles needs workspace-specific route names and layout shells.
- Review pages must suppress AdPromotionPlayer.
- Partner submissions and tenant private records must not share route handlers without explicit source scope checks.
- Upload must be scoped to My Library, Central Engineering Workspace or Partner Portal.
