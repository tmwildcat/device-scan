<?php

declare(strict_types=1);

namespace App\DeviceScan\Golden;

final class GoldenJsonValidator
{
    /**
     * @return list<string>
     */
    public function validate(array $golden): array
    {
        $errors = [];

        foreach (['schema_version', 'compiler_version', 'record_type', 'device_type', 'identity', 'source_datasheet', 'manufacturer', 'model', 'engineering', 'source_provenance', 'validation_issues', 'extraction_warnings'] as $key) {
            if (! array_key_exists($key, $golden)) {
                $errors[] = "missing_key:{$key}";
            }
        }

        if (($golden['schema_version'] ?? null) !== GoldenJsonBuilder::SCHEMA_VERSION) {
            $errors[] = 'invalid_schema_version';
        }

        $deviceType = $golden['device_type'] ?? null;

        if (! in_array($deviceType, ['module', 'inverter'], true)) {
            $errors[] = 'invalid_device_type';
        }

        if (! is_array($golden['identity'] ?? null)) {
            $errors[] = 'identity_must_be_object';
        }

        if (! is_array($golden['source_datasheet'] ?? null)) {
            $errors[] = 'source_datasheet_must_be_object';
        }

        if (! is_array($golden['engineering'] ?? null)) {
            $errors[] = 'engineering_must_be_object';
        }

        if (! is_array($golden['source_provenance'] ?? null)) {
            $errors[] = 'source_provenance_must_be_array';
        }

        if (! is_array($golden['validation_issues'] ?? null)) {
            $errors[] = 'validation_issues_must_be_array';
        }

        if (! is_array($golden['extraction_warnings'] ?? null)) {
            $errors[] = 'extraction_warnings_must_be_array';
        }

        if ($deviceType === 'module') {
            $errors = [...$errors, ...$this->validateModule($golden)];
        }

        if ($deviceType === 'inverter') {
            $errors = [...$errors, ...$this->validateInverter($golden)];
        }

        return array_values(array_unique($errors));
    }

    /**
     * @return list<string>
     */
    private function validateModule(array $golden): array
    {
        $engineering = $golden['engineering'] ?? [];
        $errors = [];

        foreach (['electrical_stc', 'mechanical', 'operating_conditions', 'temperature_characteristics', 'warranty', 'certifications', 'packaging', 'country_manufacturing_metadata'] as $key) {
            if (! array_key_exists($key, $engineering)) {
                $errors[] = "missing_module_engineering_key:{$key}";
            }
        }

        $models = $engineering['electrical_stc']['models'] ?? null;

        if (! is_array($models)) {
            $errors[] = 'module_stc_models_must_be_array';
        }

        foreach (['manufacturer', 'display_name', 'power_class_w', 'model_series', 'model_variants'] as $key) {
            if (! array_key_exists($key, $golden['identity'] ?? [])) {
                $errors[] = "missing_module_identity_key:{$key}";
            }
        }

        return $errors;
    }

    /**
     * @return list<string>
     */
    private function validateInverter(array $golden): array
    {
        $engineering = $golden['engineering'] ?? [];
        $errors = [];

        foreach (['dc_input', 'ac_output', 'rated_power_conditions', 'protection', 'central_specific', 'storage_hybrid'] as $key) {
            if (! array_key_exists($key, $engineering)) {
                $errors[] = "missing_inverter_engineering_key:{$key}";
            }
        }

        if (! is_array($engineering['rated_power_conditions'] ?? null)) {
            $errors[] = 'rated_power_conditions_must_be_array';
        }

        foreach (['manufacturer', 'display_name', 'power_class_kw', 'model_series', 'model_name', 'inverter_device_type'] as $key) {
            if (! array_key_exists($key, $golden['identity'] ?? [])) {
                $errors[] = "missing_inverter_identity_key:{$key}";
            }
        }

        return $errors;
    }
}
