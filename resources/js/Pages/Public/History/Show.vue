<script setup>
import { Head } from '@inertiajs/vue3'

defineProps({
    entries: { type: Array, default: () => [] },
})
</script>

<template>
    <Head title="Nuestra historia" />

    <div class="min-h-screen bg-ink-100">
        <header class="border-b border-ink-200 bg-white px-4 py-5 sm:px-8">
            <h1 class="text-xl font-bold text-brand-800 sm:text-2xl">⚜️ Nuestra historia</h1>
            <p class="mt-1 text-sm text-ink-500">Un recorrido por los momentos más destacados del grupo.</p>
        </header>

        <main class="mx-auto max-w-3xl p-4 sm:p-8">
            <p v-if="!entries.length" class="rounded-lg border border-dashed border-ink-300 p-8 text-center text-ink-400">
                Próximamente la historia del grupo.
            </p>

            <ol v-else class="relative space-y-8 border-l-2 border-brand-200 pl-6">
                <li v-for="entry in entries" :key="entry.id" class="relative">
                    <span class="absolute -left-[1.65rem] top-1 h-3 w-3 rounded-full bg-brand-600"></span>
                    <span class="text-sm font-bold text-brand-700">{{ entry.year }}</span>
                    <h2 class="text-lg font-semibold text-ink-800">{{ entry.title }}</h2>
                    <img
                        v-if="entry.photo_url"
                        :src="entry.photo_url"
                        :alt="entry.title"
                        class="mt-2 h-48 w-full max-w-md rounded-lg object-cover"
                        loading="lazy"
                    />
                    <p v-if="entry.body" class="mt-2 text-sm text-ink-600">{{ entry.body }}</p>
                </li>
            </ol>
        </main>
    </div>
</template>
