# Datasheet Compiler

Version: 1.0  
Project: LineWatt Library  
Status: Active  
Audience: Codex, engineers, contributors

## Compiler Concept

The compiler transforms a manufacturer datasheet into a canonical DTO.

```text
PDF Datasheet
  ↓
Intermediate document representation
  ↓
Domain compiler
  ↓
Canonical DTO
```

The compiler should optimize for engineering accuracy, not visual fidelity.

## Compiler Entry Points

Target public interfaces:

```php
$moduleDto = $moduleCompiler->compile($pdfPath);
$inverterDto = $inverterCompiler->compile($pdfPath);
```

These interfaces may evolve, but the conceptual output should remain DTO-oriented.

## Shared Responsibilities

Shared lower-level utilities may include:

- PDF loading
- OCR/native extraction
- text normalization
- heading detection helpers
- table/grid helpers
- unit normalization
- numeric parsing
- source metadata capture

## Domain Responsibilities

Module compiler owns module-specific interpretation.

Inverter compiler owns inverter-specific interpretation.

Avoid generic abstractions that obscure the engineering domain.

## Intermediate Representation

The compiler may consume:

- pages
- text blocks
- lines
- words
- table grids
- source coordinates
- metadata

A document compiler may use multiple inputs at once.

It is not limited to one table at a time.

## Extraction Strategy

Recommended order:

1. Identify pages likely to contain technical data.
2. Detect headings and section boundaries.
3. Classify sections into canonical section types.
4. Extract known parameters from each section.
5. Normalize values and units.
6. Attach source metadata and confidence.
7. Assemble canonical DTO.

## Do Not Overbuild

Do not build:

- a universal table compiler
- a universal electrical schema
- a full document layout reproduction engine
- complex corpus management before compiler stability

## Confidence and Review

The compiler should be comfortable returning partial results.

Missing fields should be explicit.

Low-confidence fields should be marked.

Human review can be added later.
