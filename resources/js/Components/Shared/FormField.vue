<script setup>
import { computed, useSlots } from 'vue'

const props = defineProps({
    label: { type: String, default: null },
    modelValue: { type: [String, Number, Boolean], default: '' },
    type: { type: String, default: 'text' }, // text, email, number, date, textarea, select, checkbox
    error: { type: String, default: null },
    required: { type: Boolean, default: false },
    placeholder: { type: String, default: '' },
    options: { type: Array, default: () => [] }, // [{ value, label }] para select
    hint: { type: String, default: null },
})

const emit = defineEmits(['update:modelValue'])
const slots = useSlots()

const value = computed({
    get: () => props.modelValue,
    set: (v) => emit('update:modelValue', v),
})

const inputClasses =
    'mt-1 block w-full rounded-lg border-ink-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm'
</script>

<template>
    <div>
        <label v-if="label" class="block text-sm font-medium text-slate-700">
            {{ label }}
            <span v-if="required" class="text-red-500">*</span>
        </label>

        <!-- Slot libre para casos complejos -->
        <slot v-if="slots.default" />

        <textarea
            v-else-if="type === 'textarea'"
            v-model="value"
            :placeholder="placeholder"
            rows="4"
            :class="inputClasses"
        />

        <select v-else-if="type === 'select'" v-model="value" :class="inputClasses">
            <option v-for="opt in options" :key="opt.value" :value="opt.value">
                {{ opt.label }}
            </option>
        </select>

        <label v-else-if="type === 'checkbox'" class="mt-1 inline-flex items-center gap-2">
            <input
                type="checkbox"
                v-model="value"
                class="rounded border-ink-300 text-brand-600 focus:ring-brand-500"
            />
            <span class="text-sm text-slate-600">{{ placeholder }}</span>
        </label>

        <input
            v-else
            :type="type"
            v-model="value"
            :placeholder="placeholder"
            :class="inputClasses"
        />

        <p v-if="hint && !error" class="mt-1 text-xs text-slate-500">{{ hint }}</p>
        <p v-if="error" class="mt-1 text-xs text-red-600">{{ error }}</p>
    </div>
</template>
