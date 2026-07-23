<?php

namespace Database\Seeders;

use App\Models\PlatformService;
use Illuminate\Database\Seeder;

class PlatformServiceSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->services() as $service) {
            PlatformService::query()->updateOrCreate(
                ['service_key' => $service['service_key']],
                $service
            );
        }
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function services(): array
    {
        $environment = app()->environment('production') ? 'production' : 'local';

        return [
            [
                'uuid' => '66666666-0000-4000-8000-000000000001',
                'name' => 'Internal Library API',
                'service_key' => 'internal-library-api',
                'service_type' => 'internal_api',
                'status' => 'active',
                'environment' => $environment,
                'description' => 'Private first-party API for LineWatt-owned applications and internal services.',
                'required_scopes' => ['library.search', 'library.view_record'],
                'endpoint_url' => '/api/internal',
                'health_check_url' => '/api/internal/health',
            ],
            [
                'uuid' => '66666666-0000-4000-8000-000000000002',
                'name' => 'MCP Gateway',
                'service_key' => 'mcp-gateway',
                'service_type' => 'mcp_gateway',
                'status' => 'active',
                'environment' => $environment,
                'description' => 'Internal MCP foundation gateway. Public exposure and real tool execution remain disabled.',
                'required_scopes' => ['mcp.tools'],
                'endpoint_url' => '/api/internal/mcp',
                'health_check_url' => '/api/internal/mcp/tools',
                'metadata' => ['public_exposure' => false, 'execution' => 'placeholder'],
            ],
            [
                'uuid' => '66666666-0000-4000-8000-000000000003',
                'name' => 'Module Compiler',
                'service_key' => 'module-compiler',
                'service_type' => 'compiler',
                'status' => 'active',
                'environment' => $environment,
                'description' => 'Solar PV module datasheet compiler service.',
                'required_scopes' => ['library.private_compile'],
            ],
            [
                'uuid' => '66666666-0000-4000-8000-000000000004',
                'name' => 'Inverter Compiler',
                'service_key' => 'inverter-compiler',
                'service_type' => 'compiler',
                'status' => 'active',
                'environment' => $environment,
                'description' => 'Solar inverter datasheet compiler service.',
                'required_scopes' => ['library.private_compile'],
            ],
            [
                'uuid' => '66666666-0000-4000-8000-000000000005',
                'name' => 'Object Storage',
                'service_key' => 'object-storage',
                'service_type' => 'storage',
                'status' => 'active',
                'environment' => $environment,
                'description' => 'S3-compatible artifact storage for PDFs, compiled JSON and review artifacts.',
                'required_scopes' => ['library.storage'],
            ],
            [
                'uuid' => '66666666-0000-4000-8000-000000000006',
                'name' => 'Email Delivery',
                'service_key' => 'email-delivery',
                'service_type' => 'email',
                'status' => 'active',
                'environment' => $environment,
                'description' => 'Outbound email delivery for workflow notifications and account flows.',
                'required_scopes' => ['library.notifications'],
            ],
            [
                'uuid' => '66666666-0000-4000-8000-000000000007',
                'name' => 'Notification Service',
                'service_key' => 'notification-service',
                'service_type' => 'notification',
                'status' => 'active',
                'environment' => $environment,
                'description' => 'In-app notifications and notification delivery tracking.',
                'required_scopes' => ['library.notifications'],
            ],
            [
                'uuid' => '66666666-0000-4000-8000-000000000008',
                'name' => 'Search Index',
                'service_key' => 'search-index',
                'service_type' => 'search_index',
                'status' => 'active',
                'environment' => $environment,
                'description' => 'Engineering Search indexing and metadata retrieval service.',
                'required_scopes' => ['library.search'],
            ],
        ];
    }
}
