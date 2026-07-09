# Module Compiler

Version: 1.0  
Project: LineWatt Library  
Status: Active  
Audience: Codex, engineers, contributors

## Goal

Extract canonical engineering data from PV module datasheets.

The compiler should understand how module manufacturers organize datasheets rather than trying to build a universal table engine.

## Canonical Module Sections

Detect these canonical sections where present:

```text
MODULE_ELECTRICAL_STC
MODULE_ELECTRICAL_NOCT
MODULE_ELECTRICAL_NMOT
MODULE_ELECTRICAL_VARIANT
MODULE_TEMPERATURE_CHARACTERISTICS
MODULE_MECHANICAL
MODULE_OPERATING_CONDITIONS
MODULE_WARRANTY
MODULE_PACKAGING
MODULE_CURVES
MODULE_MARKETING
MODULE_CERTIFICATIONS
```

Ignore marketing and curve sections unless they provide metadata.

## Electrical STC

Common headings:

```text
ELECTRICAL PARAMETERS AT STC
ELECTRICAL CHARACTERISTICS STC
ELECTRICAL DATA (STC)
RATINGS AT STANDARD TEST CONDITIONS
Electrical Data, Front STC Characteristics
```

Extract:

```text
model
rated_max_power / pmax
open_circuit_voltage / voc
maximum_power_voltage / vmp / vmpp
short_circuit_current / isc
maximum_power_current / imp / impp
module_efficiency
power_tolerance
```

## NOCT / NMOT

Common headings:

```text
ELECTRICAL DATA (NOCT)
ELECTRICAL DATA (NMOT)
Electrical Parameters | NOCT
Nominal Module Operating Temperature
```

Extract the same electrical parameters as STC, but store under NOCT/NMOT conditions.

Never overwrite STC with NOCT/NMOT values.

## Electrical Variants

If a heading contains:

```text
ELECTRICAL CHARACTERISTICS WITH
Electrical Characteristics with different rear side power gain
Bifacial Gain
BNPI
```

classify as variant.

If the section contains:

```text
bifacial
gain
rear
front
irradiation ratio
BNPI
```

classify as:

```text
BIFACIAL_GAIN_VARIANT
```

Variant values must be stored separately.

Never overwrite base STC values with bifacial or rear-side gain values.

## Temperature Characteristics

Extract:

```text
temperature_coefficient_pmax
temperature_coefficient_voc
temperature_coefficient_isc
noct
nmot
operating_temperature_range
```

Common terminology:

```text
Temperature Coefficient of Pmax
Tc of power
Power Temp. Coef.
Temperature coefficient Voc
Voltage Temp. Coef.
Temperature coefficient Isc
Current Temp. Coef.
NOCT
NMOT
Nominal Operating Cell Temperature
Nominal Module Operating Temperature
```

## Operating Conditions

Extract:

```text
maximum_system_voltage
maximum_series_fuse_rating
maximum_reverse_current
operating_temperature
static_load_front
static_load_back
mechanical_load_snow
mechanical_load_wind
safety_class
fire_rating
bifaciality
```

## Mechanical

Common headings:

```text
MECHANICAL PARAMETERS
MECHANICAL DATA
Mechanical Specifications
Mechanical Description
General Data
```

Extract:

```text
dimensions
length_mm
width_mm
thickness_mm
weight_kg
cell_type
cell_count
cell_layout
junction_box
connector
cable_length
cable_cross_section
glass
front_glass
back_glass
frame
bypass_diodes
packaging
```

## Warranty

Extract:

```text
product_warranty_years
linear_power_warranty_years
performance_warranty_years
first_year_degradation_percent
annual_degradation_percent
end_of_warranty_output_percent
```

Common text:

```text
Product Warranty
Linear Power Warranty
Performance Warranty
1st-year Degradation
Annual Degradation
Power in Year 25
Power in Year 30
```

## Packaging

Extract where useful:

```text
modules_per_pallet
pallets_per_container
modules_per_container
container_type
```

## Curves

Curve sections are usually not needed for DTO extraction.

Ignore:

```text
I-V Curve
P-V Curve
Current-Voltage Curve
Power-Voltage Curve
Low Light Behaviour
```

unless they provide strong metadata.

## Model Handling

Module tables often include multiple model columns.

Examples:

```text
JAM72D42-625/LB ... JAM72D42-650/LB
JKM595N-78HL4 ... JKM615N-78HL4
RECxxxNP3
```

The DTO should represent a model series and model-specific values.

Do not collapse all model values into one value.

## Grouped Cells

Grouped cells may imply that a value applies to multiple models.

Examples:

- Maximum system voltage applies to all models.
- Temperature coefficients apply to all models.
- Warranty applies to all models.

Represent shared values separately or copy them to model-specific records with source metadata.

## Output

The module compiler should return a Module DTO with:

- manufacturer
- series/family
- models
- electrical STC data
- NOCT/NMOT data where present
- variants where present
- mechanical data
- operating conditions
- warranty data
- packaging data
- source metadata
- warnings
