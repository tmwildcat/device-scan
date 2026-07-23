<script setup lang="ts">
import LegalGovernanceShell from '@/components/linewatt/admin/LegalGovernanceShell.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type Paginator<T> = {
    data: T[];
    links?: Array<{ url?: string | null; label: string; active: boolean }>;
    total?: number;
};
type Item = Record<string, any>;
const props = defineProps<{
    title: string;
    kind: string;
    workspace: {
        role_label?: string | null;
        is_super_admin: boolean;
        can_review?: boolean;
        can_approve?: boolean;
        can_publish?: boolean;
        can_manage_workflows?: boolean;
    };
    items?: Paginator<Item>;
    filters?: Record<string, string | null>;
    version?: Item;
    previous_version?: Item | null;
    text_diff?: Item[];
    required_reviews?: string[];
    workflow?: Item;
    requirements?: Item[];
    documents?: Item[];
    validation_errors?: string[];
    settings?: Record<string, unknown>;
    sensitive_access?: boolean;
    csrf_token?: string;
}>();
const review = useForm({
    review_type: 'legal',
    decision: 'approved',
    comments: '',
});
const schedule = useForm({ publish_at: '', effective_at: '' });
const evidence = useForm({
    subject_type: 'user',
    subject_id: '',
    case_reference: '',
    confirmed: false,
});
const workflowForm = useForm({
    name: props.workflow?.name || '',
    description: props.workflow?.description || '',
    trigger_type: props.workflow?.trigger_type || '',
    audience: props.workflow?.audience || '',
    blocking_behavior: props.workflow?.blocking_behavior || 'notice_only',
    priority: props.workflow?.priority || 0,
});
const requirementForm = useForm({
    document_id: '',
    sequence: (props.requirements?.length || 0) + 1,
    version_selection_rule: 'current_published',
    specific_version: '',
    acceptance_type: 'clickwrap_acceptance',
    is_required: true,
    blocking_behavior: 'next_login_block',
    statement: '',
});
const list = computed(() => props.items?.data || []);
const publicationStep = ref<Record<string, number>>({});
const publicationMode = ref<Record<string, 'now' | 'schedule'>>({});
const publicationConfirmed = ref<Record<string, boolean>>({});
const pretty = (value: unknown) => String(value ?? '—').replaceAll('_', ' ');
const post = (href: string) => router.post(href, {}, { preserveScroll: true });
const cancelSchedule = (item: Item) => {
    const reason = window.prompt('Reason for cancelling this schedule:');
    if (reason)
        router.delete(`/admin/legal-governance/versions/${item.id}/schedule`, {
            data: { reason },
            preserveScroll: true,
        });
};
</script>

