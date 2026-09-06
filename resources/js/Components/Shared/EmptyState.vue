<script setup>
import DashIcon from '@/Components/Shared/DashIcon.vue'

/** Estado vacío uniforme: icono, explicación y, opcionalmente, una acción. */
defineProps({
    title: { type: String, required: true },
    description: { type: String, default: null },
    icon: { type: String, default: 'search' },
})
</script>

<template>
    <div class="flex flex-col items-center justify-center gap-3 px-6 py-12 text-center">
        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
            <DashIcon :name="icon" class="h-6 w-6" />
        </span>
        <div>
            <p class="text-sm font-bold text-slate-800">{{ title }}</p>
            <p v-if="description" class="mt-1 max-w-sm text-sm text-slate-500">{{ description }}</p>
        </div>
        <div v-if="$slots.default" class="mt-1">
            <slot />
        </div>
    </div>
</template>
