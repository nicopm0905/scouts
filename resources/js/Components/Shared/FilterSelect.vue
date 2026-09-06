<script setup>
/**
 * Desplegable de filtro con la misma forma en todas las pantallas.
 * Las opciones pueden ser cadenas o { value, label }.
 */
defineProps({
    modelValue: { type: [String, Number, null], default: '' },
    options: { type: Array, default: () => [] },
    label: { type: String, default: null },
})

defineEmits(['update:modelValue'])

const normaliza = (o) => (typeof o === 'object' && o !== null ? o : { value: o, label: o })
</script>

<template>
    <label class="inline-flex items-center gap-2">
        <span v-if="label" class="whitespace-nowrap text-xs font-semibold text-slate-500">{{ label }}</span>
        <select
            :value="modelValue"
            class="rounded-xl border-slate-200 bg-white py-2.5 pl-3 pr-9 text-sm font-medium text-slate-700 shadow-2xs transition-colors focus:border-brand-500 focus:ring-brand-500"
            @change="$emit('update:modelValue', $event.target.value)"
        >
            <option v-for="opcion in options.map(normaliza)" :key="String(opcion.value)" :value="opcion.value">
                {{ opcion.label }}
            </option>
        </select>
    </label>
</template>
