# Heading Detection

Version: 1.0  
Project: LineWatt Library  
Status: Active  
Audience: Codex, engineers, contributors

## Purpose

Headings are the primary signal for section detection.

The compiler should detect headings before interpreting table contents.

## Module Heading Examples

### Electrical STC

```text
ELECTRICAL PARAMETERS AT STC
ELECTRICAL CHARACTERISTICS STC
ELECTRICAL DATA (STC)
RATINGS AT STANDARD TEST CONDITIONS
Electrical Data, Front STC Characteristics
```

### NOCT / NMOT

```text
ELECTRICAL DATA (NOCT)
ELECTRICAL DATA (NMOT)
Electrical Parameters | NOCT
Nominal Module Operating Temperature
```

### Variants

```text
Electrical Characteristics with 10% Solar Irradiation Ratio
Electrical Characteristics with different rear side power gain
Bifacial Gain
BNPI
```

### Mechanical

```text
MECHANICAL PARAMETERS
MECHANICAL DATA
Mechanical Specifications
Mechanical Description
```

### Operating

```text
OPERATING CONDITIONS
Maximum Ratings
Operating Parameters
Temperature and Maximum Ratings
```

### Warranty

```text
Warranty
Linear Performance Warranty
Product Warranty
Performance Warranty
```

## Inverter Heading Examples

### DC Input

```text
Input DC
Input (DC)
Input data
Input data (DC)
PV Input
```

### Storage / Battery

```text
Input data (Storage)
Battery
Battery connection
Storage
```

### AC Output

```text
Output AC
Output (AC)
Output data
Grid side
AC Output
```

### Protection

```text
Protection
Protection devices
Protective devices
Protection & Function
Features & Protections
```

### General

```text
General Data
System Data
Mechanical Data
Technical data
Technical Specification
```

### Communication

```text
Interfaces
Communication
Data interface
Display
```

## Heading Detection Rules

Normalize headings before matching:

- lowercase
- remove extra whitespace
- normalize unicode punctuation
- normalize hyphen variants
- ignore repeated spaces

Do not require exact matches.

Use phrase-based matching and scoring.

## Section Boundaries

A section normally begins at a heading and continues until the next known heading.

If no heading is found, use nearby page context and table labels.

## Ambiguous Headings

Some headings are broad:

```text
Technical Data
Specifications
General Data
```

Use surrounding rows to classify them.

For example:

- contains MPPT, DC voltage, PV input → inverter DC input
- contains Pmax, Voc, Isc → module electrical
- contains dimensions, weight → mechanical/general
