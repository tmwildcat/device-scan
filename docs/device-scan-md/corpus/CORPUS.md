# DeviceScan Corpus

Version: 1.0  
Project: LineWatt Library  
Status: Active  
Audience: Codex, engineers, contributors

## Purpose

The corpus is local study material for developing the datasheet compiler.

It contains real manufacturer PDFs for PV modules and inverters.

The corpus teaches the compiler how datasheets are actually organized.

## Location

```text
storage/app/private/device-scan/corpus/modules
storage/app/private/device-scan/corpus/inverters
```

## Git Policy

Corpus PDFs are ignored by git.

They are not production assets.

They should not be deployed with the application.

They should not be required for normal application runtime.

## Use

Use the corpus to:

- study manufacturer patterns
- test extraction manually
- identify repeated headings
- identify table layouts
- validate module/inverter-specific extraction
- discover edge cases

Do not build a large corpus management system yet.

## Future Golden Outputs

Golden DTOs may be added later after compiler output stabilizes.

Do not introduce golden DTO workflows prematurely.

For now, focus on extracting correct engineering data across the local corpus.
