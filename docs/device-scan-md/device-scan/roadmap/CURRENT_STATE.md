# Current State

Version: 1.0  
Project: LineWatt Library  
Status: Active research prototype  
Audience: Codex, engineers, contributors

## Current Direction

The project is moving from table-by-table interpretation toward document-level compilers.

The target is:

```text
ModuleCompiler
InverterCompiler
```

## What Exists

Prototype work exists for:

- page analysis
- OCR/native extraction
- table region detection
- grid detection
- hybrid cell text fusion
- canonical grid annotation
- table header analysis
- early module electrical interpretation

## What Is Not Yet Proven

The current table interpreter is not yet a final solution.

The current architecture may be refactored.

The current DTOs may evolve.

Golden outputs are not yet frozen.

## Corpus Status

Module corpus exists locally under:

```text
storage/app/private/device-scan/corpus/modules
```

Inverter corpus exists locally under:

```text
storage/app/private/device-scan/corpus/inverters
```

Corpus PDFs are ignored by git.

## Key Learning So Far

- Module datasheets require section-first interpretation.
- Inverter datasheets require separate DC, AC, storage, protection, and general sections.
- Marketing pages and curve sections should generally be ignored.
- Bifacial/BNPI/gain data must not overwrite STC values.
- Table fragments and merged cells are common.
- The compiler should focus on engineering data rather than PDF reconstruction.
