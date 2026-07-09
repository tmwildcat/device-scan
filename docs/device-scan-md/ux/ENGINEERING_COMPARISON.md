# Engineering Comparison

## Purpose

Engineering Comparison is a first-class capability of LineWatt Library. It compares exactly two Engineering Records side by side in v1.

Comparison is always read-only and must not modify Engineering Records.

## Supported Comparisons

- Module vs Module.
- Inverter vs Inverter.
- Private vs Central.
- Different revisions.
- Replacement products.

## Out Of Scope

- More than two products at once.
- Comparison workspaces.
- Engineering data rooms.
- Collaborative comparison sessions.
- Engineering recommendations.

## Actors

- Subscriber / Member
- Librarian
- Admin
- Super Admin
- Partner Admin
- Partner User

Guests cannot compare.

## Route Ideas

- `/library/compare?left={record}&right={record}`
- `/my-library/compare?left={record}&right={record}`
- `/central-engineering/compare?left={record}&right={record}`
- `/partner/compare?left={record}&right={record}`

## Screens

- Comparison summary.
- Electrical comparison.
- Mechanical / General comparison.
- Operating comparison.
- Protection comparison.
- Warranty comparison.
- Validation comparison.
- Source comparison.
- Downloads.

## Components

- CompareSelectionBar.
- EngineeringComparisonHeader.
- EngineeringComparisonTable.
- DifferenceHighlight.
- ScopeBadge.
- ValidationComparisonPanel.
- ReplacementComparisonPanel.
- ExportComparisonMenu.

## Status Behavior

Comparison can include published central records, private subscriber records visible to the subscriber, partner-owned records visible to the partner, different revisions and replacement products.

Comparison never changes status, review status, validation status or source artifacts.

## Permissions

Partner users have read-only access to published Central Library records for competitive benchmarking, engineering evaluation and product positioning. They may search, compare and export if subscription permits. They cannot modify Central Library records, publish records or influence search ranking.

## Edge Cases

- If one record is discontinued or replaced, show that state clearly.
- If validation grades differ, highlight validation context without recommending a winner.
- If one record is private and one is central, label scope clearly.
- If a user selects more than two records, ask them to choose two for v1.

## Future Note

Future LineWatt products may expose comparison through MCP, engineering services, data rooms or an AI Engineering Assistant. Those products are not designed in this milestone.
