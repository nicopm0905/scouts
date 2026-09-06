<script setup>
import { computed } from 'vue'

const props = defineProps({
    rama: { type: String, required: true }, // castor, lobato, ranger, pionero, ruta, responsable
    size: { type: String, default: 'md' },   // sm, md, lg
})

const config = computed(() => {
    const key = (props.rama || '').toLowerCase()
    const map = {
        castor: {
            label: 'Castor',
            bg: 'bg-orange-100/90 text-orange-900 border-orange-300',
            dot: 'bg-orange-500',
        },
        lobato: {
            label: 'Lobato',
            bg: 'bg-amber-100/90 text-amber-900 border-amber-300',
            dot: 'bg-amber-500',
        },
        ranger: {
            label: 'Ranger',
            bg: 'bg-blue-900 text-blue-100 border-blue-800',
            dot: 'bg-blue-400',
        },
        pionero: {
            label: 'Pionero',
            bg: 'bg-red-100 text-red-900 border-red-300',
            dot: 'bg-red-600',
        },
        ruta: {
            label: 'Ruta',
            bg: 'bg-emerald-100 text-emerald-900 border-emerald-300',
            dot: 'bg-emerald-600',
        },
        responsable: {
            label: 'Responsable',
            bg: 'bg-sky-100 text-sky-900 border-sky-300',
            dot: 'bg-sky-500',
        },
    }

    return map[key] || {
        label: props.rama,
        bg: 'bg-slate-100 text-slate-800 border-slate-300',
        dot: 'bg-slate-400',
    }
})

const sizeClasses = computed(() => {
    if (props.size === 'sm') return 'px-2 py-0.5 text-xs font-medium'
    if (props.size === 'lg') return 'px-3.5 py-1 text-sm font-semibold'
    return 'px-2.5 py-1 text-xs font-semibold'
})
</script>

<template>
    <span
        class="inline-flex items-center gap-1.5 rounded-full border shadow-sm transition-all"
        :class="[config.bg, sizeClasses]"
    >
        <span class="h-2 w-2 rounded-full shadow-xs" :class="config.dot"></span>
        <span>{{ config.label }}</span>
    </span>
</template>
