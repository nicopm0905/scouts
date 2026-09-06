<script setup>
import DashIcon from '@/Components/Shared/DashIcon.vue'

/**
 * Cabecera de página. Ligera a propósito: el nombre de la sección ya aparece en
 * la barra superior, así que aquí manda el contenido, no un bloque decorativo.
 *
 * Slots: `actions` (botones a la derecha) y `meta` (chips o datos bajo el título).
 */
defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: null },
    icon: { type: String, default: null },
})
</script>

<template>
    <div class="mb-6 flex flex-col gap-4 border-b border-slate-200 pb-5 sm:flex-row sm:items-end sm:justify-between">
        <div class="flex min-w-0 items-start gap-3">
            <span
                v-if="icon"
                class="mt-1 flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700"
            >
                <DashIcon :name="icon" class="h-5 w-5" />
            </span>
            <div class="min-w-0">
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">{{ title }}</h1>
                <p v-if="subtitle" class="mt-1 text-sm font-medium text-slate-500">{{ subtitle }}</p>
                <div v-if="$slots.meta" class="mt-2 flex flex-wrap items-center gap-2">
                    <slot name="meta" />
                </div>
            </div>
        </div>

        <div v-if="$slots.actions" class="flex flex-wrap items-center gap-2">
            <slot name="actions" />
        </div>
    </div>
</template>
