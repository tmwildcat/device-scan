# LineWatt Library Overview

Version: 1.0  
Project: LineWatt Library  
Status: Active  
Audience: Codex, engineers, contributors

## What This Project Is

LineWatt Library is a focused compiler for PV module and inverter datasheets.

It reads manufacturer datasheets and extracts engineering facts into canonical structured data.

The compiler exists to support downstream engineering workflows such as:

- module library creation
- inverter library creation
- PV array sizing
- string sizing
- SLD and protection design
- proposal engineering
- datasheet review and approval

## What This Project Is Not

This is not a general OCR platform.

This is not a general PDF table extraction framework.

This is not a universal electrical document compiler.

This is not trying to reconstruct the exact visual layout of a datasheet.

The objective is engineering extraction, not PDF reproduction.

## Supported Equipment

The initial scope is deliberately limited to:

1. PV modules
2. String inverters
3. Hybrid inverters

Backup boxes, meters, batteries, and other equipment may appear in the corpus as negative or related examples, but they are not the primary compiler target unless explicitly added later.

## Core Principle

Manufacturer datasheets are written for engineers. They usually contain domain headings and repeated patterns.

The compiler should exploit this.

The preferred approach is:

```text
PDF
  ↓
Text / OCR / Native extraction
  ↓
Pages / lines / blocks / grids
  ↓
Heading and section detection
  ↓
Domain-specific extractors
  ↓
Canonical DTO
```

Not:

```text
PDF
  ↓
Generic table parser
  ↓
Universal electrical analyzer
  ↓
Maybe useful output
```

## Success Criteria

The compiler is successful when it can process a representative corpus of real module and inverter datasheets and extract:

For modules:

- models
- STC electrical data
- NOCT/NMOT electrical data where present
- bifacial gain variants where present
- temperature coefficients
- operating limits
- mechanical data
- warranty data

For inverters:

- models
- DC input limits
- storage input data where present
- AC output data
- protection features
- communication interfaces
- general/mechanical data
- compliance standards

## Corpus-Driven Development

The compiler should evolve from real datasheets, not theoretical layouts.

Corpus location:

```text
storage/app/private/device-scan/corpus/modules
storage/app/private/device-scan/corpus/inverters
```

These files are local-only study material and are intentionally not committed.

## Engineering Mindset

When deciding between two approaches, prefer the one that is:

- simpler
- easier to explain
- easier to test
- more robust against real manufacturer formats
- specific to modules and inverters

Do not optimize for theoretical generality.
