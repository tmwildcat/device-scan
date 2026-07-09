# Module DTO

Version: 1.0  
Project: LineWatt Library  
Status: Draft  
Audience: Codex, engineers, contributors

## Purpose

The Module DTO represents canonical engineering data extracted from a PV module datasheet.

It should not mirror PDF table layout.

It should represent engineering meaning.

## Top-Level Fields

Suggested top-level structure:

```text
manufacturer
series
family
technology
models
electrical_stc
electrical_noct
electrical_nmot
electrical_variants
temperature_characteristics
operating_conditions
mechanical
warranty
packaging
certifications
source_metadata
warnings
```

## Model Records

Each model should have its own model-specific values.

Example model fields:

```text
model_name
rated_max_power_w
voc_v
vmp_v
isc_a
imp_a
module_efficiency_percent
```

## Shared Values

Some values apply to all models:

```text
maximum_system_voltage_v
maximum_series_fuse_rating_a
operating_temperature_range
temperature_coefficients
warranty
mechanical dimensions
```

Store shared values once or copy them with clear metadata.

## Variants

Bifacial gain or BNPI data should be stored separately.

Example:

```text
electrical_variants[]
  type = bifacial_gain
  condition = 10_percent_rear_gain
  applies_to = electrical_stc
  model_values = ...
```

## Source Metadata

Each extracted value should ideally include:

```text
source_page
source_section
source_text
source_row
source_column
confidence
```

## Warnings

Warnings may include:

```text
missing_electrical_stc
missing_mechanical
multiple_candidate_sections
low_confidence_model_mapping
variant_detected_without_base_stc
```
