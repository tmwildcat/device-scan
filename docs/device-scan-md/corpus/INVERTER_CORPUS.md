# Inverter Corpus

Version: 1.0  
Project: LineWatt Library  
Status: Active  
Audience: Codex, engineers, contributors

## Location

```text
storage/app/private/device-scan/corpus/inverters
```

## Purpose

The inverter corpus contains real string and hybrid inverter datasheets used to study inverter manufacturer patterns.

## Observed Manufacturers / Patterns

The corpus includes examples from manufacturers such as:

- Fronius
- Huawei
- SMA
- Sungrow
- Growatt
- Waaree

## Common Inverter Layouts

Most inverter datasheets contain:

- cover/marketing page
- efficiency and derating curves
- technical data table
- DC input section
- AC output section
- battery/storage section for hybrid inverters
- backup output section for hybrid/backup systems
- protection devices
- communication/interfaces
- general data
- certifications/compliance

## Important Pattern: Technical Data Pages

Inverter datasheets often concentrate engineering data under headings such as:

```text
Technical data
Technical Specification
Input (DC)
Output (AC)
Protection & Function
General Data
```

Prioritize these sections.

## Important Pattern: Protection Sections

Protection data is often explicit and should not be guessed.

Look for:

```text
Protection devices
Protective devices
Protection & Function
Features & Protections
```

## Important Pattern: Hybrid Inverters

Hybrid inverters may have:

- PV DC input
- battery/storage input
- grid AC output
- backup AC output

Keep these separate.

## Negative / Related Equipment

Some corpus documents may describe backup boxes or accessories.

These are useful negative examples.

The compiler should detect unsupported equipment and avoid forcing it into an inverter DTO.
