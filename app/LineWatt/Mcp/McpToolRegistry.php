<?php

namespace App\LineWatt\Mcp;

class McpToolRegistry
{
    /**
     * @return array<int,array<string,mixed>>
     */
    public function all(): array
    {
        return [
            $this->tool(
                'search_modules',
                'Search Modules',
                'Search published central module engineering records.',
                ['query', 'manufacturer', 'power_min_w', 'power_max_w', 'technology']
            ),
            $this->tool(
                'search_inverters',
                'Search Inverters',
                'Search published central inverter engineering records.',
                ['query', 'manufacturer', 'power_min_kw', 'power_max_kw', 'device_type']
            ),
            $this->tool(
                'get_engineering_record',
                'Get Engineering Record',
                'Fetch one published central engineering record by UUID.',
                ['record_uuid']
            ),
            $this->tool(
                'compare_modules',
                'Compare Modules',
                'Prepare a read-only comparison for two or three module engineering records.',
                ['record_uuids']
            ),
            $this->tool(
                'compare_inverters',
                'Compare Inverters',
                'Prepare a read-only comparison for two or three inverter engineering records.',
                ['record_uuids']
            ),
            $this->tool(
                'export_pan',
                'Export PAN',
                'Prepare a module PAN export from a published module engineering record.',
                ['record_uuid']
            ),
            $this->tool(
                'export_ond',
                'Export OND',
                'Prepare an inverter OND export from a published inverter engineering record.',
                ['record_uuid']
            ),
        ];
    }

    /**
     * @return array<string,mixed>|null
     */
    public function find(string $name): ?array
    {
        foreach ($this->all() as $tool) {
            if ($tool['name'] === $name) {
                return $tool;
            }
        }

        return null;
    }

    /**
     * @return array<string,mixed>
     */
    private function tool(string $name, string $title, string $description, array $properties): array
    {
        return [
            'name' => $name,
            'title' => $title,
            'description' => $description,
            'required_scope' => 'mcp.tools',
            'status' => 'placeholder',
            'visibility' => 'published_central_only',
            'input_schema' => [
                'type' => 'object',
                'properties' => collect($properties)
                    ->mapWithKeys(fn (string $property) => [
                        $property => [
                            'type' => $property === 'record_uuids' ? 'array' : 'string',
                            'description' => str_replace('_', ' ', $property),
                        ],
                    ])
                    ->all(),
            ],
        ];
    }
}
