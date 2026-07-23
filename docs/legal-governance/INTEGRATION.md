# LineWatt Integration

Registration dynamically resolves a registration workflow, presents exact official titles and versions, separates acknowledgement from agreement, and records evidence in the Account-creation transaction. With no active workflow, existing registration remains available.

Development workflow configurations cover Subscriber checkout, Manufacturer onboarding, employee acknowledgement, and API/MCP credential gating. These remain Draft/inactive until approved versions and the relevant product activation points are enabled.

`LineWattLegalIdentityResolver` maps Users and Manufacturer organisation IDs. Subscriber status remains governed by authoritative billing fields. Internal app and MCP credentials must use the workflow/obligation gate before production activation; Paddle is not treated as the sole evidence system.

Enterprise execution supports neutral organisation and subject references and externally executed artefact metadata through the schema; an external signature provider is not integrated.

Operational Counsel routes are named under `legal-governance.reviews.*`, `legal-governance.publications.*`, `legal-governance.workflows.*`, `legal-governance.evidence-exports.*`, `legal-governance.placeholders.*`, and `legal-governance.settings`. Subscriber, Manufacturer, employee, Enterprise signature, and API/MCP mutation enforcement remain separate integration work.
