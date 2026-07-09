<script setup lang="ts">
import AdminShell from './AdminShell.vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps<{
    title: string;
    subtitle?: string | null;
    breadcrumbs?: Array<{ label: string; href?: string | null }>;
    primaryAction?: { label: string; href: string } | null;
    secondaryActions?: Array<{ label: string; href: string }>;
    roleLabel?: string | null;
}>();

const page = usePage();
const user = computed(() => (page.props as any).auth?.user ?? null);
const isPublisher = computed(() => user.value?.role === 'library_publisher');
const isBusinessAdmin = computed(() => user.value?.role === 'admin');
const homeHref = computed(() => {
    if (isPublisher.value) return '/publisher';
    if (isBusinessAdmin.value) return '/admin/business';

    return '/admin/library';
});
const workspaceTitle = computed(() => {
    if (isPublisher.value) return 'Library Publisher';
    if (isBusinessAdmin.value) return 'Business Administration';

    return 'Library Administration';
});
const contextName = computed(() => {
    if (isPublisher.value) return 'Publishing Review Workspace';
    if (isBusinessAdmin.value) return 'Library Business Operations';

    return 'Central Library Operations';
});

const libraryNavItems = [
    { label: 'Dashboard', href: '/admin/library' },
    {
        label: 'Library Data Management',
        href: '/admin/library/approval-queue?view=pending_approval',
        children: [
            { label: 'Review & Approval', href: '/admin/library/approval-queue?view=pending_approval' },
            { label: 'Datasheets', href: '/admin/library/datasheets?view=new_uploads' },
            { label: 'Engineering Data', href: '/admin/library/engineering-data?view=pending_approval' },
        ],
    },
    {
        label: 'Equipment Manufacturers',
        href: '/admin/library/manufacturers',
        children: [
            { label: 'All Manufacturers', href: '/admin/library/manufacturers' },
            { label: 'Subscribers', href: '/admin/library/oem-subscribers' },
            { label: 'Manufacturer Requests', href: '/admin/library/partner-requests' },
        ],
    },
    {
        label: 'Members',
        href: '/admin/library/members',
        children: [
            { label: 'Subscribers', href: '/admin/library/members?view=subscribers' },
            { label: 'Registered Users', href: '/admin/library/members?view=registered' },
            { label: 'Support', href: '/admin/library/members?view=support_access' },
        ],
    },
    {
        label: 'Library Management',
        href: '/admin/library/publishers',
        children: [
            { label: 'Publishers', href: '/admin/library/publishers' },
            { label: 'Power Search Taxonomy', href: '/admin/library/power-search' },
            { label: 'Manufacturer Normalization', href: '/admin/library/governance/manufacturer-normalization' },
            { label: 'Governance', href: '/admin/library/governance/objectionable-content' },
        ],
    },
    { label: 'Settings', href: '/settings/profile' },
];

const businessNavItems = [
    { label: 'Dashboard', href: '/admin/business' },
    {
        label: 'Library',
        href: '/admin/library/approval-queue?view=pending_approval',
        children: [
            { label: 'Review & Approval', href: '/admin/library/approval-queue?view=pending_approval' },
            { label: 'Datasheets', href: '/admin/library/datasheets?view=new_uploads' },
            { label: 'Engineering Data', href: '/admin/library/engineering-data?view=pending_approval' },
        ],
    },
    {
        label: 'Manufacturers',
        href: '/admin/library/manufacturers',
        children: [
            { label: 'All Manufacturers', href: '/admin/library/manufacturers' },
            { label: 'Subscribers', href: '/admin/library/oem-subscribers' },
            { label: 'Manufacturer Requests', href: '/admin/library/partner-requests' },
        ],
    },
    {
        label: 'Members',
        href: '/admin/library/members',
        children: [
            { label: 'Subscribers', href: '/admin/library/members?view=subscribers' },
            { label: 'Registered Users', href: '/admin/library/members?view=registered' },
        ],
    },
    {
        label: 'Growth',
        href: '/admin/business/discovery',
        children: [
            { label: 'SEO Dashboard', href: '/admin/business/discovery' },
            { label: 'Canonical URLs', href: '/admin/business/discovery/canonical-urls' },
            { label: 'Landing Pages', href: '/admin/business/discovery/landing-pages' },
            { label: 'Redirect Manager', href: '/admin/business/discovery/redirects' },
            { label: 'Sitemap Manager', href: '/admin/business/discovery/sitemaps' },
            { label: 'Structured Data', href: '/admin/business/discovery/structured-data' },
            { label: 'AI Discoverability', href: '/admin/business/discovery/ai-discoverability' },
            { label: 'Compiler', href: '/admin/business/compiler' },
            { label: 'Promotions', href: '/admin/library/promotions' },
            { label: 'Champions', href: '/admin/library/champions' },
        ],
    },
    {
        label: 'Library Management',
        href: '/admin/library/publishers',
        children: [
            { label: 'Publishers', href: '/admin/library/publishers' },
            { label: 'Power Search', href: '/admin/library/power-search' },
            { label: 'Manufacturer Normalization', href: '/admin/library/governance/manufacturer-normalization' },
            { label: 'Governance', href: '/admin/library/governance/objectionable-content' },
        ],
    },
    { label: 'Settings', href: '/settings/profile' },
];

const publisherNavItems = [
    { label: 'Dashboard', href: '/publisher' },
    {
        label: 'Library Data Management',
        href: '/publisher/uploads?view=pending_review&device_type=module',
        children: [
            { label: 'Datasheets', href: '/publisher/uploads?view=pending_review&device_type=module' },
            { label: 'Engineering Data', href: '/publisher/review?view=pending_review&device_type=module' },
        ],
    },
    {
        label: 'Equipment Manufacturers',
        href: '/manufacturers',
        children: [
            { label: 'All Manufacturers', href: '/manufacturers' },
            { label: 'Subscribers', href: '/publisher/oem-subscribers' },
        ],
    },
];

const navItems = computed(() => {
    if (isPublisher.value) return publisherNavItems;
    if (isBusinessAdmin.value) return businessNavItems;

    return libraryNavItems;
});
</script>

<template>
    <AdminShell
        :workspace-title="workspaceTitle"
        :context-name="contextName"
        :home-href="homeHref"
        :hide-workspace-identity="!isPublisher"
        :workspace-button-label="isPublisher ? null : workspaceTitle"
        :search-href="isPublisher ? null : '/search'"
        :role-label="roleLabel"
        :nav-items="navItems"
        :breadcrumbs="breadcrumbs"
        :title="title"
        :subtitle="subtitle"
        :primary-action="primaryAction"
        :secondary-actions="secondaryActions"
        :settings-href="isPublisher ? null : '/settings/profile'"
    >
        <slot />
    </AdminShell>
</template>