<template>
    <Head :title="title" />
    <LegalGovernanceShell
        :title="title"
        subtitle="Permission-controlled Legal Governance operations."
        :role-label="workspace.role_label"
        :is-super-admin="workspace.is_super_admin"
    >
        <div
            v-if="validation_errors?.length"
            class="mb-5 rounded-lg border border-amber-300 bg-amber-50 p-5 text-amber-950"
        >
            <h2 class="font-black">Activation blocked</h2>
            <ul class="mt-2 list-disc space-y-1 pl-5 text-sm">
                <li v-for="error in validation_errors" :key="error">
                    {{ error }}
                </li>
            </ul>
        </div>

        <section v-if="kind === 'reviews'" class="grid gap-4">
            <form
                method="get"
                action="/admin/legal-governance/reviews"
                class="grid gap-3 rounded-lg border bg-white p-4 sm:grid-cols-2 lg:grid-cols-4 dark:border-slate-700 dark:bg-slate-900"
            >
                <select
                    name="review_type"
                    :value="filters?.review_type || ''"
                    class="rounded-md border p-2"
                    aria-label="Review type"
                >
                    <option value="">All review types</option>
                    <option
                        v-for="type in [
                            'legal',
                            'privacy',
                            'security',
                            'product',
                            'finance',
                            'engineering',
                        ]"
                        :key="type"
                        :value="type"
                    >
                        {{ pretty(type) }}
                    </option>
                </select>
                <input
                    name="category"
                    :value="filters?.category || ''"
                    class="rounded-md border p-2"
                    placeholder="Category"
                    aria-label="Document category"
                />
                <input
                    name="reviewer"
                    :value="filters?.reviewer || ''"
                    class="rounded-md border p-2"
                    placeholder="Reviewer reference"
                    aria-label="Reviewer"
                />
                <select
                    name="status"
                    :value="filters?.status || ''"
                    class="rounded-md border p-2"
                    aria-label="Lifecycle status"
                >
                    <option value="">All statuses</option>
                    <option value="in_review">In review</option>
                    <option value="changes_requested">Changes requested</option>
                </select>
                <input
                    type="date"
                    name="submitted_from"
                    :value="filters?.submitted_from || ''"
                    class="rounded-md border p-2"
                    aria-label="Submitted from"
                />
                <input
                    type="date"
                    name="submitted_until"
                    :value="filters?.submitted_until || ''"
                    class="rounded-md border p-2"
                    aria-label="Submitted until"
                />
                <select
                    name="placeholder_state"
                    :value="filters?.placeholder_state || ''"
                    class="rounded-md border p-2"
                    aria-label="Placeholder state"
                >
                    <option value="">Any placeholder state</option>
                    <option value="blocked">Release blocked</option>
                    <option value="clear">No blockers</option>
                </select>
                <button
                    class="rounded-md bg-slate-950 px-4 py-2 font-black text-white"
                >
                    Apply filters
                </button>
            </form>
            <article
                v-for="item in list"
                :key="item.id"
                class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900"
            >
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-black">
                            {{ item.document }}
                            <span class="text-slate-500">{{
                                item.version
                            }}</span>
                        </h2>
                        <p class="mt-1 text-sm text-slate-600">
                            {{ item.category }} · {{ pretty(item.status) }} ·
                            {{ item.open_blockers }} blocking placeholders
                        </p>
                        <p class="mt-2 font-mono text-xs text-slate-500">
                            {{ item.checksum }}
                        </p>
                    </div>
                    <Link
                        :href="`/admin/legal-governance/reviews/${item.id}`"
                        class="rounded-md bg-slate-950 px-4 py-2 text-sm font-black text-white"
                        >Open review</Link
                    >
                </div>
            </article>
            <div
                v-if="!list.length"
                class="rounded-lg border bg-white p-12 text-center text-slate-500"
            >
                No versions currently require review.
            </div>
        </section>

        <section
            v-else-if="kind === 'review-detail' && version"
            class="grid gap-6 xl:grid-cols-[1.3fr_0.7fr]"
        >
            <div class="space-y-6">
                <article
                    class="rounded-lg border bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900"
                >
                    <div class="mb-4 font-mono text-xs text-slate-500">
                        Checksum: {{ version.checksum }}
                    </div>
                    <div
                        class="prose dark:prose-invert max-w-none"
                        v-html="version.sanitized_html"
                    />
                </article>
                <details
                    class="rounded-lg border bg-white p-5 dark:border-slate-700 dark:bg-slate-900"
                >
                    <summary class="cursor-pointer font-black">
                        Markdown and plain-text previews
                    </summary>
                    <pre
                        class="mt-4 overflow-auto text-xs whitespace-pre-wrap"
                        >{{ version.markdown_source }}</pre
                    >
                </details>
                <article
                    class="rounded-lg border bg-white p-5 dark:border-slate-700 dark:bg-slate-900"
                >
                    <h2 class="font-black">
                        Previous version and textual diff
                    </h2>
                    <p
                        v-if="previous_version"
                        class="mt-2 text-sm text-slate-500"
                    >
                        {{ previous_version.version }} ·
                        {{ previous_version.checksum }}
                    </p>
                    <div
                        v-if="text_diff?.length"
                        class="mt-4 space-y-1 font-mono text-xs"
                    >
                        <div
                            v-for="(line, index) in text_diff"
                            :key="index"
                            :class="
                                line.type === 'added'
                                    ? 'text-emerald-700'
                                    : 'text-rose-700'
                            "
                        >
                            {{ line.type === 'added' ? '+' : '-' }}
                            {{ line.text }}
                        </div>
                    </div>
                    <p v-else class="mt-3 text-sm text-slate-500">
                        No previous version or no line-level changes.
                    </p>
                </article>
            </div>
            <aside class="space-y-5">
                <form
                    v-if="workspace.can_review"
                    class="rounded-lg border bg-white p-5 dark:border-slate-700 dark:bg-slate-900"
                    @submit.prevent="
                        review.post(
                            `/admin/legal-governance/versions/${version.id}/reviews`,
                        )
                    "
                >
                    <h2 class="font-black">Record review decision</h2>
                    <label class="mt-4 block text-sm font-bold"
                        >Review type<select
                            v-model="review.review_type"
                            class="mt-1 w-full rounded-md border p-2"
                        >
                            <option
                                v-for="type in [
                                    'legal',
                                    'privacy',
                                    'security',
                                    'product',
                                    'finance',
                                    'engineering',
                                ]"
                                :key="type"
                            >
                                {{ type }}
                            </option>
                        </select></label
                    ><label class="mt-4 block text-sm font-bold"
                        >Decision<select
                            v-model="review.decision"
                            class="mt-1 w-full rounded-md border p-2"
                        >
                            <option value="approved">Approve review</option>
                            <option value="changes_requested">
                                Request changes
                            </option>
                            <option value="rejected">Reject</option>
                        </select></label
                    ><label class="mt-4 block text-sm font-bold"
                        >Comment<textarea
                            v-model="review.comments"
                            rows="5"
                            class="mt-1 w-full rounded-md border p-2"
                        /></label
                    ><button
                        class="mt-4 rounded-md bg-slate-950 px-4 py-2 text-sm font-black text-white"
                    >
                        Record checksum-bound decision
                    </button>
                </form>
                <button
                    v-if="
                        version.status === 'in_review' && workspace.can_approve
                    "
                    class="w-full rounded-md bg-emerald-700 px-4 py-2 text-sm font-black text-white"
                    @click="
                        post(
                            `/admin/legal-governance/versions/${version.id}/approve`,
                        )
                    "
                >
                    Approve lifecycle after required reviews
                </button>
                <article
                    class="rounded-lg border bg-white p-5 dark:border-slate-700 dark:bg-slate-900"
                >
                    <h2 class="font-black">Review history</h2>
                    <p class="mt-2 text-xs text-slate-500">
                        Required matrix: {{ required_reviews?.join(', ') }}
                    </p>
                    <div
                        v-for="entry in version.reviews"
                        :key="entry.reviewed_at"
                        class="mt-3 border-t pt-3 text-sm"
                    >
                        <b>{{ entry.type }} · {{ pretty(entry.decision) }}</b>
                        <p>{{ entry.comments || 'No comment.' }}</p>
                        <code class="text-xs">{{ entry.checksum }}</code>
                    </div>
                </article>
            </aside>
        </section>

        <section v-else-if="kind === 'publications'" class="grid gap-5">
            <article
                v-for="item in list"
                :key="item.id"
                class="rounded-lg border bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900"
            >
                <h2 class="text-lg font-black">
                    {{ item.document }} {{ item.version }}
                </h2>
                <p class="mt-1 text-sm text-slate-500">
                    Publication wizard · Step
                    {{ publicationStep[item.id] || 1 }} of 4
                </p>
                <div
                    v-if="(publicationStep[item.id] || 1) === 1"
                    class="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-4"
                >
                    <div
                        v-for="(state, check) in item.readiness"
                        :key="check"
                        class="rounded-md border p-3 text-sm"
                    >
                        <div class="text-slate-500 capitalize">
                            {{ pretty(check) }}
                        </div>
                        <b class="capitalize">{{ pretty(state) }}</b>
                    </div>
                </div>
                <div
                    v-if="(publicationStep[item.id] || 1) === 2"
                    class="mt-4 rounded-md border p-4 text-sm"
                >
                    <h3 class="font-black">Change and impact review</h3>
                    <p class="mt-2">
                        {{
                            item.change_summary || 'No change summary recorded.'
                        }}
                    </p>
                    <p>
                        Material change:
                        {{ item.material_change ? 'Yes' : 'No' }}
                    </p>
                    <p>
                        Affected workflows:
                        {{ item.affected_workflows?.join(', ') || 'None' }}
                    </p>
                    <p>
                        Public footer impact:
                        {{ item.public_footer_impact || 'None' }}
                    </p>
                    <p>
                        Estimated re-acceptance obligations:
                        {{ item.estimated_reacceptance_obligations || 0 }}
                    </p>
                </div>
                <div
                    v-if="(publicationStep[item.id] || 1) === 3"
                    class="mt-4 grid gap-3 sm:grid-cols-2"
                >
                    <button
                        class="rounded-md border p-4 text-left font-black"
                        @click="publicationMode[item.id] = 'now'"
                    >
                        Publish now
                    </button>
                    <button
                        class="rounded-md border p-4 text-left font-black"
                        @click="publicationMode[item.id] = 'schedule'"
                    >
                        Schedule for later
                    </button>
                </div>
                <div
                    v-if="(publicationStep[item.id] || 1) === 4"
                    class="mt-4 rounded-md border p-4"
                >
                    <h3 class="font-black">Final confirmation</h3>
                    <p class="mt-2 text-sm">
                        {{
                            publicationMode[item.id] === 'schedule'
                                ? 'Schedule publication'
                                : 'Publish now'
                        }}
                    </p>
                    <label class="mt-3 flex gap-2 text-sm"
                        ><input
                            v-model="publicationConfirmed[item.id]"
                            type="checkbox"
                        />
                        I confirm this governed publication action.</label
                    >
                </div>
                <div
                    v-if="item.status === 'approved' && workspace.can_publish"
                    class="mt-4 flex gap-2"
                >
                    <button
                        v-if="(publicationStep[item.id] || 1) > 1"
                        class="rounded-md border px-4 py-2 font-black"
                        @click="
                            publicationStep[item.id] =
                                (publicationStep[item.id] || 1) - 1
                        "
                    >
                        Back
                    </button>
                    <button
                        v-if="(publicationStep[item.id] || 1) < 4"
                        class="rounded-md bg-slate-950 px-4 py-2 font-black text-white"
                        :disabled="
                            (publicationStep[item.id] || 1) === 1 &&
                            Object.values(item.readiness).includes('blocked')
                        "
                        @click="
                            publicationStep[item.id] =
                                (publicationStep[item.id] || 1) + 1
                        "
                    >
                        Continue
                    </button>
                </div>
                <form
                    v-if="
                        item.status === 'approved' &&
                        workspace.can_publish &&
                        publicationMode[item.id] === 'schedule' &&
                        (publicationStep[item.id] || 1) === 4
                    "
                    class="mt-5 grid gap-3 md:grid-cols-3"
                    @submit.prevent="
                        schedule.post(
                            `/admin/legal-governance/versions/${item.id}/schedule`,
                        )
                    "
                >
                    <input
                        v-model="schedule.publish_at"
                        type="datetime-local"
                        required
                        class="rounded-md border p-2"
                        aria-label="Publication time"
                    /><input
                        v-model="schedule.effective_at"
                        type="datetime-local"
                        required
                        class="rounded-md border p-2"
                        aria-label="Effective time"
                    /><button
                        class="rounded-md bg-slate-950 px-4 py-2 font-black text-white"
                    >
                        Schedule
                    </button>
                </form>
                <button
                    v-if="
                        workspace.can_publish &&
                        item.status === 'approved' &&
                        publicationMode[item.id] === 'now' &&
                        (publicationStep[item.id] || 1) === 4 &&
                        publicationConfirmed[item.id] &&
                        !Object.values(item.readiness).includes('blocked')
                    "
                    class="mt-5 rounded-md bg-emerald-700 px-4 py-2 font-black text-white"
                    @click="
                        post(
                            `/admin/legal-governance/versions/${item.id}/publish`,
                        )
                    "
                >
                    Publish frozen version
                </button>
                <button
                    v-if="item.status === 'scheduled' && workspace.can_publish"
                    class="mt-5 ml-3 rounded-md border border-amber-600 px-4 py-2 font-black text-amber-800"
                    @click="cancelSchedule(item)"
                >
                    Cancel schedule
                </button>
            </article>
            <div
                v-if="!list.length"
                class="rounded-lg border bg-white p-12 text-center text-slate-500"
            >
                No Approved or Scheduled versions.
            </div>
        </section>

        <section v-else-if="kind === 'workflows'" class="grid gap-4">
            <article
                v-for="item in list"
                :key="item.id"
                class="rounded-lg border bg-white p-5 dark:border-slate-700 dark:bg-slate-900"
            >
                <div class="flex justify-between gap-4">
                    <div>
                        <h2 class="font-black">{{ item.name }}</h2>
                        <p class="text-sm text-slate-500">
                            {{ pretty(item.trigger) }} ·
                            {{ pretty(item.audience) }} ·
                            {{ pretty(item.status) }}
                        </p>
                        <p class="mt-2 text-sm">
                            {{
                                item.requirements.join(', ') ||
                                'No requirements'
                            }}
                        </p>
                    </div>
                    <Link
                        :href="`/admin/legal-governance/workflows/${item.id}`"
                        class="font-black text-emerald-700"
                        >Configure</Link
                    >
                </div>
            </article>
        </section>

        <section
            v-else-if="kind === 'workflow-detail' && workflow"
            class="grid gap-6 lg:grid-cols-2"
        >
            <form
                class="rounded-lg border bg-white p-6 dark:border-slate-700 dark:bg-slate-900"
                @submit.prevent="
                    workflowForm.put(
                        `/admin/legal-governance/workflows/${workflow.public_id}`,
                    )
                "
            >
                <h2 class="text-lg font-black">Declarative configuration</h2>
                <label
                    v-for="field in [
                        'name',
                        'description',
                        'trigger_type',
                        'audience',
                        'blocking_behavior',
                        'priority',
                    ]"
                    :key="field"
                    class="mt-4 block text-sm font-bold capitalize"
                    >{{ pretty(field)
                    }}<input
                        v-model="(workflowForm as any)[field]"
                        class="mt-1 w-full rounded-md border p-2" /></label
                ><button
                    :disabled="!publicationConfirmed[item.id]"
                    class="mt-5 rounded-md bg-slate-950 px-4 py-2 font-black text-white"
                >
                    Save configuration
                </button>
            </form>
            <div>
                <article
                    class="rounded-lg border bg-white p-6 dark:border-slate-700 dark:bg-slate-900"
                >
                    <h2 class="font-black">User-facing preview</h2>
                    <ol class="mt-3 list-decimal space-y-3 pl-5">
                        <li
                            v-for="requirement in requirements"
                            :key="requirement.id"
                        >
                            <b>{{ requirement.document }}</b>
                            <div class="text-sm text-slate-500">
                                {{ pretty(requirement.acceptance_type) }} ·
                                {{
                                    requirement.required
                                        ? 'required'
                                        : 'optional'
                                }}
                            </div>
                            <p class="text-sm">{{ requirement.statement }}</p>
                            <button
                                type="button"
                                class="mt-2 text-xs font-black text-rose-700"
                                @click="
                                    router.delete(
                                        `/admin/legal-governance/workflows/${workflow.public_id}/requirements/${requirement.id}`,
                                        { preserveScroll: true },
                                    )
                                "
                            >
                                Remove requirement
                            </button>
                        </li>
                    </ol>
                </article>
                <form
                    class="mt-5 rounded-lg border bg-white p-6 dark:border-slate-700 dark:bg-slate-900"
                    @submit.prevent="
                        requirementForm.post(
                            `/admin/legal-governance/workflows/${workflow.public_id}/requirements`,
                        )
                    "
                >
                    <h2 class="font-black">
                        Add or replace document requirement
                    </h2>
                    <label class="mt-3 block text-sm font-bold"
                        >Document<select
                            v-model="requirementForm.document_id"
                            required
                            class="mt-1 w-full rounded-md border p-2"
                        >
                            <option value="" disabled>Select document</option>
                            <option
                                v-for="document in documents"
                                :key="document.public_id"
                                :value="document.public_id"
                            >
                                {{ document.title }} ·
                                {{ pretty(document.visibility) }}
                            </option>
                        </select></label
                    >
                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        <label class="text-sm font-bold"
                            >Sequence<input
                                v-model="requirementForm.sequence"
                                type="number"
                                min="1"
                                class="mt-1 w-full rounded-md border p-2"
                        /></label>
                        <label class="text-sm font-bold"
                            >Version rule<select
                                v-model="requirementForm.version_selection_rule"
                                class="mt-1 w-full rounded-md border p-2"
                            >
                                <option
                                    v-for="rule in [
                                        'current_published',
                                        'current_effective',
                                        'latest_material_version',
                                        'specific_version',
                                    ]"
                                    :key="rule"
                                    :value="rule"
                                >
                                    {{ pretty(rule) }}
                                </option>
                            </select></label
                        >
                        <label
                            v-if="
                                requirementForm.version_selection_rule ===
                                'specific_version'
                            "
                            class="text-sm font-bold"
                            >Specific version<input
                                v-model="requirementForm.specific_version"
                                class="mt-1 w-full rounded-md border p-2"
                        /></label>
                        <label class="text-sm font-bold"
                            >Acceptance type<select
                                v-model="requirementForm.acceptance_type"
                                class="mt-1 w-full rounded-md border p-2"
                            >
                                <option
                                    v-for="type in [
                                        'clickwrap_acceptance',
                                        'acknowledgement',
                                        'optional_consent',
                                        'electronic_signature',
                                        'organisation_execution',
                                        'no_acceptance_required',
                                    ]"
                                    :key="type"
                                    :value="type"
                                >
                                    {{ pretty(type) }}
                                </option>
                            </select></label
                        >
                        <label class="text-sm font-bold"
                            >Blocking mode<select
                                v-model="requirementForm.blocking_behavior"
                                class="mt-1 w-full rounded-md border p-2"
                            >
                                <option
                                    v-for="mode in [
                                        'notice_only',
                                        'next_login_block',
                                        'checkout_block',
                                        'feature_block',
                                        'credential_block',
                                        'organisation_admin_required',
                                    ]"
                                    :key="mode"
                                    :value="mode"
                                >
                                    {{ pretty(mode) }}
                                </option>
                            </select></label
                        >
                    </div>
                    <label
                        class="mt-3 flex items-center gap-2 text-sm font-bold"
                        ><input
                            v-model="requirementForm.is_required"
                            type="checkbox"
                        />
                        Required</label
                    >
                    <label class="mt-3 block text-sm font-bold"
                        >Acceptance statement<textarea
                            v-model="requirementForm.statement"
                            rows="3"
                            class="mt-1 w-full rounded-md border p-2"
                        />
                    </label>
                    <button
                        class="mt-4 rounded-md bg-slate-950 px-4 py-2 font-black text-white"
                    >
                        Save requirement
                    </button>
                </form>
                <button
                    v-if="!validation_errors?.length"
                    class="mt-4 rounded-md bg-emerald-700 px-4 py-2 font-black text-white"
                    @click="
                        post(
                            `/admin/legal-governance/workflows/${workflow.public_id}/activate`,
                        )
                    "
                >
                    Activate validated workflow
                </button>
            </div>
        </section>

        <form
            v-else-if="kind === 'evidence'"
            class="max-w-2xl rounded-lg border bg-white p-6 dark:border-slate-700 dark:bg-slate-900"
            method="post"
            action="/admin/legal-governance/evidence-exports"
        >
            <h2 class="text-lg font-black">Generate audited evidence bundle</h2>
            <p class="mt-2 text-sm text-slate-600">
                Exports are subject-scoped. Dashboard summaries never expose raw
                evidence. Sensitive access:
                {{ sensitive_access ? 'granted' : 'masked' }}.
            </p>
            <input type="hidden" name="_token" :value="csrf_token" /><label
                class="mt-4 block font-bold"
                >Subject type<select
                    v-model="evidence.subject_type"
                    name="subject_type"
                    class="mt-1 w-full rounded-md border p-2"
                >
                    <option
                        v-for="type in [
                            'user',
                            'manufacturer',
                            'organisation',
                            'enterprise_customer',
                            'api_client',
                            'mcp_client',
                        ]"
                        :key="type"
                        :value="type"
                    >
                        {{ pretty(type) }}
                    </option>
                </select></label
            ><label class="mt-4 block font-bold"
                >Subject reference<input
                    v-model="evidence.subject_id"
                    name="subject_id"
                    required
                    class="mt-1 w-full rounded-md border p-2" /></label
            ><label class="mt-4 block font-bold"
                >Purpose / case reference<input
                    v-model="evidence.case_reference"
                    name="case_reference"
                    required
                    class="mt-1 w-full rounded-md border p-2" /></label
            ><label class="mt-4 flex gap-2"
                ><input
                    v-model="evidence.confirmed"
                    name="confirmed"
                    value="1"
                    type="checkbox"
                    required
                />
                I confirm this scoped export is authorised and necessary.</label
            ><button
                class="mt-5 rounded-md bg-slate-950 px-4 py-2 font-black text-white"
            >
                Generate JSON evidence bundle
            </button>
        </form>

        <section v-else-if="kind === 'placeholders'" class="grid gap-4">
            <article
                v-for="item in list"
                :key="item.id"
                class="rounded-lg border bg-white p-5 dark:border-slate-700 dark:bg-slate-900"
            >
                <h2 class="font-black">
                    {{ item.document }} · {{ item.version }}
                </h2>
                <code class="mt-2 block text-sm text-rose-700">{{
                    item.placeholder
                }}</code>
                <p class="mt-2 text-sm text-slate-600">{{ item.context }}</p>
                <form
                    v-if="
                        ![
                            'published',
                            'superseded',
                            'archived',
                            'withdrawn',
                        ].includes(item.version_status)
                    "
                    class="mt-4 flex flex-wrap gap-2"
                    @submit.prevent="
                        router.patch(
                            `/admin/legal-governance/placeholders/${item.id}`,
                            {
                                status: ($event.target as HTMLFormElement)
                                    .status.value,
                                resolution: ($event.target as HTMLFormElement)
                                    .resolution.value,
                            },
                        )
                    "
                >
                    <select name="status" class="rounded-md border p-2">
                        <option value="open">Open</option>
                        <option value="resolved">Resolved</option>
                        <option value="not_applicable">
                            Not applicable
                        </option></select
                    ><input
                        name="resolution"
                        class="min-w-64 flex-1 rounded-md border p-2"
                        placeholder="Resolution note"
                    /><button
                        class="rounded-md bg-slate-950 px-4 py-2 font-black text-white"
                    >
                        Update
                    </button>
                </form>
            </article>
        </section>

        <section
            v-else-if="kind === 'settings'"
            class="rounded-lg border bg-white p-6 dark:border-slate-700 dark:bg-slate-900"
        >
            <h2 class="text-lg font-black">Safe read-only configuration</h2>
            <dl class="mt-4 divide-y">
                <div
                    v-for="(value, key) in settings"
                    :key="key"
                    class="grid gap-2 py-3 md:grid-cols-[240px_1fr]"
                >
                    <dt class="font-black capitalize">{{ pretty(key) }}</dt>
                    <dd class="font-mono text-sm break-all">
                        {{ JSON.stringify(value) }}
                    </dd>
                </div>
            </dl>
            <p class="mt-5 text-sm text-slate-500">
                Secrets, storage credentials, queues, billing, and platform
                security settings are intentionally excluded.
            </p>
        </section>
    </LegalGovernanceShell>
</template>
