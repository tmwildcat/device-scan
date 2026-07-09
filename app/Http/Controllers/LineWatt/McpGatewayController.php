<?php

namespace App\Http\Controllers\LineWatt;

use App\Http\Controllers\Controller;
use App\LineWatt\Mcp\McpToolRegistry;
use App\Models\InternalApplication;
use App\Models\McpAuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class McpGatewayController extends Controller
{
    public function tools(Request $request, McpToolRegistry $registry): JsonResponse
    {
        $this->audit($request, null, 'tools/list', 'success', Response::HTTP_OK, [
            'tool_count' => count($registry->all()),
        ]);

        return response()->json([
            'server' => 'linewatt-library-mcp-foundation',
            'status' => 'foundation_only',
            'tools' => $registry->all(),
        ]);
    }

    public function call(Request $request, McpToolRegistry $registry): JsonResponse
    {
        $data = $request->validate([
            'tool' => ['required', 'string'],
            'arguments' => ['nullable', 'array'],
        ]);

        $toolName = $data['tool'];
        $tool = $registry->find($toolName);

        if ($tool === null) {
            $this->audit($request, $toolName, 'tools/call', 'unknown_tool', Response::HTTP_NOT_FOUND);

            return response()->json([
                'error' => 'unknown_tool',
                'message' => 'The requested MCP tool is not registered.',
            ], Response::HTTP_NOT_FOUND);
        }

        $arguments = $data['arguments'] ?? [];
        $payload = $this->placeholderPayload($tool, $arguments);

        $this->audit($request, $toolName, 'tools/call', 'placeholder', Response::HTTP_OK, [
            'placeholder' => true,
            'argument_keys' => array_keys($arguments),
        ]);

        return response()->json($payload);
    }

    /**
     * @param array<string,mixed> $tool
     * @param array<string,mixed> $arguments
     * @return array<string,mixed>
     */
    private function placeholderPayload(array $tool, array $arguments): array
    {
        return [
            'tool' => $tool['name'],
            'status' => 'placeholder',
            'message' => 'MCP tool execution is not publicly enabled yet. This foundation endpoint records the call and will later delegate to the internal API/service layer.',
            'visibility' => 'published_central_only',
            'will_call' => 'internal_api_or_service_layer',
            'arguments_received' => array_keys($arguments),
        ];
    }

    /**
     * @param array<string,mixed>|null $responseSummary
     */
    private function audit(Request $request, ?string $toolName, string $action, string $status, int $statusCode, ?array $responseSummary = null): void
    {
        $application = $request->attributes->get('internal_application');

        McpAuditLog::query()->create([
            'internal_application_id' => $application instanceof InternalApplication ? $application->id : null,
            'tool_name' => $toolName,
            'action' => $action,
            'status' => $status,
            'status_code' => $statusCode,
            'input_summary' => [
                'keys' => array_keys($request->all()),
                'argument_keys' => array_keys((array) $request->input('arguments', [])),
            ],
            'response_summary' => $responseSummary,
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 2000),
        ]);
    }
}
