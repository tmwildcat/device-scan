# Next Steps

Version: 1.0  
Project: LineWatt Library  
Status: Active  
Audience: Codex, engineers, contributors

## Immediate Priority

Build the ModuleCompiler first.

Recommended sequence:

1. Define Module DTO shape.
2. Build ModuleSectionDetector.
3. Detect STC, NOCT/NMOT, mechanical, operating, warranty, packaging, curves, and marketing sections.
4. Build focused module extractors.
5. Run against local module corpus.
6. Refine based on real failures.

## Second Priority

Build the InverterCompiler.

Recommended sequence:

1. Define Inverter DTO shape.
2. Build InverterSectionDetector.
3. Detect DC input, storage input, AC output, backup output, protection, communication, general, compliance, curves, and marketing sections.
4. Build focused inverter extractors.
5. Run against local inverter corpus.
6. Refine based on real failures.

## Defer

Do not build these yet:

- corpus approval workflow
- golden DTO comparison system
- large corpus management UI
- generic document compiler
- universal electrical table engine

## Later

After compiler output stabilizes:

- create golden DTOs for representative datasheets
- add regression tests
- build review UI
- persist approved module/inverter records
- add confidence dashboards

## Guiding Rule

Use the corpus to guide implementation.

Do not solve imaginary formats before solving the actual module and inverter datasheets available locally.
