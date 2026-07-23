# Extraction Guide

## Copy the portable layer

Extract `app/LegalGovernance`, the legal migrations, `config/legal-governance.php`, provider registration, and core tests. Replace adapters for identity, organisation, audience, authentication context, notifications, audit, storage, routes, clock, and PDF.

## Application-specific pieces

Replace `LineWattLegalIdentityResolver`, `LineWattLegalPdfRenderer`, `EnsureLegalPermission`, the role constant/User helper, Fortify registration integration, Blade/Inertia routes, and development seeder. The core has no Manufacturer, Paddle, MCP, tenant, or admin-framework dependency.

## Installation sequence

Register the provider, publish/merge configuration, migrate, implement resolver and policy adapters, import a reviewed manifest as Drafts, configure role permissions, add portal/admin routes, activate only validated workflows, and run portability plus database tests.

Do not copy LineWatt legal text into another application without legal review and an application-specific manifest.
