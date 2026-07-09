# Module Corpus

Version: 1.0  
Project: LineWatt Library  
Status: Active  
Audience: Codex, engineers, contributors

## Location

```text
storage/app/private/device-scan/corpus/modules
```

## Purpose

The module corpus contains real PV module datasheets used to study module manufacturer patterns.

## Observed Manufacturers / Patterns

The corpus includes examples from manufacturers such as:

- JA Solar
- Jinko
- LONGi
- Trina
- Canadian Solar
- Risen
- Astronergy
- Adani
- First Solar
- Maxeon
- REC
- FuturaSun
- Vikram Solar

## Common Module Layouts

Most module datasheets contain:

- cover/marketing page
- electrical data at STC
- electrical data at NOCT/NMOT
- bifacial gain or BNPI data for bifacial modules
- temperature characteristics
- mechanical data
- operating conditions
- warranty statements
- packaging information
- I-V and P-V curves

## Important Pattern: Split Sections

Some datasheets visually split a single engineering area into multiple table fragments.

Do not assume one PDF table equals one engineering section.

Use headings and section context.

## Important Pattern: Bifacial Variants

Headings such as:

```text
Electrical Characteristics with 10% Solar Irradiation Ratio
Electrical Characteristics with different rear side power gain
Bifacial Gain
BNPI
```

should be treated as variants, not base STC.

Do not overwrite STC values.

## Important Pattern: Shared Values

Values such as maximum system voltage, fuse rating, warranty, and temperature coefficients often apply to all models in a series.

Represent shared values carefully.

## Marketing Pages

Cover pages often contain useful metadata but should not be parsed as engineering tables.

Examples:

- efficiency claims
- warranty badges
- product benefits
- technology names

Use only where helpful.
