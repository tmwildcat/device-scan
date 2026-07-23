<?php

namespace App\LegalGovernance\Enums;

enum LegalAcceptanceType: string
{
    case Clickwrap = 'clickwrap_acceptance';
    case Acknowledgement = 'acknowledgement';
    case ElectronicSignature = 'electronic_signature';
    case OrganisationExecution = 'organisation_execution';
    case Consent = 'consent';
    case OptionalConsent = 'optional_consent';
    case Decline = 'decline';
}
