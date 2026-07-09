<?php

use App\Models\InternalApplication;
use App\Models\McpAuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createMcpApplication(array $scopes = ['mcp.tools']): array
{
    $secret = InternalApplication::generateSecret();
    $application = new InternalApplication([
        'name' => 'LineWatt MCP Gateway',
        'environment' => 'local',
        'status' => 'active',
        'allowed_domains' => ['https://mcp.linewatt.test'],
        'scopes' => $scopes,
    ]);
    $application->setPlainSecret($secret);
    $application->save();

    return [$application, $secret];
}

function mcpHeaders(InternalApplication $application, string $secret): array
{
    return [
        'X-LineWatt-Client-Id' => $application->client_id,
        'X-LineWatt-Client-Secret' => $secret,
    ];
}

it('lists the curated MCP tools for an authorized internal application', function () {
    [$application, $secret] = createMcpApplication();

    $this->getJson('/api/internal/mcp/tools', mcpHeaders($application, $secret))
        ->assertOk()
        ->assertJsonPath('status', 'foundation_only')
        ->assertJsonPath('tools.0.name', 'search_modules')
        ->assertJsonPath('tools.6.name', 'export_ond');

    expect(McpAuditLog::query()->where('action', 'tools/list')->count())->toBe(1);
});

it('requires the mcp tools scope', function () {
    [$application, $secret] = createMcpApplication(['library.search']);

    $this->getJson('/api/internal/mcp/tools', mcpHeaders($application, $secret))
        ->assertForbidden();
});

it('logs placeholder MCP tool calls', function () {
    [$application, $secret] = createMcpApplication();

    $this->postJson('/api/internal/mcp/call', [
        'tool' => 'search_modules',
        'arguments' => [
            'query' => '600W bifacial Jinko TOPCon',
        ],
    ], mcpHeaders($application, $secret))
        ->assertOk()
        ->assertJsonPath('tool', 'search_modules')
        ->assertJsonPath('status', 'placeholder')
        ->assertJsonPath('will_call', 'internal_api_or_service_layer');

    $log = McpAuditLog::query()->where('tool_name', 'search_modules')->firstOrFail();

    expect($log->internal_application_id)->toBe($application->id)
        ->and($log->status)->toBe('placeholder')
        ->and($log->input_summary['argument_keys'])->toBe(['query']);
});

it('returns a clean error for unknown tools and audits the attempt', function () {
    [$application, $secret] = createMcpApplication();

    $this->postJson('/api/internal/mcp/call', [
        'tool' => 'delete_everything',
        'arguments' => [],
    ], mcpHeaders($application, $secret))
        ->assertNotFound()
        ->assertJsonPath('error', 'unknown_tool');

    expect(McpAuditLog::query()->where('tool_name', 'delete_everything')->where('status', 'unknown_tool')->exists())->toBeTrue();
});
