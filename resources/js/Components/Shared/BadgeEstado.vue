<script setup>
import { computed } from 'vue'

const props = defineProps({
    label: { type: String, required: true },
    // color semántico: green, yellow, red, blue, gray, orange
    color: { type: String, default: 'gray' },
})

const classes = computed(() => {
    const map = {
        green: 'bg-emerald-100 text-emerald-800',
        yellow: 'bg-amber-100 text-amber-800',
        red: 'bg-red-100 text-red-800',
        blue: 'bg-blue-100 text-blue-800',
        gray: 'bg-slate-100 text-slate-700',
        orange: 'bg-orange-100 text-orange-800',
    }
    return map[props.color] ?? map.gray
})
</script>

<template>
    <span
        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
        :class="classes"
    >
        {{ label }}
    </span>
</template>
