<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import DashIcon from '@/Components/Shared/DashIcon.vue'

/**
 * Tarjeta de cifra (KPI). Misma forma en todas las pantallas.
 * Tonos: neutral · positive · warning · danger · brand
 * Si recibe `href`, toda la tarjeta es clicable.
 */
const props = defineProps({
    label: { type: String, required: true },
    value: { type: [String, Number], required: true },
    hint: { type: String, default: null },
    tone: { type: String, default: 'neutral' },
    icon: { type: String, default: null },
    href: { type: String, default: null },
})

const tonos = {
    neutral: 'text-slate-900',
    positive: 'text-emerald-600',
    warning: 'text-amber-600',
    danger: 'text-rose-600',
    brand: 'text-brand-700',
}

const fondosIcono = {
    neutral: 'bg-slate-100 text-slate-500',
    positive: 'bg-emerald-50 text-emerald-600',
    warning: 'bg-amber-50 text-amber-600',
    danger: 'bg-rose-50 text-rose-600',
    brand: 'bg-brand-50 text-brand-700',
}

const colorCifra = computed(() => tonos[props.tone] ?? tonos.neutral)
const colorIcono = computed(() => fondosIcono[props.tone] ?? fondosIcono.neutral)
</script>

<template>
    <component
        :is="href ? Link : 'div'"
        :href="href || undefined"
        class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-2xs sm:p-5"
        :class="href ? 'transition-all duration-200 hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-md motion-reduce:transform-none' : ''"
    >
        <span
            v-if="icon"
            class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl"
            :class="colorIcono"
        >
            <DashIcon :name="icon" class="h-4 w-4" />
        </span>

        <span class="min-w-0 flex-1">
            <span class="block text-xs font-bold uppercase tracking-wide text-slate-500">{{ label }}</span>
            <span class="mt-1 block text-2xl font-extrabold tracking-tight sm:text-3xl" :class="colorCifra">{{ value }}</span>
            <span v-if="hint" class="mt-0.5 block truncate text-xs font-medium text-slate-500">{{ hint }}</span>
        </span>

        <DashIcon v-if="href" name="chevron" class="mt-1 h-4 w-4 shrink-0 text-slate-300" />
    </component>
</template>
