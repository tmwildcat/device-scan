# Golden JSON Contract

Golden JSON fixtures stabilize compiler output for LineWatt Library Engineering Records.
They are filesystem fixtures, not database rows, and live under:

```text
storage/app/private/device-scan/golden/modules
storage/app/private/device-scan/golden/inverters
```

## Common Shape

Every golden fixture uses:

```json
{
  "schema_version": "linewatt.golden.v0.1",
  "compiler_version": "",
  "record_type": "",
  "device_type": "module | inverter",
  "identity": {},
  "source_datasheet": {},
  "manufacturer": "",
  "model": {},
  "engineering": {},
  "source_provenance": [],
  "validation_issues": [],
  "validation": {},
  "extraction_warnings": [],
  "sections": [],
  "quality": {},
  "raw_compiler_output": {}
}
```

`source_provenance` is flattened from source-aware values and keeps:

```json
{
  "field": "",
  "page": null,
  "section": null,
  "source_text": null,
  "confidence": null
}
```

## Module Engineering Record

One module datasheet may create many golden module records. Each record represents one model/rating row or column.

Module identity includes:

```json
{
  "manufacturer": "",
  "series": "",
  "family": "",
  "model_series": "",
  "model_name": "",
  "model_variants": [],
  "display_name": "",
  "power_class_w": null,
  "technology": ""
}
```

Module engineering includes:

```json
{
  "electrical_stc": {},
  "mechanical": {},
  "operating_conditions": {},
  "temperature_characteristics": {},
  "warranty": {},
  "certifications": {},
  "packaging": {},
  "country_manufacturing_metadata": null
}
```

## Inverter Engineering Record

Inverter identity includes:

```json
{
  "manufacturer": "",
  "series": "",
  "family": null,
  "model_series": "",
  "model_name": "",
  "display_name": "",
  "power_class_kw": null,
  "technology": null,
  "inverter_device_type": "string_inverter | hybrid_inverter | central_inverter | storage_inverter | accessory | unknown"
}
```

Inverter engineering includes:

```json
{
  "dc_input": {},
  "ac_output": {},
  "rated_power_conditions": [],
  "protection": {},
  "central_specific": {},
  "storage_hybrid": {}
}
```

## Commands

Generate fixtures:

```bash
php artisan device-scan:generate-golden-json --device=module
php artisan device-scan:generate-golden-json --device=inverter
```

Validate fixtures:

```bash
php artisan device-scan:validate-golden-json
```
