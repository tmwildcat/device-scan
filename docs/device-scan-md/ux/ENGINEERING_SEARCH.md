# Engineering Search

## Purpose

Engineering Search is the primary discovery mechanism for LineWatt Library. It is an engineering-specific search experience for modules and inverters, not a generic text search.

Engineering Search is separate from the operational workspaces. It is where users find Engineering Records to read, compare and export.

## Actors

- Guest
- Subscriber / Member
- Librarian
- Admin
- Super Admin
- Partner Admin
- Partner User

## Route Ideas

- `/search`
- `/search/modules`
- `/search/inverters`
- `/library/search`
- `/my-library/search`
- `/central-engineering/search`
- `/partner/search`

## Dominant Action

Find trusted Engineering Records by engineering attributes.

## Search Fields

Search should support:

- manufacturer
- model
- model series
- power
- technology
- device type
- module/inverter
- certification
- validation grade
- status
- inverter protection features: DC SPD, AC SPD, AFCI, RCMU, DC switch, grid monitoring
- module attributes: TOPCon, HJT, bifacial, double glass

## Compare From Search

Authenticated users can select exactly two Engineering Records and open Engineering Comparison. Guests cannot compare.

## Screens

- Engineering Search results.
- Module results.
- Inverter results.
- Filtered manufacturer page.
- Filtered technology page.
- Workspace-scoped search results.

## Components

- EngineeringSearchBar.
- EngineeringFilterPanel.
- DeviceTypeTabs.
- PowerRangeFilter.
- TechnologyFilter.
- ProtectionFeatureFilter.
- CertificationFilter.
- ValidationGradeFilter.
- SearchResultRecordCard.
- SearchResultEngineeringTable.
- CompareSelectionBar.

## Status Behavior

Guest searches show public/published central records only.

Subscriber searches can include published central records and own private records.

Central Engineering Workspace searches can include uploaded, compiled, published, rejected, discontinued and replaced central records.

Partner searches include published Central Library records and partner-owned products/submissions.

## Permissions

Search result visibility is workspace-specific. A private tenant record should never appear in guest, partner or central searches unless explicitly copied/promoted to central.

Partner access supports competitive benchmarking, engineering evaluation and product positioning. Partner users may search, compare and export if permitted, but cannot modify records or influence search ranking.

## Edge Cases

- If a discontinued record matches, label it clearly and show replacement links.
- Sponsored promotions must be labeled and separated from organic Engineering Search ranking.
- Validation grade should be filterable but not used as a hidden ranking penalty unless product policy defines it.
- More than two selected records should prompt the user to choose two for v1 comparison.
