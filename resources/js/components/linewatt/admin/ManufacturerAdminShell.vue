<script setup lang="ts">
import AdminShell from './AdminShell.vue';
import { computed } from 'vue';
import { useLineWattI18n } from '@/lib/linewatt-i18n';

const props = defineProps<{
    company: {
        name: string;
        plan_code?: string | null;
        plan_label?: string | null;
        manufacturer_role_label?: string | null;
        can_upgrade?: boolean;
        upgrade_message?: string | null;
    };
    title: string;
    subtitle?: string | null;
    breadcrumbs?: Array<{ label: string; href?: string | null }>;
    primaryAction?: { label: string; href: string } | null;
}>();

const { t } = useLineWattI18n();

const navItems = computed(() => [
    { label: t('manufacturer.dashboard'), href: '/admin/manufacturer' },
    { label: t('manufacturer.datasheets'), href: '/admin/manufacturer/datasheets' },
    { label: t('manufacturer.reviewQueue'), href: '/admin/manufacturer/structured-engineering-data?status=review_required' },
    {
        label: t('manufacturer.supportingDocuments'),
        href: '/admin/manufacturer/supporting-documents',
        children: [
            { label: t('manufacturer.companyWide'), href: '/admin/manufacturer/supporting-documents?scope=company' },
            { label: t('manufacturer.datasheetSpecific'), href: '/admin/manufacturer/supporting-documents?scope=datasheet' },
        ],
    },
    { label: t('manufacturer.promotions'), href: '/admin/manufacturer/datasheets?tab=Promotions' },
    { label: t('manufacturer.insights'), href: '/admin/manufacturer/datasheets?tab=Analytics' },
    { label: t('manufacturer.websiteIntegration'), href: '/admin/manufacturer/website-integration' },
    {
        label: t('manufacturer.company'),
        href: '/admin/manufacturer/company/profile',
        children: [
            { label: t('manufacturer.companyProfile'), href: '/admin/manufacturer/company/profile' },
            { label: t('manufacturer.supportingDocuments'), href: '/admin/manufacturer/supporting-documents?scope=company' },
            { label: t('manufacturer.factoryLocations'), href: '/admin/manufacturer/company/factories' },
            { label: t('manufacturer.distributionCountries'), href: '/admin/manufacturer/company/distribution-countries' },
            { label: t('manufacturer.countryContacts'), href: '/admin/manufacturer/country-contacts' },
        ],
    },
    { label: t('manufacturer.users'), href: '/admin/manufacturer/users' },
    { label: t('manufacturer.subscription'), href: '/admin/manufacturer/upgrade' },
]);

function includedEntitlements(): string[] {
    if (props.company.plan_code === 'enterprise') {
        return ['Promotions', 'Insights', 'Competitor comparison', 'Datasheet Designer', 'Website Integration', 'Digital Product Passport'];
    }

    if (props.company.plan_code === 'pro') {
        return ['Promotions', 'Insights', 'Competitor comparison'];
    }

    return ['Smart links', 'Datasheet administration'];
}
</script>

<template>
    <AdminShell
        :workspace-title="t('manufacturer.workspaceTitle')"
        home-href="/admin/manufacturer"
        :context-name="company.name"
        :role-label="company.manufacturer_role_label"
        :plan-label="company.plan_label"
        :plan-code="company.plan_code"
        :nav-items="navItems"
        :breadcrumbs="breadcrumbs"
        :title="title"
        :subtitle="subtitle"
        :primary-action="primaryAction"
        :upgrade-href="company.can_upgrade ? '/admin/manufacturer/upgrade' : null"
        :upgrade-message="company.upgrade_message"
        :included-entitlements="includedEntitlements()"
        :enterprise-entitlements="company.plan_code === 'enterprise' ? [] : ['Datasheet Designer', 'Website Integration', 'Digital Product Passport']"
    >
        <slot />
    </AdminShell>
</template>
