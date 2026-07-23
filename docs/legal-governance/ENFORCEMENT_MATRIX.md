# Capability and Public Legal Matrix

## Authenticated capability enforcement

| Capability | Routes/actions | Audience | Workflow | Blocking |
|---|---|---|---|---|
| `library.private_workspace.access` | My Library, private storage, private upload/import | Subscribers | `subscriber-checkout` | Yes |
| `publisher.submission` | Publisher uploads, saves, review submissions | Publishers | `publisher-submission` | Yes |
| `manufacturer.portal.access` | Manufacturer portal and managed company/product actions | Manufacturers | `manufacturer-onboarding` | Yes |
| `api.private.access` | Reserved for authenticated private API endpoints | API clients | `api-mcp-access` | Yes when attached |
| `platform.registered.access` | Reserved for registered-only platform transitions | Registered Users | `registration` | Yes when attached |

Legal Counsel and Super Administrator routes are not currently mapped to public operational-term workflows. There is no general administrative bypass inside the access service.

## Public footer

| Label source | Manifest slug | Public viewing acceptance | Required footer entry |
|---|---|---|---|
| Current governed title | `website-terms-of-use` | No | Yes |
| Current governed title | `privacy-policy` | No | Yes |
| Current governed title | `cookie-policy` | No | Yes |
| Current governed title | `acceptable-use-policy` | No | No |
| Current governed title | `intellectual-property-and-licensing-policy` | No | No |

Only currently Published and effective public versions appear. Viewing never creates acceptance evidence.

## Cookie implementation finding

The current application code uses authentication/session, CSRF, appearance, and sidebar-preference storage. No analytics or marketing integration was identified. Accordingly, no decorative consent banner was added. Any future non-essential technology requires a functional consent mechanism and Cookie Register update before loading it.
