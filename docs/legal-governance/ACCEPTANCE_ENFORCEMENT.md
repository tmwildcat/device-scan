# Acceptance Enforcement

## Architecture

`LegalAccessService` is the sole legal eligibility decision point. Route middleware, browser controllers, APIs, and future action guards consume its immutable `LegalAccessDecision`; they must not query acceptance tables independently.

The service maps a stable capability key to an active workflow, validates its audience and ordered requirements, resolves Published versions, reuses or creates deduplicated obligations, checks immutable acceptance evidence, and separates blocking from optional obligations. Missing or invalid protected configuration fails closed.

## Protecting a capability

1. Add a unique capability key and stable workflow slug to `config/legal-governance.php`.
2. Configure the workflow, audience, ordered requirements, statements, and blocking modes in Legal Governance.
3. Publish the reviewed document versions; Drafts never satisfy enforcement.
4. Apply `legal.acceptance:<capability>` after authentication, verification, and resource authorisation.
5. Run `php artisan legal:validate-enforcement`; use `--allow-staged` only in non-production preparation.

Browser GET requests retain a user-scoped internal destination and redirect to `legal.acceptance.index`. Non-idempotent actions are not replayed. JSON requests receive a structured 403 response. Acceptance routes have no enforcement middleware and therefore cannot loop.

## Evidence

The server selects the obligation, exact Published version, workflow requirement, acceptance type, and statement. Explicit affirmation creates immutable manifest-backed acceptance evidence, completes the locked obligation, and emits audit events in one transaction. Material Changes create separate obligations and evidence.

## Diagnosis

Inspect the user's Legal Status page, active workflow validation, selected Published versions, pending obligations, and safe audit identifiers. Never resolve a blockage by editing acceptance evidence or publishing an unreviewed document.
