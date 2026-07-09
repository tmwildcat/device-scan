# Existing Pipeline

Version: 1.0  
Project: LineWatt Library  
Status: Prototype  
Audience: Codex, engineers, contributors

## Purpose

This document describes the existing DeviceScan pipeline as prototype work.

Codex should study it before making changes, but should not treat it as final.

## Existing Concepts

The current implementation has explored these areas:

- source document representation
- page representation
- native PDF text extraction
- OCR fallback
- OCR word and line extraction
- block building
- engineering block classification
- table region detection
- native table region detection
- hybrid table region detection
- grid detection
- native grid detection
- hybrid OCR/native cell text fusion
- canonical grid debug annotation
- table header analysis
- canonical parameter normalization
- early module electrical table interpretation

## Valuable Existing Work

The following ideas are likely worth reusing or adapting:

### Native Extraction

Native PDF text is often cleaner than OCR for born-digital datasheets.

Use native words where reliable.

### OCR Fallback

Some datasheets or pages require OCR, especially where text is embedded in images.

OCR should remain available as fallback.

### Hybrid Fusion

Combining native text and OCR text can improve table cell content.

This idea is valuable, but implementation may be refactored.

### Table Region and Grid Detection

The prototype already detects table-like regions and reconstructs grids.

This is useful but should not become the entire compiler architecture.

### Canonical Parameter Tagging

Early canonical tags such as `open_circuit_voltage`, `maximum_power_current`, and `module_efficiency` are useful.

The dictionary should evolve into module-specific and inverter-specific parameter dictionaries.

## Known Weaknesses

The prototype has exposed real issues:

- page marketing content can be misclassified as technical data
- table fragments may be split across multiple grids
- curve legends can pollute electrical table detection
- merged cells and grouped headers are difficult to reconstruct geometrically
- universal electrical interpretation creates scope creep
- module and inverter patterns are different enough to justify separate compilers

## Refactoring Guidance

When touching existing code:

- keep useful low-level extraction capabilities
- remove over-generic engineering interpretation where needed
- prefer domain-specific names
- make section detection explicit
- move product knowledge into Module and Inverter compiler paths

## Non-Goal

Do not spend large effort making the old table interpreter universal.

The new direction is document compiler plus domain-specific extraction.
