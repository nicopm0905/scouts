<script setup>
import { ref, computed } from 'vue'
import { Link, usePage, router } from '@inertiajs/vue3'
import { useAuth } from '@/composables/useAuth'
import ToastContainer from '@/Components/Shared/ToastContainer.vue'

const { user, can } = useAuth()
const page = usePage()
const sidebarOpen = ref(false)

// Navegación. Cada ítem se muestra solo si el usuario tiene el permiso.
// route: nombre de ruta Laravel (los agentes registran estas rutas en Fase 1).
const nav = computed(() => [
    { label: 'Inicio', icon: '🏠', route: 'dashboard', permission: null },
    { label: 'Miembros', icon: '👥', route: 'members.index', permission: 'members.view' },
    { label: 'Cobros', icon: '💶', route: 'charges.index', permission: 'charges.view' },
    { label: 'Facturas', icon: '🧾', route: 'invoices.index', permission: 'invoices.view' },
    { label: 'Calendario', icon: '📅', route: 'events.index', permission: 'events.view' },
    { label: 'Documentos', icon: '📁', route: 'documents.index', permission: 'documents.view' },
    { label: 'Actas', icon: '📝', route: 'minutes.index', permission: 'minutes.view' },
    { label: 'Plan de rama', icon: '🎯', route: 'branch-plans.index', permission: 'plans.view' },
    { label: 'Actividades', icon: '🎲', route: 'activities.index', permission: 'activities.view' },
    { label: 'Inventario', icon: '📦', route: 'inventory.index', permission: 'inventory.view' },
    { label: 'Fotos', icon: '📷', route: 'albums.index', permission: 'photos.view' },
    { label: 'Ajustes', icon: '⚙️', route: 'settings.edit', permission: 'settings.manage' },
])

const visibleNav = computed(() =>
    nav.value.filter((i) => !i.permission || can(i.permission))
)

// Comprueba si una ruta existe (aún no todas están registradas en Fase 0).
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
</script>

<template>
    <div class="min-h-screen bg-slate-100">
        <!-- Barra superior móvil -->
        <div class="flex items-center justify-between border-b border-slate-200 bg-white px-4 py-3 lg:hidden">
            <button class="text-2xl" @click="sidebarOpen = !sidebarOpen" aria-label="Menú">☰</button>
            <span class="font-bold text-emerald-700">{{ page.props.app?.name }}</span>
            <span class="w-6"></span>
        </div>

        <div class="lg:flex">
            <!-- Sidebar -->
            <aside
                class="fixed inset-y-0 left-0 z-40 w-64 transform overflow-y-auto bg-emerald-800 text-emerald-50 transition-transform lg:static lg:translate-x-0"
                :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            >
                <div class="px-5 py-5 text-lg font-bold tracking-tight">
                    ⚜️ {{ page.props.app?.name }}
                </div>
                <nav class="space-y-1 px-3 pb-6">
                    <Link
                        v-for="item in visibleNav"
                        :key="item.route"
                        :href="href(item.route)"
                        class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition"
                        :class="isCurrent(item.route) ? 'bg-emerald-900 text-white' : 'text-emerald-100 hover:bg-emerald-700'"
                        @click="sidebarOpen = false"
                    >
                        <span>{{ item.icon }}</span>
                        <span>{{ item.label }}</span>
                    </Link>
                </nav>
            </aside>

            <!-- Overlay móvil -->
            <div v-if="sidebarOpen" class="fixed inset-0 z-30 bg-slate-900/40 lg:hidden" @click="sidebarOpen = false" />

            <!-- Contenido -->
            <div class="flex min-h-screen w-full flex-col">
                <header class="hidden items-center justify-between border-b border-slate-200 bg-white px-6 py-3 lg:flex">
                    <div v-if="$slots.header" class="text-sm text-slate-500">
                        <slot name="header" />
                    </div>
                    <div class="ml-auto flex items-center gap-4 text-sm">
                        <span class="text-slate-600">{{ user?.name }}</span>
                        <Link :href="route('profile.edit')" class="text-slate-500 hover:text-slate-700">Perfil</Link>
                        <button class="text-slate-500 hover:text-red-600" @click="logout">Salir</button>
                    </div>
                </header>

                <main class="flex-1 p-4 sm:p-6 lg:p-8">
                    <slot />
                </main>
            </div>
        </div>

        <ToastContainer />
    </div>
</template>
