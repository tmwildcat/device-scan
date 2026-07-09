# Confidence and Source Metadata

Version: 1.0  
Project: LineWatt Library  
Status: Draft  
Audience: Codex, engineers, contributors

## Purpose

Extraction should be reviewable.

The compiler should preserve confidence and source metadata where practical.

## Suggested Metadata

For each extracted value, store:

```text
source_page
source_section
source_heading
source_text
source_row
source_column
source_cell
confidence
method
```

## Confidence Signals

High confidence:

- value appears in a known section
- parameter name matches canonical dictionary
- unit matches expected unit
- model header is clear
- table structure is consistent

Medium confidence:

- parameter phrase is partial
- value is nearby but not clearly tabular
- model mapping is inferred

Low confidence:

- value found in marketing text
- multiple candidate values exist
- section is ambiguous
- unit is missing or unexpected

## Warnings

Use warnings instead of silent failure.

Examples:

```text
missing_stc_section
multiple_stc_sections
ambiguous_model_columns
variant_without_base_stc
unsupported_equipment_type
low_confidence_value
```

## Do Not Guess

Never fabricate missing values.

If the datasheet does not state a value, leave it null.
