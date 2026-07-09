# Engineering Decisions

Version: 1.0  
Project: LineWatt Library  
Status: Active  
Audience: Codex, engineers, contributors

## Decision 1: This Is a Compiler, Not an OCR Product

The project should be treated as a compiler for manufacturer datasheets.

The input is a PDF datasheet.

The output is canonical engineering data.

Intermediate OCR, text, block, and table representations are implementation details.

## Decision 2: Do Not Recreate the PDF

The compiler does not need to recreate the original PDF layout.

It only needs to extract trusted engineering facts.

Perfect visual table reconstruction is not required if reliable DTO extraction can be achieved by simpler means.

## Decision 3: Scope Is Narrow

The supported scope is:

1. PV module datasheets
2. string inverter datasheets
3. hybrid inverter datasheets

Avoid building generic support for arbitrary electrical equipment or arbitrary datasheets.

## Decision 4: Module and Inverter Paths Should Be Separate

Modules and inverters have different engineering structures.

Modules revolve around:

- STC electrical data
- NOCT/NMOT data
- bifacial variants
- mechanical parameters
- operating conditions
- warranty

Inverters revolve around:

- DC input
- storage input
- AC output
- efficiency
- protection
- communication
- general data
- compliance

They may share low-level extraction utilities, but domain interpretation should remain separate.

## Decision 5: Heading-First / Section-First

Use headings and document sections before relying on table geometry.

A heading such as `ELECTRICAL PARAMETERS AT STC` is stronger evidence than a grid shape.

A heading such as `Protection Devices` is stronger evidence than isolated words like `Yes`.

## Decision 6: Table Geometry Is a Helper

Table geometry should help align values with models and parameters.

It should not dominate document understanding.

If a document contains grouped cells, merged headings, or split tables, the compiler should use section context and parameter names to recover meaning.

## Decision 7: Corpus Is Local Study Material

Corpus PDFs live under:

```text
storage/app/private/device-scan/corpus
```

They are ignored by git.

They are not production assets.

They are used to study real manufacturer formats and improve the compiler.

## Decision 8: Existing Code Is Prototype Work

Existing code may contain valuable solutions for:

- native extraction
- OCR
- table regions
- grid detection
- hybrid text fusion
- canonical parameter tagging

But none of it is final.

Codex may modify, refactor, replace, or remove existing pieces where that improves the compiler.

## Decision 9: Do Not Freeze Golden DTOs Too Early

Do not build an elaborate corpus approval or golden DTO system until the compiler output stabilizes.

Early work should focus on extracting correct data across the corpus.

Golden DTOs can be introduced later for regression testing.

## Decision 10: Preserve Source Metadata

Extracted facts should ideally carry source metadata:

- source page
- source section
- source text
- source row/cell if available
- confidence

This supports human review and debugging.
