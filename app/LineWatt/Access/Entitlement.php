<?php

namespace App\LineWatt\Access;

final class Entitlement
{
    public const LIBRARY_SEARCH = 'library.search';
    public const LIBRARY_VIEW_RECORD = 'library.view_record';
    public const LIBRARY_DOWNLOAD = 'library.download';
    public const LIBRARY_EXPORT = 'library.export';
    public const LIBRARY_COMPARE = 'library.compare';
    public const LIBRARY_PRIVATE_UPLOAD = 'library.private_upload';
    public const LIBRARY_PRIVATE_COMPILE = 'library.private_compile';
    public const LIBRARY_STORAGE_QUOTA = 'library.storage_quota';
    public const LIBRARY_PUBLISHER_WORKFLOW = 'library.publisher_workflow';
    public const CENTRAL_MANAGE = 'central.manage';
    public const PARTNER_MANAGE_PRODUCTS = 'partner.manage_products';
    public const PARTNER_MANAGE_PROMOTIONS = 'partner.manage_promotions';
    public const PARTNER_ENTERPRISE_FEATURES = 'partner.enterprise_features';
}
