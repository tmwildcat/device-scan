# Acceptance Evidence

Each acceptance references the exact document version, workflow and obligation where applicable; actor, subject and organisation references; action type and statement; UTC time; locale; configured IP/user-agent/request evidence; presented checksum; manifest checksum; and canonical evidence checksum.

Canonical JSON recursively sorts object keys and uses deterministic unescaped JSON before SHA-256 hashing. Passwords, tokens, session secrets, unrelated browsing, and device fingerprints are excluded.

Acceptances are append-only. Withdrawal of optional consent requires a later legal event rather than rewriting the original evidence. Account closure does not automatically delete retained legal evidence.

The Evidence Exports page requires `legal.acceptances.export`, an allowed subject type/reference, purpose or case reference, and confirmation. JSON bundles are subject-scoped and omit IP addresses, full user agents, request references, and raw evidence metadata; each export records checksum, path, requesting identity, and case reference in the audit log.
