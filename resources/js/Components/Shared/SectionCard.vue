<script setup>
import DashIcon from '@/Components/Shared/DashIcon.vue'

/**
 * Bloque de contenido con cabecera. Es la caja estándar de la plataforma:
 * misma esquina, mismo borde y misma cabecera con icono y acciones a la derecha.
 */
defineProps({
    title: { type: String, default: null },
    subtitle: { type: String, default: null },
    icon: { type: String, default: null },
    tone: { type: String, default: 'neutral' },
    // Sin relleno cuando dentro va una tabla a sangre.
    flush: { type: Boolean, default: false },
})

const fondos = {
    neutral: 'bg-slate-100 text-slate-500',
    brand: 'bg-brand-50 text-brand-700',
    positive: 'bg-emerald-50 text-emerald-600',
    warning: 'bg-amber-50 text-amber-600',
    danger: 'bg-rose-50 text-rose-600',
    info: 'bg-sky-50 text-sky-700',
}
</script>

<template>
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xs">
        <header
            v-if="title || $slots.actions"
            class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4"
        >
            <div class="flex min-w-0 items-center gap-2.5">
                <span
                    v-if="icon"
                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg"
                    :class="fondos[tone] ?? fondos.neutral"
                >
                    <DashIcon :name="icon" class="h-4 w-4" />
                </span>
                <div class="min-w-0">
                    <h2 v-if="title" class="truncate text-base font-bold text-slate-900">{{ title }}</h2>
                    <p v-if="subtitle" class="truncate text-xs font-medium text-slate-500">{{ subtitle }}</p>
                </div>
            </div>
            <div v-if="$slots.actions" class="flex flex-wrap items-center gap-2">
                <slot name="actions" />
            </div>
        </header>

        <div :class="flush ? '' : 'p-5'">
            <slot />
        </div>
    </section>
</template>
