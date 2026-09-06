<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { useAuth } from '@/composables/useAuth'
import ToastContainer from '@/Components/Shared/ToastContainer.vue'
import CommandPalette from '@/Components/Shared/CommandPalette.vue'
import DashIcon from '@/Components/Shared/DashIcon.vue'

const { user, can } = useAuth()

const sidebarOpen = ref(false)      // cajón en móvil
const plegada = ref(false)          // modo compacto en escritorio
const paleta = ref(null)

// El estado plegado se recuerda entre visitas.
onMounted(() => {
    try {
        plegada.value = localStorage.getItem('nav:plegada') === '1'
    } catch (e) {
        plegada.value = false
    }
})

watch(plegada, (valor) => {
    try {
        localStorage.setItem('nav:plegada', valor ? '1' : '0')
    } catch (e) {
        // Modo privado: seguimos sin recordar la preferencia.
    }
})

// Navegación agrupada. Cada ítem se muestra si el usuario tiene el permiso.
const sections = computed(() => [
    {
        title: null,
        items: [{ label: 'Inicio', icon: 'home', route: 'dashboard', permission: null }],
    },
    {
        title: 'Personas',
        items: [
            { label: 'Miembros', icon: 'users', route: 'members.index', permission: 'members.view' },
            { label: 'Familias', icon: 'heart', route: 'families.index', permission: 'members.view' },
            { label: 'Asistencia', icon: 'check', route: 'attendance.index', permission: 'attendance.manage' },
            { label: 'Revisiones de familias', icon: 'inbox', route: 'member-change-requests.index', permission: 'members.manage' },
        ],
    },
    {
        title: 'Tesorería',
        items: [
            { label: 'Panel Tesorería', icon: 'chart', route: 'finance.dashboard', permission: null },
            { label: 'Cobros', icon: 'euro', route: 'charges.index', permission: 'charges.view' },
            { label: 'Facturas', icon: 'receipt', route: 'invoices.index', permission: 'invoices.view' },
        ],
    },
    {
        title: 'Actividad',
        items: [
            { label: 'Eventos y Salidas', icon: 'calendar', route: 'events.index', permission: 'events.view' },
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
        items: [
            { label: 'Usuarios', icon: 'users', route: 'users.index', permission: 'users.manage' },
            { label: 'Ajustes', icon: 'cog', route: 'settings.edit', permission: 'settings.finance' },
        ],
    },
])

// SVG paths (Heroicons outline) por clave de icono, para la barra lateral.
const icons = {
    home: 'M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75',
    users: 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z',
    heart: 'M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z',
    check: 'M4.5 12.75l6 6 9-13.5',
    euro: 'M14.25 7.756a4.5 4.5 0 100 8.488M7.5 10.5h5.25m-5.25 3h5.25M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    receipt: 'M8.25 9.75h4.875a2.625 2.625 0 010 5.25H12M8.25 9.75L10.5 7.5M8.25 9.75L10.5 12m9-7.243V21.75l-3.75-1.5-3.75 1.5-3.75-1.5-3.75 1.5V4.757c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0c1.1.128 1.907 1.077 1.907 2.185z',
    calendar: 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5',
    target: 'M12 21a9 9 0 100-18 9 9 0 000 18zm0-3a6 6 0 100-12 6 6 0 000 12zm0-3a3 3 0 100-6 3 3 0 000 6z',
    sparkles: 'M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z',
    chart: 'M10.5 6a7.5 7.5 0 107.5 7.5h-7.5V6z M13.5 10.5H21A7.5 7.5 0 0013.5 3v7.5z',
    box: 'M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9',
    folder: 'M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z',
    file: 'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z',
    photo: 'M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z',
    cog: 'M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.281z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
    inbox: 'M2.25 13.5h3.86a2.25 2.25 0 012.012 1.244l.256.512a2.25 2.25 0 002.013 1.244h3.218a2.25 2.25 0 002.013-1.244l.256-.512a2.25 2.25 0 012.013-1.244h3.859m-19.5.338V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 00-2.15-1.588H6.911a2.25 2.25 0 00-2.15 1.588L2.35 13.177a2.25 2.25 0 00-.1.661z',
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

// Sección activa, para el título de la barra superior. Reconoce también las
// subpáginas: estando en members.show o members.edit, la sección sigue siendo Miembros.
const tituloActual = computed(() => {
    const items = sections.value.flatMap((s) => s.items)

    const exacto = items.find((i) => isCurrent(i.route))
    if (exacto) return exacto.label

    try {
        const actual = route().current()
        if (actual) {
            const prefijo = actual.split('.')[0]
            const porGrupo = items.find((i) => i.route.split('.')[0] === prefijo)
            if (porGrupo) return porGrupo.label
        }
    } catch (e) {
        // Ziggy no disponible: caemos al título genérico.
    }

    return 'Plataforma'
})

// Comandos de la paleta: navegación + acciones de creación frecuentes.
const comandos = computed(() => {
    const navegacion = sections.value.flatMap((seccion) =>
        seccion.items
            .filter((item) => (!item.permission || can(item.permission)) && hasRoute(item.route))
            .map((item) => ({
                label: item.label,
                hint: seccion.title,
                icon: item.icon,
                href: href(item.route),
                group: 'Ir a',
            })),
    )

    const acciones = [
        { label: 'Nuevo miembro', route: 'members.create', icon: 'userPlus', permission: 'members.manage' },
        { label: 'Nuevo evento', route: 'events.create', icon: 'calendarPlus', permission: 'events.manage' },
        { label: 'Nueva actividad', route: 'activities.create', icon: 'sparkles', permission: 'activities.manage' },
        { label: 'Nueva familia', route: 'families.create', icon: 'heart', permission: 'members.manage' },
        { label: 'Importar miembros', route: 'members.import', icon: 'upload', permission: 'members.manage' },
        { label: 'Mi perfil', route: 'profile.edit', icon: 'users', permission: null },
    ]
        .filter((a) => (!a.permission || can(a.permission)) && hasRoute(a.route))
        .map((a) => ({ label: a.label, icon: a.icon, href: href(a.route), group: 'Acciones' }))

    return [...acciones, ...navegacion]
})

const initials = computed(() => {
    const n = user.value?.name ?? '?'
    return n.split(' ').map((p) => p[0]).slice(0, 2).join('').toUpperCase()
})

// Cierra el cajón móvil al navegar.
router.on('navigate', () => {
    sidebarOpen.value = false
})
</script>

<template>
    <div class="min-h-screen bg-slate-50 text-slate-800 font-sans antialiased">
        <div class="lg:flex">
            <!-- ═══ Barra lateral ═══ -->
            <aside
                class="fixed inset-y-0 left-0 z-40 flex transform flex-col bg-slate-900 text-white shadow-xl transition-[width,transform] duration-300 ease-suave lg:static lg:translate-x-0"
                :class="[
                    plegada ? 'w-64 lg:w-[4.5rem]' : 'w-64',
                    sidebarOpen ? 'translate-x-0' : '-translate-x-full',
                ]"
            >
                <!-- Marca -->
                <div class="flex h-16 items-center gap-3 border-b border-slate-800 bg-slate-950/50 px-4">
                    <Link :href="href('dashboard')" class="flex min-w-0 items-center gap-3" aria-label="Ir al panel">
                        <img
                            src="/images/logosj.png"
                            alt=""
                            class="h-9 w-9 shrink-0 rounded-lg bg-white object-contain p-1"
                            :class="plegada ? 'lg:mx-auto' : ''"
                        />
                        <span v-show="!plegada" class="min-w-0 leading-tight lg:block">
                            <span class="block truncate text-sm font-extrabold tracking-tight text-white">Scouts de San José</span>
                            <span class="block text-[11px] font-bold text-brand-400">Plataforma de Gestión</span>
                        </span>
                    </Link>
                </div>

                <!-- Navegación -->
                <nav class="custom-scrollbar flex-1 space-y-5 overflow-y-auto px-3 py-4" aria-label="Menú principal">
                    <div v-for="(section, si) in sections" :key="si">
                        <p
                            v-if="section.title && !plegada"
                            class="mb-1.5 px-3 text-[11px] font-bold uppercase tracking-wider text-slate-500"
                        >
                            {{ section.title }}
                        </p>
                        <div v-else-if="section.title" class="mx-3 mb-2 border-t border-slate-800"></div>

                        <div class="space-y-0.5">
                            <template v-for="item in section.items" :key="item.route">
                                <Link
                                    v-if="!item.permission || can(item.permission)"
                                    :href="href(item.route)"
                                    class="group relative flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition-colors duration-200"
                                    :class="isCurrent(item.route)
                                        ? 'bg-brand-600/15 text-brand-300'
                                        : 'text-slate-400 hover:bg-slate-800 hover:text-white'"
                                    :title="plegada ? item.label : null"
                                    @click="sidebarOpen = false"
                                >
                                    <!-- Marca de página actual -->
                                    <span
                                        v-if="isCurrent(item.route)"
                                        class="absolute inset-y-1.5 left-0 w-1 rounded-r-full bg-brand-500"
                                        aria-hidden="true"
                                    ></span>
                                    <svg
                                        class="h-5 w-5 shrink-0 transition-transform duration-200 group-hover:scale-110"
                                        :class="[
                                            isCurrent(item.route) ? 'text-brand-400' : 'text-slate-500 group-hover:text-slate-300',
                                            plegada ? 'lg:mx-auto' : '',
                                        ]"
                                        fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" :d="icons[item.icon]" />
                                    </svg>
                                    <span v-show="!plegada" class="truncate">{{ item.label }}</span>

                                    <!-- Etiqueta flotante en modo compacto -->
                                    <span
                                        v-if="plegada"
                                        class="pointer-events-none absolute left-full z-50 ml-2 hidden whitespace-nowrap rounded-lg bg-slate-800 px-2.5 py-1.5 text-xs font-semibold text-white opacity-0 shadow-lg transition-opacity duration-200 group-hover:opacity-100 lg:block"
                                    >
                                        {{ item.label }}
                                    </span>
                                </Link>
                            </template>
                        </div>
                    </div>
                </nav>

                <!-- Usuario -->
                <div class="border-t border-slate-800 bg-slate-950/50 p-3">
                    <div v-show="!plegada" class="flex items-center gap-3 rounded-xl bg-slate-900 p-2.5 ring-1 ring-slate-800">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-500 text-sm font-bold text-white">
                            {{ initials }}
                        </span>
                        <div class="min-w-0 flex-1 leading-tight">
                            <p class="truncate text-sm font-bold text-white">{{ user?.name }}</p>
                            <p class="truncate text-xs font-medium capitalize text-slate-400">{{ user?.roles?.[0] || 'Responsable' }}</p>
                        </div>
                    </div>
                    <div v-show="!plegada" class="mt-2 flex gap-1.5">
                        <Link :href="href('profile.edit')" class="flex-1 rounded-lg border border-slate-700 bg-slate-800 py-1.5 text-center text-xs font-semibold text-slate-300 transition hover:bg-slate-700 hover:text-white">
                            Perfil
                        </Link>
                        <button class="flex-1 rounded-lg border border-red-500/30 bg-red-500/10 py-1.5 text-center text-xs font-semibold text-red-400 transition hover:bg-red-500/20" @click="logout">
                            Salir
                        </button>
                    </div>

                    <!-- En modo compacto solo el avatar -->
                    <Link
                        v-show="plegada"
                        :href="href('profile.edit')"
                        class="mx-auto hidden h-9 w-9 items-center justify-center rounded-lg bg-brand-500 text-sm font-bold text-white lg:flex"
                        :title="user?.name"
                    >
                        {{ initials }}
                    </Link>
                </div>
            </aside>

            <!-- Fondo del cajón móvil -->
            <Transition
                enter-active-class="transition-opacity duration-200"
                enter-from-class="opacity-0"
                leave-active-class="transition-opacity duration-200"
                leave-to-class="opacity-0"
            >
                <div v-if="sidebarOpen" class="fixed inset-0 z-30 bg-slate-900/50 backdrop-blur-sm lg:hidden" @click="sidebarOpen = false" />
            </Transition>

            <!-- ═══ Contenido ═══ -->
            <div class="flex min-h-screen w-full min-w-0 flex-col">
                <!-- Barra superior -->
                <header class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-slate-200 bg-white/85 px-4 backdrop-blur-md sm:px-6">
                    <!-- Menú (móvil) -->
                    <button
                        type="button"
                        class="rounded-lg p-2 text-slate-600 transition hover:bg-slate-100 lg:hidden"
                        aria-label="Abrir menú"
                        @click="sidebarOpen = true"
                    >
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                    </button>

                    <!-- Plegar/desplegar (escritorio) -->
                    <button
                        type="button"
                        class="hidden rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-800 lg:block"
                        :aria-label="plegada ? 'Desplegar el menú' : 'Plegar el menú'"
                        :title="plegada ? 'Desplegar el menú' : 'Plegar el menú'"
                        @click="plegada = !plegada"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 5.25h16.5m-16.5 6.75h16.5m-16.5 6.75h16.5" />
                        </svg>
                    </button>

                    <p class="truncate text-sm font-bold text-slate-900 sm:text-base">{{ tituloActual }}</p>

                    <!-- Buscador global -->
                    <button
                        type="button"
                        class="ml-auto flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 py-2 pl-3 pr-2 text-sm text-slate-400 transition hover:border-slate-300 hover:bg-white sm:w-64"
                        @click="paleta?.abrir()"
                    >
                        <DashIcon name="search" class="h-4 w-4 shrink-0" />
                        <span class="hidden sm:block">Buscar…</span>
                        <kbd class="ml-auto hidden rounded border border-slate-200 bg-white px-1.5 py-0.5 text-[11px] font-semibold text-slate-500 sm:block">
                            Ctrl K
                        </kbd>
                    </button>

                    <!-- Avatar (móvil) -->
                    <Link :href="href('profile.edit')" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-500 text-xs font-bold text-white lg:hidden">
                        {{ initials }}
                    </Link>
                </header>

                <main class="mx-auto w-full max-w-7xl flex-1 p-4 sm:p-6 lg:p-8">
                    <slot />
                </main>
            </div>
        </div>

        <CommandPalette ref="paleta" :commands="comandos" />
        <ToastContainer />
    </div>
</template>
