# Canonical Parameters

Version: 1.0  
Project: LineWatt Library  
Status: Active  
Audience: Codex, engineers, contributors

## Purpose

Canonical parameters normalize manufacturer terminology into stable field names.

## Module Electrical Parameters

```text
rated_max_power
  Pmax
  PMAX
  Rated Maximum Power
  Maximum Power
  Nominal Power
  Peak Power

open_circuit_voltage
  Voc
  Open Circuit Voltage
  Open-circuit Voltage

maximum_power_voltage
  Vmp
  Vmpp
  Voltage at Maximum Power
  Maximum Power Voltage
  Rated Voltage

short_circuit_current
  Isc
  Short Circuit Current
  Short-circuit Current

maximum_power_current
  Imp
  Impp
  Current at Maximum Power
  Maximum Power Current
  Rated Current

module_efficiency
  Module Efficiency
  Panel Efficiency
  Efficiency
```

## Module Temperature Parameters

```text
temperature_coefficient_pmax
  Temperature Coefficient of Pmax
  Tc of Power
  Power Temp. Coef.

temperature_coefficient_voc
  Temperature Coefficient of Voc
  Voltage Temp. Coef.

temperature_coefficient_isc
  Temperature Coefficient of Isc
  Current Temp. Coef.

noct
  NOCT
  Nominal Operating Cell Temperature

nmot
  NMOT
  Nominal Module Operating Temperature
```

## Module Operating Parameters

```text
maximum_system_voltage
maximum_series_fuse_rating
operating_temperature
maximum_reverse_current
safety_class
fire_rating
bifaciality
static_load_front
static_load_back
```

## Inverter DC Parameters

```text
recommended_max_pv_power
max_dc_voltage
startup_voltage
rated_dc_voltage
mppt_voltage_range
full_power_mppt_range
mppt_count
strings_per_mppt
max_input_current
max_short_circuit_current
dc_connection
```

## Inverter AC Parameters

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
```

## Inverter Protection Parameters

```text
dc_switch
dc_disconnector
dc_reverse_polarity_protection
anti_islanding
ac_short_circuit_protection
ac_overcurrent_protection
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
```

## Unit Normalization

Preserve original value and normalized value where possible.

Examples:

```text
1500 VDC → 1500 V
34.6kg → 34.6 kg
2382×1134×30mm → length=2382, width=1134, thickness=30
0~+3% → tolerance_min=0, tolerance_max=3
```

## Rule

Never infer a value not present in the datasheet.

If unsure, leave field null and add a warning.
