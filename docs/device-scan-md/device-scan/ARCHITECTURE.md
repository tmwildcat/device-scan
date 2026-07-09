# DeviceScan Architecture

Version: 1.0  
Project: LineWatt Library  
Status: Active  
Audience: Codex, engineers, contributors

## High-Level Architecture

DeviceScan should be understood as two layers:

1. Document understanding layer
2. Domain compiler layer

```text
PDF
  ↓
Document understanding
  ↓
Intermediate document representation
  ↓
ModuleCompiler / InverterCompiler
  ↓
Canonical DTO
```

## Document Understanding Layer

This layer may include:

- native PDF text extraction
- OCR fallback
- word extraction
- line reconstruction
- block detection
- table region detection
- grid reconstruction
- OCR/native text fusion
- canonical text tagging

This layer is allowed to be shared by module and inverter compilers.

It should not contain product-specific business logic beyond generic tagging and extraction helpers.

## Domain Compiler Layer

This layer is product-specific.

It should contain separate compiler paths for:

```text
ModuleCompiler
InverterCompiler
```

The module compiler understands how module datasheets are organized.

The inverter compiler understands how inverter datasheets are organized.

Do not force both into a universal electrical table interpreter.

## Preferred Pipeline

```text
Source PDF
  ↓
Page extraction
  ↓
Native text and/or OCR text
  ↓
Lines, blocks, table regions, grids
  ↓
Section detection
  ↓
Section classification
  ↓
Domain-specific extraction
  ↓
Canonical DTO
```

## Section-First Design

Datasheets commonly include clear headings.

Examples for modules:

- Electrical Parameters at STC
- Electrical Data (NOCT)
- Mechanical Parameters
- Operating Conditions
- Temperature Characteristics
- Warranty

Examples for inverters:

- Input Data (DC)
- Output Data (AC)
- Battery Connection
- Protection Devices
- General Data
- Interfaces

The compiler should detect these sections and then extract known fields inside them.

## Table Geometry Is Secondary

Table geometry helps answer questions like:

- which values belong to which model?
- which cells form a row?
- which header applies to which column?
- which grouped cell applies across multiple columns?

But geometry should not be the only way to decide what a section means.

Heading and surrounding text are often more reliable.

## Canonical DTOs

The final output should be DTOs, not raw grids or raw tables.

Expected entry points:

```php
$moduleDto = $moduleCompiler->compile($pdfPath);
$inverterDto = $inverterCompiler->compile($pdfPath);
```

DTOs should preserve source metadata and confidence where possible.

## Refactoring Policy

The existing implementation is experimental.

Codex may reuse or replace existing components.

The target architecture is not fixed. The target outcome is reliable extraction from real module and inverter datasheets.
