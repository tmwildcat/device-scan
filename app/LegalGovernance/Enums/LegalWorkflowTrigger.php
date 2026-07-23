<?php

namespace App\LegalGovernance\Enums;

enum LegalWorkflowTrigger: string
{
    case Registration = 'registration';
    case FirstLogin = 'first_login';
    case SubscriptionCheckout = 'subscription_checkout';
    case SubscriptionUpgrade = 'subscription_upgrade';
    case ManufacturerInvitation = 'manufacturer_invitation';
    case ManufacturerActivation = 'manufacturer_activation';
    case PublisherSubmission = 'publisher_submission';
    case EmployeeOnboarding = 'employee_onboarding';
    case EnterpriseOnboarding = 'enterprise_onboarding';
    case OrderExecution = 'order_execution';
    case ApiCredentialIssuance = 'api_credential_issuance';
    case McpAccessGrant = 'mcp_access_grant';
    case MaterialDocumentChange = 'material_document_change';
    case ManualAssignment = 'manual_assignment';
}
