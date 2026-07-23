# Legal Workflows

## Model

Workflows are constrained declarative records keyed by application, trigger, audience, priority, effective window, and blocking mode. Requirements select an exact version using current-published, current-effective, specific-version, order-attached, or latest-material semantics.

Supported triggers include registration, login, Subscriber checkout/upgrade, Manufacturer invitation/activation, Publisher submission, employee and Enterprise onboarding, Order execution, API credential issuance, MCP access, Material Change, and manual assignment.

## Legal actions

Clickwrap, acknowledgement, electronic signature, organisation execution, consent, optional consent, and decline remain distinct. Validation rejects required optional consent, notice-as-universal-consent, internal document presentation, missing audience, and missing selectable versions.

Development seed workflows remain inactive Drafts until applicable legal versions are approved and published.

The workflow pages provide list, validated declarative editing, user-facing preview, and guarded activation. Activation rejects unsupported triggers, missing audiences or requirements, unavailable Published versions, internal presentation, required optional consent, notice-as-universal-consent, and missing required acceptance statements. Preview creates no Acceptance evidence.

The requirement editor permits only allow-listed version-selection rules, acceptance types, and blocking modes. Adding, replacing, or removing a requirement returns the workflow to Draft so it must pass validation before activation; it never accepts executable expressions.
