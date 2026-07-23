# Legal Governance Platform Architecture

## Portable core

`app/LegalGovernance` contains contracts, enums, models, actions, services, support utilities, and workflows. Core operations accept neutral type/id references and do not depend on Manufacturer, Paddle, MCP, or Inertia.

## LineWatt adapters

`Adapters/LineWattLegalIdentityResolver.php` maps `User` and optional Manufacturer organisation references. `LineWattLegalPdfRenderer.php` maps the existing `SimplePdf` renderer. HTTP controllers and role middleware are application adapters.

## Data flow

```text
governed Markdown manifest -> deliberate Draft import -> database authoring
-> checksum-bound reviews -> guarded publication -> frozen artefacts/manifest
-> public portal and dynamic workflow resolution -> append-only evidence
```

Published, Superseded, Archived, and Withdrawn versions reject model updates. Retention-sensitive records use restrictive foreign keys and append-only model guards.

## Landing and dashboard queries

The existing `EntitlementChecker` remains the authoritative landing resolver. It evaluates Super Administrator before Legal Counsel, then preserves the established role branches. `LegalGovernanceDashboardQuery` is a read-only application query over legal tables and returns only allow-listed activity fields.

## Transactions

Import, acceptance, obligation assignment, lifecycle changes, and publication use database transactions. Storage artefacts are generated before the final publication state transition; operational recovery must reconcile storage if a database transaction rolls back after a storage write.

The HTTP operations adapter calls the existing actions and services rather than duplicating lifecycle rules. Routes remain independently protected by named legal permissions. Daily integrity verification records an append-only summary event and never repairs retained evidence automatically.
