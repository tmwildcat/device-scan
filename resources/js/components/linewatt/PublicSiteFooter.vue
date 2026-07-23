<script setup lang="ts">
defineProps<{ legalDocuments: Array<{ title: string; href: string }> }>();
</script>

<template>
    <footer class="border-t border-slate-800 bg-slate-950 text-slate-300">
        <div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:px-6 md:grid-cols-2 lg:px-8">
            <div>
                <div class="text-lg font-black text-white">LineWatt Library</div>
                <p class="mt-2 max-w-md text-sm leading-6 text-slate-400">Trusted renewable-energy engineering information and governed public resources.</p>
            </div>
            <nav v-if="legalDocuments.length" aria-label="Legal" class="md:justify-self-end">
                <h2 class="text-sm font-black uppercase tracking-wider text-white">Legal</h2>
                <ul class="mt-3 grid gap-2 sm:grid-cols-2">
                    <li v-for="document in legalDocuments" :key="document.href">
                        <a :href="document.href" class="rounded text-sm hover:text-white focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-emerald-300">{{ document.title }}</a>
                    </li>
                </ul>
            </nav>
        </div>
        <div class="border-t border-slate-800 px-4 py-4 text-center text-xs text-slate-500">© {{ new Date().getFullYear() }} LineWatt. All rights reserved.</div>
    </footer>
</template>
