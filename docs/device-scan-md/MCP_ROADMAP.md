# MCP Roadmap

The MCP Server will be the public external integration layer for LineWatt Library.

Do not build MCP in v1.

## Role

MCP will expose curated engineering tools rather than raw database access.

It should serve AI agents, engineering assistants and controlled external tool integrations through explicit, entitlement-aware capabilities.

## Architecture

MCP should call:

- Private API endpoints.
- Shared application services.
- Existing entitlement checks.
- Existing PDF access policy checks.
- Existing export services.

MCP should not query LineWatt database tables directly.

## Future Tools

Candidate MCP tools:

- `search_modules`
- `search_inverters`
- `get_engineering_record`
- `compare_modules`
- `compare_inverters`
- `find_equivalent_products`
- `check_module_inverter_compatibility`
- `export_pan`
- `export_ond`

## Access

MCP access should be:

- Entitlement controlled.
- Audited.
- Rate limited.
- Scoped to published central records unless a user/session grants private access.
- PDF-policy aware.

## Phasing

V1:

- Sanctum for LineWatt-owned applications on different domains.
- First-party apps.
- swem2m apps.
- Internal service-to-service tokens.
- LineWatt Studio access.
- Admin-generated service keys.
- Internal-only MCP foundation routes protected by Internal App Access.
- Placeholder MCP tool registry and audit logging.

Later:

- MCP Gateway.
- Public AI/tool access.
- Entitlement-controlled tool calls.
- Private API/service-layer calls internally.

The MCP gateway should be registered as a trusted internal application or service gateway through Internal App Access. It should receive only the scopes needed for the curated MCP tools it exposes, and every call into the private API/service layer should remain audited.

Foundation details are documented in:

```text
MCP_SERVER_FOUNDATION.md
```

Do not install Passport for v1.

Use Passport only if LineWatt later needs third-party OAuth clients, external developer app registration or customer-managed client secret flows.
