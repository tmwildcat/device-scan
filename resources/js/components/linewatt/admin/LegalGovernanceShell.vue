<script setup lang="ts">
import AdminShell from './AdminShell.vue';

defineProps<{
    title: string;
    subtitle?: string | null;
    roleLabel?: string | null;
    isSuperAdmin?: boolean;
}>();

const navItems = [
    {
        label: 'Legal Governance',
        href: '/admin/legal-governance',
        children: [
            { label: 'Dashboard', href: '/admin/legal-governance' },
            { label: 'Documents', href: '/admin/legal-governance/documents' },
            { label: 'Review Queue', href: '/admin/legal-governance/reviews' },
            { label: 'Publication Schedule', href: '/admin/legal-governance/publications' },
            { label: 'Workflows', href: '/admin/legal-governance/workflows' },
            { label: 'Obligations', href: '/admin/legal-governance/obligations' },
            { label: 'Acceptances', href: '/admin/legal-governance/acceptances' },
            { label: 'Manifests', href: '/admin/legal-governance/manifests' },
            { label: 'Evidence Exports', href: '/admin/legal-governance/evidence-exports' },
            { label: 'Placeholders', href: '/admin/legal-governance/placeholders' },
            { label: 'Audit Log', href: '/admin/legal-governance/audit' },
            { label: 'Settings', href: '/admin/legal-governance/settings' },
        ],
    },
];
</script>

<template>
    <AdminShell
        workspace-title="Legal Governance"
        context-name="Counsel Operations"
        home-href="/admin/legal-governance"
        workspace-button-label="Legal Governance"
        search-href="/admin/legal-governance/documents"
        :role-label="roleLabel"
        :nav-items="navItems"
        :breadcrumbs="[{ label: 'Legal Governance', href: '/admin/legal-governance' }, { label: title }]"
        :title="title"
        :subtitle="subtitle"
        settings-href="/settings/profile"
        :primary-action="isSuperAdmin ? { label: 'Platform Administration', href: '/admin/platform' } : null"
        :header-badges="[{ label: 'Governed workspace', tone: 'emerald' }]"
    >
        <slot />
    </AdminShell>
</template>
