<script setup>
import DashIcon from '@/Components/Shared/DashIcon.vue'

/** Campo de búsqueda uniforme (lo usa DataTable y las páginas con filtro propio). */
defineProps({
    modelValue: { type: String, default: '' },
    placeholder: { type: String, default: 'Buscar…' },
})

defineEmits(['update:modelValue'])
</script>

<template>
    <div class="relative w-full sm:max-w-xs">
        <DashIcon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
        <input
            :value="modelValue"
            type="search"
            :placeholder="placeholder"
            class="w-full rounded-xl border-slate-200 bg-white py-2.5 pl-9 pr-3 text-sm text-slate-800 shadow-2xs transition-colors placeholder:text-slate-400 focus:border-brand-500 focus:ring-brand-500"
            @input="$emit('update:modelValue', $event.target.value)"
        />
    </div>
</template>
