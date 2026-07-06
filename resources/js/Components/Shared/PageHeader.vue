<script setup>
defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: null },
})
</script>

<template>
    <div class="mb-6 flex flex-col gap-3 border-b border-ink-200 pb-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold text-ink-900 sm:text-2xl">{{ title }}</h1>
            <p v-if="subtitle" class="mt-1 text-sm text-ink-500">{{ subtitle }}</p>
        </div>
        <div v-if="$slots.actions" class="flex flex-wrap items-center gap-2">
            <slot name="actions" />
        </div>
    </div>
</template>
