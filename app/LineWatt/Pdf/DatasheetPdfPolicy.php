<?php

namespace App\LineWatt\Pdf;

use App\LineWatt\Access\Entitlement;
use App\LineWatt\Access\EntitlementChecker;
use App\Models\DeviceDatasheet;
use App\Models\User;

class DatasheetPdfPolicy
{
    public const INTERNAL_ONLY = 'internal_only';
    public const EXTERNAL_LINK_ONLY = 'external_link_only';
    public const HOSTED_WITH_PERMISSION = 'hosted_with_permission';
    public const PARTNER_SUPPLIED = 'partner_supplied';
    public const USER_PRIVATE = 'user_private';

    public function __construct(private readonly EntitlementChecker $entitlements) {}

    public function canInternalPreview(?User $user, DeviceDatasheet $datasheet): bool
    {
        if (! $user) {
            return false;
        }

        if ($this->entitlements->has($user, Entitlement::CENTRAL_MANAGE)) {
            return (bool) ($datasheet->can_internal_preview ?? true);
        }

        if (
            $datasheet->source_type === 'central_curated'
            && $this->entitlements->has($user, Entitlement::LIBRARY_PUBLISHER_WORKFLOW)
        ) {
            return (bool) ($datasheet->can_internal_preview ?? true);
        }

        if ($datasheet->source_type === 'tenant_private') {
            return (int) $datasheet->tenant_id === (int) $user->id;
        }

        if ($datasheet->source_type === 'partner_submitted') {
            return (int) $datasheet->partner_id === (int) $user->id
                || (int) ($datasheet->metadata['manufacturer_company_id'] ?? 0) === (int) ($user->manufacturer_company_id ?? 0);
        }

        return false;
    }

    public function canPublicPreview(DeviceDatasheet $datasheet): bool
    {
        return (bool) $datasheet->can_public_preview
            && in_array($this->mode($datasheet), [self::HOSTED_WITH_PERMISSION, self::PARTNER_SUPPLIED], true);
    }

    public function canPublicDownload(DeviceDatasheet $datasheet): bool
    {
        return (bool) $datasheet->can_public_download
            && in_array($this->mode($datasheet), [self::HOSTED_WITH_PERMISSION, self::PARTNER_SUPPLIED], true);
    }

    public function canPrivateDownload(?User $user, DeviceDatasheet $datasheet): bool
    {
        if (! $user || ! $datasheet->can_private_download) {
            return false;
        }

        if ($this->entitlements->has($user, Entitlement::CENTRAL_MANAGE)) {
            return true;
        }

        if ($datasheet->source_type === 'tenant_private') {
            return (int) $datasheet->tenant_id === (int) $user->id;
        }

        if ($datasheet->source_type === 'partner_submitted') {
            return (int) $datasheet->partner_id === (int) $user->id
                || (int) ($datasheet->metadata['manufacturer_company_id'] ?? 0) === (int) ($user->manufacturer_company_id ?? 0);
        }

        return $this->canPublicDownload($datasheet);
    }

    /**
     * @return array<string,mixed>
     */
    public function viewPayload(?User $user, DeviceDatasheet $datasheet, ?string $internalPreviewUrl = null, ?string $publicPreviewUrl = null): array
    {
        $mode = $this->mode($datasheet);
        $canInternalPreview = $this->canInternalPreview($user, $datasheet);
        $canPublicPreview = $this->canPublicPreview($datasheet);

        return [
            'access_mode' => $mode,
            'source_url' => $datasheet->source_url,
            'source_domain' => $datasheet->source_domain,
            'permission_status' => $datasheet->permission_status ?: 'unknown',
            'permission_notes' => $datasheet->permission_notes,
            'attribution_text' => $datasheet->attribution_text,
            'can_public_download' => $this->canPublicDownload($datasheet),
            'can_public_preview' => $canPublicPreview,
            'can_internal_preview' => $canInternalPreview,
            'can_private_download' => $this->canPrivateDownload($user, $datasheet),
            'can_embed' => $canInternalPreview || $canPublicPreview,
            'preview_url' => $canInternalPreview ? $internalPreviewUrl : ($canPublicPreview ? $publicPreviewUrl : null),
            'source_label' => $this->sourceLabel($datasheet),
            'pdf_label' => $this->pdfLabel($datasheet),
            'external_only' => $mode === self::EXTERNAL_LINK_ONLY || (! $canInternalPreview && ! $canPublicPreview),
        ];
    }

    public function sourceLabel(DeviceDatasheet $datasheet): string
    {
        return match ($this->mode($datasheet)) {
            self::PARTNER_SUPPLIED => 'Partner Supplied',
            self::USER_PRIVATE => 'User Private',
            self::HOSTED_WITH_PERMISSION => 'Hosted with Permission',
            default => 'Manufacturer Website',
        };
    }

    public function pdfLabel(DeviceDatasheet $datasheet): string
    {
        return match ($this->mode($datasheet)) {
            self::HOSTED_WITH_PERMISSION => 'Hosted with Permission',
            self::PARTNER_SUPPLIED => 'Partner Supplied',
            self::USER_PRIVATE => 'User Private',
            self::EXTERNAL_LINK_ONLY => 'External Link Only',
            default => 'Internal Only',
        };
    }

    public function mode(DeviceDatasheet $datasheet): string
    {
        $mode = $datasheet->pdf_access_mode;

        if (in_array($mode, [
            self::INTERNAL_ONLY,
            self::EXTERNAL_LINK_ONLY,
            self::HOSTED_WITH_PERMISSION,
            self::PARTNER_SUPPLIED,
            self::USER_PRIVATE,
        ], true)) {
            return $mode;
        }

        return match ($datasheet->source_type) {
            'tenant_private' => self::USER_PRIVATE,
            'partner_submitted' => self::PARTNER_SUPPLIED,
            default => $datasheet->source_url ? self::EXTERNAL_LINK_ONLY : self::INTERNAL_ONLY,
        };
    }
}
