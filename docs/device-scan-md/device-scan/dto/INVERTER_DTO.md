# Inverter DTO

Version: 1.0  
Project: LineWatt Library  
Status: Draft  
Audience: Codex, engineers, contributors

## Purpose

The Inverter DTO represents canonical engineering data extracted from a string or hybrid inverter datasheet.

It should represent engineering meaning, not PDF layout.

## Top-Level Fields

Suggested top-level structure:

```text
manufacturer
series
family
equipment_type
models
dc_input
storage_input
ac_output
backup_output
efficiency
protection
communication
general
mechanical
compliance
source_metadata
warnings
```

## Model Records

Each inverter model should have its own model-specific values.

Example:

```text
model_name
rated_ac_power_w
max_ac_power_w
max_dc_voltage_v
mppt_count
strings_per_mppt
max_input_current_a
max_output_current_a
```

## Shared Values

Some values apply to all models:

```text
ip_rating
cooling
communication
protection features
compliance standards
mounting type
```

Store shared values once or copy them clearly.

## Protection

Protection fields should be structured and explicit:

```text
dc_switch
dc_reverse_polarity_protection
afci
dc_spd
ac_spd
anti_islanding
grid_monitoring
ground_fault_monitoring
residual_current_monitoring
```

Values may be boolean, text, or type-rated values such as `Type II`.

## Hybrid / Storage

Hybrid inverters should keep PV input and battery input separate.

Do not place battery voltage range under PV DC input.

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
unsupported_equipment_type
backup_box_detected
missing_dc_input
missing_ac_output
low_confidence_model_mapping
multiple_technical_tables_detected
```
