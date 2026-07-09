# Inverter Compiler

Version: 1.0  
Project: LineWatt Library  
Status: Active  
Audience: Codex, engineers, contributors

## Goal

Extract canonical engineering data from string and hybrid inverter datasheets.

The compiler should understand inverter datasheet sections rather than trying to build a universal electrical table parser.

## Canonical Inverter Sections

Detect these canonical sections:

```text
INVERTER_DC_INPUT
INVERTER_STORAGE_INPUT
INVERTER_AC_OUTPUT
INVERTER_BACKUP_OUTPUT
INVERTER_EFFICIENCY
INVERTER_PROTECTION
INVERTER_COMMUNICATION
INVERTER_GENERAL
INVERTER_MECHANICAL
INVERTER_COMPLIANCE
INVERTER_CURVES
INVERTER_MARKETING
```

Ignore marketing and curve-only sections unless useful for metadata.

## DC Input

Common headings:

```text
Input DC
Input (DC)
Input data
Input data (DC)
PV Input
Input
```

Extract:

```text
model
recommended_max_pv_power
max_dc_voltage
startup_voltage
rated_dc_voltage
nominal_dc_voltage
mppt_voltage_range
full_power_mppt_range
mppt_count
strings_per_mppt
number_of_dc_inputs
max_input_current
max_input_current_per_mppt
max_input_current_per_string
max_short_circuit_current
max_short_circuit_current_per_mppt
dc_connection
```

## Storage Input

For hybrid inverters only.

Common headings:

```text
Battery
Battery connection
Input data (Storage)
Storage
DC battery connection
```

Extract:

```text
battery_voltage_range
battery_type
max_charge_current
max_discharge_current
max_charge_power
max_discharge_power
compatible_batteries
storage_connection
battery_communication
```

## AC Output

Common headings:

```text
Output AC
Output (AC)
Output data
Output data (AC)
Grid side
AC Output
```

Extract:

```text
rated_ac_power
max_ac_power
rated_apparent_power
max_apparent_power
rated_ac_voltage
ac_voltage_range
rated_frequency
frequency_range
rated_output_current
max_output_current
power_factor
thd
phase_type
feed_in_phases
ac_connection
```

## Backup Output

For hybrid or backup systems.

Common headings:

```text
Backup output
Output AC (Back-up)
PV Point
Full Backup
Continuous Backup Operation
```

Extract:

```text
backup_rated_power
backup_peak_power
backup_voltage
backup_current
backup_frequency
backup_switch_time
backup_phase_type
```

## Efficiency

Extract:

```text
max_efficiency
european_efficiency
mppt_efficiency
mpp_adaptation_efficiency
```

Ignore curve data unless numeric efficiency values are not available elsewhere.

## Protection

Common headings:

```text
Protection
Protection devices
Protective devices
Protection & Function
Features & Protections
```

Extract:

```text
dc_switch
dc_disconnector
input_side_disconnection_device
dc_reverse_polarity_protection
anti_islanding
ac_short_circuit_protection
ac_overcurrent_protection
ac_overvoltage_protection
ground_fault_monitoring
insulation_monitoring
residual_current_monitoring
rcmu
afci
pid_recovery
dc_spd
ac_spd
string_current_monitoring
grid_monitoring
leakage_current_protection
```

## Communication

Common headings:

```text
Interfaces
Communication
Features / Functions / Accessories
Display
```

Extract:

```text
rs485
ethernet
wifi
modbus
modbus_tcp
modbus_rtu
usb
lan
gprs
cellular
digital_inputs
digital_outputs
api
display
datalogger
web_server
smart_meter_support
```

## General / Mechanical

Extract:

```text
dimensions
weight
ip_rating
protection_degree
cooling
topology
operating_temperature
humidity
altitude
noise
mounting
night_consumption
self_consumption
warranty
country_of_manufacture
```

## Compliance

Extract certificates and standards:

```text
IEC 62109
IEC 62116
IEC 61727
EN 50549
VDE-AR-N
G98
G99
AS/NZS 4777
CEI
UL
C10/C11
TOR
```

## Model Handling

Inverter datasheets commonly contain multiple model columns.

Examples:

- SUN2000-8/10/12/15/17/20KTL-M2
- SG5.0RT / SG6.0RT / SG8.0RT / SG10RT / SG12RT
- Fronius Verto 10.0 / 12.5 / 15.0 / 18.0 / 36.0

Extract model-specific values separately.

Shared values such as IP rating or communication interfaces may apply to all models.

## Non-Inverter Documents

Some corpus files may be related equipment, such as backup boxes.

The compiler should detect when a PDF is not an inverter and avoid forcing it into an inverter DTO.

Return a classification warning such as:

```text
unsupported_equipment_type
backup_box_detected
```

## Output

The inverter compiler should return an Inverter DTO with:

- manufacturer
- series/family
- models
- DC input data
- storage input data where present
- AC output data
- backup output data where present
- efficiency
- protection
- communication
- general/mechanical data
- compliance
- source metadata
- warnings
