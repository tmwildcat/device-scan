<?php

use App\LineWatt\Access\Entitlement;
use App\LineWatt\Access\LineWattRole;

return [
    'roles' => [
        LineWattRole::SUPER_ADMIN,
        LineWattRole::ADMIN,
        LineWattRole::LIBRARIAN,
        LineWattRole::LIBRARY_PUBLISHER,
        LineWattRole::LIBRARY_CHAMPION,
        LineWattRole::LEGAL_COUNSEL,
        LineWattRole::SUBSCRIBER,
        LineWattRole::GUEST,
        LineWattRole::PARTNER_ADMIN,
        LineWattRole::PARTNER_USER,
    ],

    'subscription_statuses' => [
        'active',
        'trialing',
        'past_due',
        'paused',
        'canceled',
        'expired',
        'contract_active',
    ],

    'role_entitlements' => [
        LineWattRole::GUEST => [
            Entitlement::LIBRARY_SEARCH,
            Entitlement::LIBRARY_VIEW_RECORD,
        ],
        LineWattRole::SUBSCRIBER => [
            Entitlement::LIBRARY_SEARCH,
            Entitlement::LIBRARY_VIEW_RECORD,
            Entitlement::LIBRARY_DOWNLOAD,
            Entitlement::LIBRARY_EXPORT,
            Entitlement::LIBRARY_COMPARE,
            Entitlement::LIBRARY_PRIVATE_UPLOAD,
            Entitlement::LIBRARY_PRIVATE_COMPILE,
            Entitlement::LIBRARY_STORAGE_QUOTA,
        ],
        LineWattRole::LIBRARIAN => [
            Entitlement::LIBRARY_SEARCH,
            Entitlement::LIBRARY_VIEW_RECORD,
            Entitlement::LIBRARY_DOWNLOAD,
            Entitlement::LIBRARY_EXPORT,
            Entitlement::LIBRARY_COMPARE,
            Entitlement::CENTRAL_MANAGE,
        ],
        LineWattRole::LIBRARY_PUBLISHER => [
            Entitlement::LIBRARY_SEARCH,
            Entitlement::LIBRARY_VIEW_RECORD,
            Entitlement::LIBRARY_DOWNLOAD,
            Entitlement::LIBRARY_EXPORT,
            Entitlement::LIBRARY_COMPARE,
            Entitlement::LIBRARY_PUBLISHER_WORKFLOW,
        ],
        LineWattRole::LIBRARY_CHAMPION => [
            Entitlement::LIBRARY_SEARCH,
            Entitlement::LIBRARY_VIEW_RECORD,
        ],
        LineWattRole::LEGAL_COUNSEL => [
            Entitlement::LIBRARY_SEARCH,
            Entitlement::LIBRARY_VIEW_RECORD,
        ],
        LineWattRole::ADMIN => [
            Entitlement::LIBRARY_SEARCH,
            Entitlement::LIBRARY_VIEW_RECORD,
            Entitlement::LIBRARY_DOWNLOAD,
            Entitlement::LIBRARY_EXPORT,
            Entitlement::LIBRARY_COMPARE,
            Entitlement::CENTRAL_MANAGE,
            Entitlement::PARTNER_MANAGE_PRODUCTS,
            Entitlement::PARTNER_MANAGE_PROMOTIONS,
        ],
        LineWattRole::SUPER_ADMIN => [
            Entitlement::LIBRARY_SEARCH,
            Entitlement::LIBRARY_VIEW_RECORD,
            Entitlement::LIBRARY_DOWNLOAD,
            Entitlement::LIBRARY_EXPORT,
            Entitlement::LIBRARY_COMPARE,
            Entitlement::LIBRARY_PRIVATE_UPLOAD,
            Entitlement::LIBRARY_PRIVATE_COMPILE,
            Entitlement::LIBRARY_STORAGE_QUOTA,
            Entitlement::CENTRAL_MANAGE,
            Entitlement::PARTNER_MANAGE_PRODUCTS,
            Entitlement::PARTNER_MANAGE_PROMOTIONS,
        ],
        LineWattRole::PARTNER_ADMIN => [
            Entitlement::LIBRARY_SEARCH,
            Entitlement::LIBRARY_VIEW_RECORD,
            Entitlement::LIBRARY_DOWNLOAD,
            Entitlement::LIBRARY_EXPORT,
            Entitlement::LIBRARY_COMPARE,
            Entitlement::PARTNER_MANAGE_PRODUCTS,
            Entitlement::PARTNER_MANAGE_PROMOTIONS,
        ],
        LineWattRole::PARTNER_USER => [
            Entitlement::LIBRARY_SEARCH,
            Entitlement::LIBRARY_VIEW_RECORD,
            Entitlement::LIBRARY_DOWNLOAD,
            Entitlement::LIBRARY_EXPORT,
            Entitlement::LIBRARY_COMPARE,
            Entitlement::PARTNER_MANAGE_PRODUCTS,
        ],
    ],

    'plan_entitlements' => [
        'subscriber' => [
            Entitlement::LIBRARY_SEARCH,
            Entitlement::LIBRARY_VIEW_RECORD,
            Entitlement::LIBRARY_DOWNLOAD,
            Entitlement::LIBRARY_EXPORT,
            Entitlement::LIBRARY_COMPARE,
            Entitlement::LIBRARY_PRIVATE_UPLOAD,
            Entitlement::LIBRARY_PRIVATE_COMPILE,
            Entitlement::LIBRARY_STORAGE_QUOTA,
        ],
        'demo_member' => [
            Entitlement::LIBRARY_SEARCH,
            Entitlement::LIBRARY_VIEW_RECORD,
            Entitlement::LIBRARY_DOWNLOAD,
            Entitlement::LIBRARY_EXPORT,
            Entitlement::LIBRARY_COMPARE,
            Entitlement::LIBRARY_PRIVATE_UPLOAD,
            Entitlement::LIBRARY_PRIVATE_COMPILE,
            Entitlement::LIBRARY_STORAGE_QUOTA,
        ],
        'manufacturer_pro' => [
            Entitlement::PARTNER_MANAGE_PRODUCTS,
            Entitlement::PARTNER_MANAGE_PROMOTIONS,
        ],
        'manufacturer_enterprise' => [
            Entitlement::PARTNER_MANAGE_PRODUCTS,
            Entitlement::PARTNER_MANAGE_PROMOTIONS,
            Entitlement::PARTNER_ENTERPRISE_FEATURES,
        ],
    ],
];
