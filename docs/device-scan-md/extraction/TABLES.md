# Table Handling

Version: 1.0  
Project: LineWatt Library  
Status: Active  
Audience: Codex, engineers, contributors

## Purpose

Tables are important but should not dominate the architecture.

The goal is to extract engineering facts, not reconstruct table layout perfectly.

## Common Table Challenges

Real datasheets contain:

- split tables
- merged cells
- grouped headers
- repeated model columns
- shared values
- rotated or embedded diagrams
- curve legends mixed near data tables
- marketing text near engineering data

## One Table Is Not Always One Section

A logical section may be split into multiple detected grids.

A detected grid may also contain multiple logical sections.

Use headings and semantic signals to decide.

## Grouped Cells

Grouped cells may imply:

- one header applies to multiple columns
- one value applies to all models
- one condition applies to multiple rows
- STC/NOCT groups apply to adjacent rows

Handle these with simple practical logic.

Do not build a universal merged-cell engine unless necessary.

## Model Columns

Many module and inverter tables have model columns.

Map values by model.

If model mapping is uncertain, preserve source text and add warning.

## Shared Rows

Rows like maximum system voltage, fuse rating, IP rating, cooling, communication, or warranty may apply to all models.

Store as shared values or propagate with metadata.

## Curves

Curve sections often contain numbers that can confuse extraction.

Ignore tables or grids dominated by:

```text
I-V curve
P-V curve
Efficiency curve
Power derating
Current-Voltage Curve
Power-Voltage Curve
```

unless the compiler explicitly needs curve metadata.

## Geometry as Helper

Use geometry for:

- row ordering
- column ordering
- model/value alignment
- nearby heading relationships

Do not use geometry as the only classifier.
