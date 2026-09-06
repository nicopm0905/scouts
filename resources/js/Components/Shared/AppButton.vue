<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import DashIcon from '@/Components/Shared/DashIcon.vue'

/**
 * Botón único de la plataforma. Se renderiza como <button>, como enlace Inertia
 * (si recibe `href`) o como <a> externo (si recibe `href` y `external`).
 *
 * Variantes: primary · secondary · ghost · danger
 * Tamaños:   sm · md
 */
const props = defineProps({
    variant: { type: String, default: 'secondary' },
    size: { type: String, default: 'md' },
    href: { type: String, default: null },
    external: { type: Boolean, default: false },
    method: { type: String, default: 'get' },
    icon: { type: String, default: null },
    iconRight: { type: String, default: null },
    type: { type: String, default: 'button' },
    disabled: { type: Boolean, default: false },
    loading: { type: Boolean, default: false },
})

const variantes = {
    primary: 'border-transparent bg-brand-600 text-white shadow-sm hover:bg-brand-700 hover:shadow-md focus-visible:outline-brand-600',
    secondary: 'border-slate-200 bg-white text-slate-700 shadow-2xs hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900 focus-visible:outline-slate-400',
    ghost: 'border-transparent bg-transparent text-slate-600 hover:bg-slate-100 hover:text-slate-900 focus-visible:outline-slate-400',
    danger: 'border-transparent bg-rose-600 text-white shadow-sm hover:bg-rose-700 focus-visible:outline-rose-600',
}

const tamanos = {
    sm: 'gap-1.5 rounded-lg px-2.5 py-1.5 text-xs',
    md: 'gap-2 rounded-xl px-4 py-2.5 text-sm',
}

const clases = computed(() => [
    'inline-flex items-center justify-center border font-semibold transition-all duration-200',
    'focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2',
    'active:scale-[0.98] motion-reduce:transform-none',
    'disabled:pointer-events-none disabled:opacity-50',
    variantes[props.variant] ?? variantes.secondary,
    tamanos[props.size] ?? tamanos.md,
])

const tamanoIcono = computed(() => (props.size === 'sm' ? 'h-3.5 w-3.5' : 'h-4 w-4'))
</script>

<template>
    <component
        :is="href ? (external ? 'a' : Link) : 'button'"
        :class="clases"
        :href="href || undefined"
        :method="href && !external ? method : undefined"
        :as="href && !external && method !== 'get' ? 'button' : undefined"
        :target="external ? '_blank' : undefined"
        :rel="external ? 'noopener' : undefined"
        :type="href ? undefined : type"
        :disabled="href ? undefined : (disabled || loading)"
    >
        <!-- Girando mientras se envía -->
        <svg v-if="loading" class="animate-spin" :class="tamanoIcono" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z" />
        </svg>
        <DashIcon v-else-if="icon" :name="icon" :class="tamanoIcono" />

        <slot />

        <DashIcon v-if="iconRight" :name="iconRight" :class="tamanoIcono" />
    </component>
</template>
