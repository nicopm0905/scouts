<script setup>
import { Link, usePage } from '@inertiajs/vue3'

const page = usePage()
</script>

<template>
    <div class="flex min-h-screen flex-col items-center justify-center bg-ink-100 px-4 py-8">
        <Link href="/" class="mb-6 flex items-center gap-3">
            <img src="/images/logosj.png" alt="Scouts de San José" class="h-12 w-auto object-contain bg-white rounded-xl p-1 shadow-sm" />
            <div class="leading-tight">
                <p class="text-lg font-bold text-ink-900">{{ page.props.app?.name ?? 'Grupo Scout' }}</p>
                <p class="text-xs text-ink-400">Plataforma de gestión</p>
            </div>
        </Link>

        <div class="w-full overflow-hidden rounded-2xl border border-ink-200 bg-white px-7 py-8 shadow-card sm:max-w-md">
            <slot />
        </div>

        <p class="mt-6 text-xs text-ink-400">Movimiento Scout Católico · Andalucía</p>
    </div>
</template>
