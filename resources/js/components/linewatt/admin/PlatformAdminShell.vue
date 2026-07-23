<script setup lang="ts">
import AdminShell from './AdminShell.vue';

const props = defineProps<{
    title: string;
    subtitle?: string | null;
    breadcrumbs?: Array<{ label: string; href?: string | null }>;
    primaryAction?: { label: string; href: string } | null;
    roleLabel?: string | null;
    environment?: string | null;
    health?: string | null;
    legalGovernanceHref?: string;
}>();

const navItems = [
    {
        label: 'Platform',
        href: '/admin/platform',
        children: [
            { label: 'Dashboard', href: '/admin/platform' },
            { label: 'Legal Governance', href: props.legalGovernanceHref ?? '/admin/legal-governance' },
            { label: 'System Health', href: '/admin/platform/system-health' },
            { label: 'Security', href: '/admin/platform/security' },
            { label: 'Storage', href: '/admin/platform/storage' },
            { label: 'Background Jobs', href: '/admin/platform/background-jobs' },
            { label: 'Queue Monitor', href: '/admin/platform/queue-monitor' },
            { label: 'Notifications', href: '/admin/platform/notifications' },
            { label: 'Logs', href: '/admin/platform/logs' },
            { label: 'Backup & Recovery', href: '/admin/platform/backup-recovery' },
            { label: 'Environment', href: '/admin/platform/environment' },
            { label: 'Feature Flags', href: '/admin/platform/feature-flags' },
            { label: 'Audit Logs', href: '/admin/platform/audit-logs' },
            { label: 'Developer Tools', href: '/admin/platform/developer-tools' },
        ],
    },
    {
        label: 'Discovery',
        href: '/admin/platform/discovery',
        children: [
            { label: 'Dashboard', href: '/admin/platform/discovery' },
            { label: 'Landing Pages', href: '/admin/platform/discovery/landing-pages' },
            { label: 'Metadata', href: '/admin/platform/discovery/metadata' },
            { label: 'Canonical URLs', href: '/admin/platform/discovery/canonical-urls' },
            { label: 'Redirects', href: '/admin/platform/discovery/redirects' },
            { label: 'Structured Data', href: '/admin/platform/discovery/structured-data' },
            { label: 'Sitemaps', href: '/admin/platform/discovery/sitemaps' },
            { label: 'Robots', href: '/admin/platform/discovery/robots' },
            { label: 'Search Console', href: '/admin/platform/discovery/search-console' },
            { label: 'AI Discoverability', href: '/admin/platform/discovery/ai' },
        ],
    },
    {
        label: 'Users & Security',
        href: '/admin/platform/system-administrators',
        children: [
            { label: 'System Administrators', href: '/admin/platform/system-administrators' },
            { label: 'Roles', href: '/admin/platform/roles' },
            { label: 'Permissions', href: '/admin/platform/permissions' },
            { label: 'Entitlements', href: '/admin/platform/entitlements' },
            { label: 'Authentication', href: '/admin/platform/authentication' },
            { label: 'SSO', href: '/admin/platform/sso' },
        ],
    },
    {
        label: 'Infrastructure',
        href: '/admin/platform/object-storage',
        children: [
            { label: 'Object Storage', href: '/admin/platform/object-storage' },
            { label: 'Services', href: '/admin/platform/services' },
            { label: 'Internal App Access', href: '/admin/platform/internal-app-access' },
            { label: 'Compiler Services', href: '/admin/platform/compiler-services' },
            { label: 'Search Index', href: '/admin/platform/search-index' },
            { label: 'Email', href: '/admin/platform/email' },
            { label: 'Scheduled Jobs', href: '/admin/platform/scheduled-jobs' },
            { label: 'Monitoring', href: '/admin/platform/monitoring' },
        ],
    },
];
</script>

<template>
    <AdminShell
        workspace-title="Platform Administration"
        context-name="System Operations"
        home-href="/admin/platform"
        workspace-button-label="Platform Administration"
        search-href="/search"
        :role-label="roleLabel"
        :nav-items="navItems"
        :breadcrumbs="breadcrumbs"
        :title="title"
        :subtitle="subtitle"
        :primary-action="primaryAction"
        settings-href="/settings/profile"
        :header-badges="[
            { label: environment || 'environment', tone: 'sky' },
            { label: health || 'Health', tone: health === 'Healthy' ? 'emerald' : 'amber' },
        ]"
    >
        <slot />
    </AdminShell>
</template>
