<script setup>
import { ref, computed } from 'vue'
import { Link, usePage, router } from '@inertiajs/vue3'
import { useAuth } from '@/composables/useAuth'
import ToastContainer from '@/Components/Shared/ToastContainer.vue'

const { user, can } = useAuth()
const page = usePage()
const sidebarOpen = ref(false)

// Navegación agrupada por secciones. Cada ítem se muestra si el usuario tiene el permiso.
const sections = computed(() => [
    {
        title: null,
        items: [{ label: 'Inicio', icon: 'home', route: 'dashboard', permission: null }],
    },
    {
        title: 'Personas',
        items: [{ label: 'Miembros', icon: 'users', route: 'members.index', permission: 'members.view' }],
    },
    {
        title: 'Tesorería',
        items: [
            { label: 'Cobros', icon: 'euro', route: 'charges.index', permission: 'charges.view' },
            { label: 'Facturas', icon: 'receipt', route: 'invoices.index', permission: 'invoices.view' },
        ],
    },
    {
        title: 'Actividad',
        items: [
            { label: 'Calendario', icon: 'calendar', route: 'events.index', permission: 'events.view' },
            { label: 'Plan de rama', icon: 'target', route: 'branch-plans.index', permission: 'plans.view' },
            { label: 'Actividades', icon: 'sparkles', route: 'activities.index', permission: 'activities.view' },
            { label: 'Inventario', icon: 'box', route: 'inventory.index', permission: 'inventory.view' },
        ],
    },
    {
        title: 'Secretaría',
        items: [
            { label: 'Documentos', icon: 'folder', route: 'documents.index', permission: 'documents.view' },
            { label: 'Actas', icon: 'file', route: 'minutes.index', permission: 'minutes.view' },
            { label: 'Fotos', icon: 'photo', route: 'albums.index', permission: 'photos.view' },
        ],
    },
    {
        title: 'Sistema',
        items: [{ label: 'Ajustes', icon: 'cog', route: 'settings.edit', permission: 'settings.manage' }],
    },
])

// SVG paths (Heroicons outline) por clave de icono.
const icons = {
    home: 'M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75',
    users: 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z',
    euro: 'M14.25 7.756a4.5 4.5 0 100 8.488M7.5 10.5h5.25m-5.25 3h5.25M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    receipt: 'M8.25 9.75h4.875a2.625 2.625 0 010 5.25H12M8.25 9.75L10.5 7.5M8.25 9.75L10.5 12m9-7.243V21.75l-3.75-1.5-3.75 1.5-3.75-1.5-3.75 1.5V4.757c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0c1.1.128 1.907 1.077 1.907 2.185z',
    calendar: 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5',
    target: 'M12 21a9 9 0 100-18 9 9 0 000 18zm0-3a6 6 0 100-12 6 6 0 000 12zm0-3a3 3 0 100-6 3 3 0 000 6z',
    sparkles: 'M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z',
    box: 'M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9',
    folder: 'M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z',
    file: 'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z',
    photo: 'M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z',
    cog: 'M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.281z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
}

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

const initials = computed(() => {
    const n = user.value?.name ?? '?'
    return n.split(' ').map((p) => p[0]).slice(0, 2).join('').toUpperCase()
})
</script>

<template>
    <div class="min-h-screen bg-ink-100">
        <!-- Barra superior móvil -->
        <div class="flex items-center justify-between border-b border-ink-200 bg-white px-4 py-3 lg:hidden">
            <button class="rounded-md p-1 text-ink-600 hover:bg-ink-100" @click="sidebarOpen = !sidebarOpen" aria-label="Menú">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
            </button>
            <span class="font-bold text-brand-700">{{ page.props.app?.name }}</span>
            <span class="w-8"></span>
        </div>

        <div class="lg:flex">
            <!-- Sidebar -->
            <aside
                class="fixed inset-y-0 left-0 z-40 flex w-64 transform flex-col border-r border-ink-200 bg-white transition-transform lg:static lg:translate-x-0"
                :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            >
                <!-- Marca -->
                <div class="flex items-center gap-2.5 border-b border-ink-100 px-5 py-4">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-600 text-white">⚜</span>
                    <div class="leading-tight">
                        <p class="text-sm font-bold text-ink-800">{{ page.props.app?.name }}</p>
                        <p class="text-[11px] text-ink-400">Gestión del grupo</p>
                    </div>
                </div>

                <nav class="flex-1 space-y-5 overflow-y-auto px-3 py-4">
                    <div v-for="(section, si) in sections" :key="si">
                        <p v-if="section.title" class="mb-1 px-3 text-[11px] font-semibold uppercase tracking-wider text-ink-400">
                            {{ section.title }}
                        </p>
                        <template v-for="item in section.items" :key="item.route">
                            <Link
                                v-if="!item.permission || can(item.permission)"
                                :href="href(item.route)"
                                class="group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition"
                                :class="isCurrent(item.route)
                                    ? 'bg-brand-50 text-brand-700'
                                    : 'text-ink-600 hover:bg-ink-100 hover:text-ink-900'"
                                @click="sidebarOpen = false"
                            >
                                <svg class="h-5 w-5 shrink-0" :class="isCurrent(item.route) ? 'text-brand-600' : 'text-ink-400 group-hover:text-ink-600'"
                                     fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" :d="icons[item.icon]" />
                                </svg>
                                <span>{{ item.label }}</span>
                            </Link>
                        </template>
                    </div>
                </nav>

                <!-- Usuario -->
                <div class="border-t border-ink-100 p-3">
                    <div class="flex items-center gap-3 rounded-lg px-2 py-2">
                        <span class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-100 text-sm font-semibold text-brand-700">{{ initials }}</span>
                        <div class="min-w-0 flex-1 leading-tight">
                            <p class="truncate text-sm font-medium text-ink-800">{{ user?.name }}</p>
                            <p class="truncate text-xs text-ink-400">{{ user?.roles?.[0] }}</p>
                        </div>
                    </div>
                    <div class="mt-1 flex gap-1">
                        <Link :href="route('profile.edit')" class="flex-1 rounded-md px-2 py-1.5 text-center text-xs font-medium text-ink-500 hover:bg-ink-100">Perfil</Link>
                        <button class="flex-1 rounded-md px-2 py-1.5 text-center text-xs font-medium text-ink-500 hover:bg-brand-50 hover:text-brand-700" @click="logout">Salir</button>
                    </div>
                </div>
            </aside>

            <!-- Overlay móvil -->
            <div v-if="sidebarOpen" class="fixed inset-0 z-30 bg-ink-900/40 lg:hidden" @click="sidebarOpen = false" />

            <!-- Contenido -->
            <div class="flex min-h-screen w-full flex-col">
                <main class="mx-auto w-full max-w-7xl flex-1 p-4 sm:p-6 lg:p-8">
                    <slot />
                </main>
            </div>
        </div>

        <ToastContainer />
    </div>
</template>
