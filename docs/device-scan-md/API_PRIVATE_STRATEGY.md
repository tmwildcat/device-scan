# Private API Strategy

LineWatt Library v1 does not expose a public developer API.

The REST/API layer exists to support LineWatt-owned applications and internal services only.

## Purpose

The private API supports:

- LineWatt Library frontend.
- LineWatt Studio.
- swem2m applications.
- Future LineWatt mobile applications.
- Internal services and jobs.
- Future MCP gateway calls.

It is not a public customer integration surface in v1.

## Route Strategy

Private API routes should be grouped clearly:

```text
/api/internal/...
```

or:

```text
/api/linewatt/...
```

The exact prefix can be chosen during API implementation, but it must communicate that the API is first-party and private.

## Access Model

V1 should use:

- Laravel Sanctum for cross-domain first-party token-based API access.
- First-party application tokens.
- Session/auth-protected access where appropriate.
- Admin-generated internal service tokens.
- LineWatt Studio access.
- swem2m app access where approved.

## Internal App Access

Super Admins manage first-party application credentials under:

```text
/admin/platform/internal-app-access
```

The UI language is **Internal App Access**, not public API keys.

Each registered first-party application stores:

- Application name.
- Client ID.
- Hashed secret.
- Allowed domains.
- Environment.
- Status.
- Scopes.
- Last-used metadata.

The raw secret is shown only once after creation or regeneration. Operators must copy it immediately.

Initial internal API routes live under:

```text
/api/internal/...
```

Requests must present first-party credentials, pass active status checks, pass scope checks, and generate an access log entry.

Do not implement:

- Public self-serve API keys.
- Public developer portal.
- Third-party OAuth clients.
- Customer app registration.
- OAuth2.
- Laravel Passport.

Passport remains out of scope unless LineWatt later makes a true third-party OAuth developer API decision.

Use Passport only if a future product decision requires third-party OAuth clients, external developer applications, customer-managed client secrets or scoped authorization-code flows.

## Token Abilities

Sanctum tokens should use explicit abilities.

Initial abilities:

- `library.search`
- `library.view_record`
- `library.download_pdf`
- `library.export`
- `library.compare`
- `library.private_upload`
- `library.private_compile`
- `library.storage`
- `library.notifications`

## Middleware

Private API routes should use:

- `auth:sanctum`.
- A `first_party_app` entitlement/scope check for token-based service access.
- Rate limiting.
- Audit logging.
- Entitlement checks where user-level capabilities are involved.

## Security Rules

Private API responses must:

- Not expose unpublished central records unless the caller is authorized.
- Not expose private tenant records unless the caller owns or is authorized for that tenant.
- Respect subscriber entitlements.
- Respect manufacturer/OEM ownership boundaries.
- Respect PDF access policy.
- Log sensitive access events.
- Be versioned before broader adoption.

## Public Wording

Avoid customer-facing wording such as:

- Public API.
- Developer API.
- API Keys for customers.

Use:

- Internal API.
- Internal App Access.
- Website Integration.
- MCP Server coming later.

## MCP Relationship

The future MCP gateway should call the private API or shared service layer.

MCP should not query the database directly.

MCP may be public later, but its public surface should be curated tools, entitlement controlled, and routed through the internal API/service layer.
