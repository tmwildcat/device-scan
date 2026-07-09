# MCP Server Foundation

This document defines the LineWatt Library MCP foundation.

MCP is not publicly exposed in v1.

The current implementation is an internal gateway foundation for future curated MCP tools.

## Goals

- Define a stable MCP tool registry.
- Require trusted Internal App Access credentials.
- Log every authenticated MCP tool call.
- Keep MCP behind the private/internal API boundary.
- Prevent direct database access from MCP tools.

## Internal Routes

Foundation routes:

```text
GET  /api/internal/mcp/tools
POST /api/internal/mcp/call
```

These routes require Internal App Access credentials and the `mcp.tools` scope.

They are not public customer API routes.

## Access Model

MCP callers must be registered as trusted internal applications.

Examples:

- Future LineWatt MCP Gateway.
- Internal testing harness.
- LineWatt-owned AI/tool gateway.

Credentials are managed in:

```text
/admin/platform/internal-app-access
```

Required scope:

```text
mcp.tools
```

## Tool Registry

The current registry is implemented in:

```text
App\LineWatt\Mcp\McpToolRegistry
```

Initial tools:

- `search_modules`
- `search_inverters`
- `get_engineering_record`
- `compare_modules`
- `compare_inverters`
- `export_pan`
- `export_ond`

All tools are marked as:

```text
visibility = published_central_only
status = placeholder
```

## Execution Policy

MCP must not query database tables directly.

Future tool execution should call:

- Existing internal API endpoints.
- Shared application services.
- Existing entitlement checks.
- Existing PDF access policy checks.
- Existing export services.

For now, `/api/internal/mcp/call` records the call and returns a placeholder response.

## Visibility Rules

Foundation policy:

- No tenant/private records.
- No unpublished central records.
- Published central records only when future execution is enabled.
- Private access may be added later through an explicit entitlement and user context model.

## Audit Logging

MCP calls are logged in:

```text
mcp_audit_logs
```

Logged fields include:

- Internal application.
- Tool name.
- Action.
- Status.
- Status code.
- Input summary.
- Response summary.
- IP.
- User agent.
- Timestamp.

Do not store raw sensitive payloads in audit summaries.

## Future Public Rollout

Before exposing MCP publicly:

1. Add a dedicated MCP gateway service.
2. Map MCP identities to user/account entitlements.
3. Route tool execution through internal API/service layer.
4. Add published/private visibility rules per caller.
5. Add rate limits per gateway/client/user.
6. Add tool-specific validation and export safeguards.
7. Add monitoring dashboards for tool usage and failures.
8. Publish a curated MCP tool manifest, not raw database access.
