# Entitlement And Billing Notes

## Purpose

Keep LineWatt Library roles separate from paid feature access so Paddle can be added without rewriting workspace controllers.

## Current Model

Roles answer: who is this user?

Entitlements answer: what is this user allowed to use?

The current demo implementation derives entitlements from role mappings in `config/linewatt-access.php`, then applies per-user `entitlement_overrides`.

## User Fields

- `role`
- `plan_code`
- `subscription_status`
- `entitlement_overrides`

## Paddle Integration Point

Later Paddle work should update subscription state, not controller logic.

- Paddle webhook updates `subscription_status`.
- Paddle product/price maps to `plan_code`.
- Active subscription maps to paid entitlements.
- Expired, canceled or paused subscription removes paid entitlements.
- Manual `entitlement_overrides` remain available for support and contracts.
- Central staff roles are not controlled by Paddle.
- Partner access may be contract/manual, not public subscription.

## V1 Plan Scope

The v1 commercial plan set is intentionally small:

- `subscriber`: subscriber access to LineWatt Library, private compilation, exports and comparison according to entitlements.
- `manufacturer_pro`: self-serve manufacturer plan for Manufacturer Admin access, datasheet management, review submissions, supporting documents, basic insights placeholders and promotion placeholders where enabled.
- `manufacturer_enterprise`: Pro capabilities plus Enterprise placeholders such as website integration, datasheet designer, advanced content distribution, APIs and multilingual datasheet workflows.

No lower-tier manufacturer plan is part of v1; onboarding, badges, plan comparison, entitlement mapping and seeded demo data should only use Manufacturer Pro or Manufacturer Enterprise.

Paddle v1 should use a Subscriber price and a Manufacturer Pro price. Manufacturer Enterprise may remain a contact-sales/request flow unless a Paddle price is explicitly configured.

## Controller Rule

Controllers and routes should ask `EntitlementChecker` or entitlement middleware. They should not hardcode paid access.
