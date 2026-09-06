<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { useAuth } from '@/composables/useAuth'
import ToastContainer from '@/Components/Shared/ToastContainer.vue'

const { user } = useAuth()
const menuOpen = ref(false)

const links = [
    { label: 'Inicio', route: 'portal.dashboard' },
    { label: 'Pagos', route: 'portal.payments' },
    { label: 'Calendario', route: 'portal.calendar' },
]

function hasRoute(name) {
    try {
        return typeof route === 'function' && route().has(name)
    } catch (e) {
        return false
    }
}
function href(name) {
    return hasRoute(name) ? route(name) : '#'
}
function isCurrent(name) {
    return hasRoute(name) && route().current(name)
}
function logout() {
    router.post(route('logout'))
}

router.on('navigate', () => {
    menuOpen.value = false
})
</script>

<template>
    <div class="min-h-screen bg-slate-50 text-slate-800 font-sans antialiased">
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex h-16 max-w-4xl items-center justify-between px-4">
                <Link :href="href('portal.dashboard')" class="flex items-center gap-3">
                    <img src="/images/logosj.png" alt="" class="h-9 w-9 rounded-lg bg-white object-contain p-0.5" />
                    <span class="leading-tight">
                        <span class="block text-sm font-extrabold tracking-tight text-slate-900">Scouts de San José</span>
                        <span class="block text-[11px] font-bold text-brand-500">Portal de familias</span>
                    </span>
                </Link>

                <nav class="hidden items-center gap-1 sm:flex" aria-label="Portal">
                    <Link
                        v-for="l in links"
                        :key="l.route"
                        :href="href(l.route)"
                        class="rounded-lg px-3 py-2 text-sm font-semibold transition-colors"
                        :class="isCurrent(l.route) ? 'bg-brand-50 text-brand-700' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-800'"
                    >
                        {{ l.label }}
                    </Link>
                </nav>

                <div class="flex items-center gap-3">
                    <span class="hidden text-sm text-slate-500 md:block">{{ user?.name }}</span>
                    <Link :href="href('profile.edit')" class="text-sm text-slate-400 hover:text-slate-700" title="Mi cuenta">
                        Cuenta
                    </Link>
                    <button type="button" class="text-sm text-slate-400 hover:text-red-600" @click="logout">Salir</button>
                    <button
                        type="button"
                        class="rounded-lg p-1.5 text-slate-600 hover:bg-slate-100 sm:hidden"
                        aria-label="Menú"
                        @click="menuOpen = !menuOpen"
                    >
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                    </button>
                </div>
            </div>

            <nav v-if="menuOpen" class="border-t border-slate-100 px-4 py-2 sm:hidden">
                <Link
                    v-for="l in links"
                    :key="l.route"
                    :href="href(l.route)"
                    class="block rounded-lg px-3 py-2 text-sm font-semibold"
                    :class="isCurrent(l.route) ? 'bg-brand-50 text-brand-700' : 'text-slate-600'"
                >
                    {{ l.label }}
                </Link>
            </nav>
        </header>

        <main class="mx-auto max-w-4xl px-4 py-8">
            <slot />
        </main>

        <ToastContainer />
    </div>
</template>
