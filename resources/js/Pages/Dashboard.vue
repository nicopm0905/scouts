<script setup>
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import BadgeEstado from '@/Components/Shared/BadgeEstado.vue'
import DashIcon from '@/Components/Shared/DashIcon.vue'
import { useAuth } from '@/composables/useAuth'

const props = defineProps({
    attention: { type: Array, default: () => [] },
    week: { type: Object, default: null },
    agenda: { type: Array, default: () => [] },
    tasks: { type: Array, default: () => [] },
    finance: { type: Object, default: null },
    inventory: { type: Object, default: null },
    members: { type: Object, default: null },
    today: { type: String, default: '' },
})

// "Tu semana": rejilla de 7 días con los eventos de cada uno.
const weekDays = computed(() => {
    const nombres = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom']
    const from = props.week ? new Date(`${props.week.from}T00:00:00`) : null
    return nombres.map((label, i) => {
        const d = from ? new Date(from) : null
        if (d) d.setDate(d.getDate() + i)
        return {
            label,
            iso: i + 1,
            dayNum: d ? d.getDate() : null,
            isToday: props.week?.today_weekday === i + 1,
            events: (props.week?.events ?? []).filter((e) => e.weekday === i + 1),
        }
    })
})
const weekHasContent = computed(() => (props.week?.events?.length ?? 0) > 0 || (props.week?.tasks_due ?? 0) > 0)

const { user, can } = useAuth()

// ─── Saludo según la hora, para que el panel se sienta vivo ───
const saludo = computed(() => {
    const h = new Date().getHours()
    if (h < 6) return 'Buenas noches'
    if (h < 13) return 'Buenos días'
    if (h < 21) return 'Buenas tardes'
    return 'Buenas noches'
})

// Nombre para el saludo: usamos el primero, salvo que sea una abreviatura
// ("Resp. Lobatos"), en cuyo caso mostramos el nombre completo.
const nombreCorto = computed(() => {
    const completo = (user.value?.name ?? '').trim()
    const primero = completo.split(' ')[0] ?? ''
    return primero.endsWith('.') || primero.length < 3 ? completo : primero
})

// ─── Avisos: primero los que tienen algo pendiente, por gravedad ───
const pendientes = computed(() =>
    [...props.attention]
        .filter((a) => a.value > 0)
        .sort((a, b) => (a.severity === b.severity ? b.value - a.value : a.severity === 'alta' ? -1 : 1)),
)

const resueltos = computed(() => props.attention.filter((a) => a.value === 0))

const estiloAviso = (severidad) =>
    severidad === 'alta'
        ? {
            marco: 'border-rose-200 bg-rose-50/70 hover:border-rose-300',
            cifra: 'text-rose-700',
            punto: 'bg-rose-500',
            etiqueta: 'text-rose-900',
        }
        : {
            marco: 'border-amber-200 bg-amber-50/70 hover:border-amber-300',
            cifra: 'text-amber-700',
            punto: 'bg-amber-500',
            etiqueta: 'text-amber-900',
        }

// ─── Agenda: "Hoy", "Mañana" o día de la semana ───
const formatoDia = new Intl.DateTimeFormat('es-ES', { weekday: 'short', day: 'numeric', month: 'short' })

const diaRelativo = (iso) => {
    if (!iso) return { texto: '', destacado: false }

    const fecha = new Date(iso)
    const hoy = new Date()
    const dias = Math.round((new Date(fecha.toDateString()) - new Date(hoy.toDateString())) / 86400000)

    if (dias === 0) return { texto: 'Hoy', destacado: true }
    if (dias === 1) return { texto: 'Mañana', destacado: true }
    if (dias < 7) return { texto: formatoDia.format(fecha), destacado: false }
    return { texto: formatoDia.format(fecha), destacado: false }
}

// Colores oficiales de rama (idénticos a tailwind.config.js). Se aplican como
// estilo porque Tailwind no puede generar clases construidas en tiempo de ejecución.
const colorRama = {
    castor: '#f97316',
    lobato: '#eab308',
    ranger: '#1e3a8a',
    pionero: '#e11d48',
    ruta: '#16a34a',
    responsable: '#0284c7',
}

const colorEvento = {
    reunion: 'bg-sky-100 text-sky-800',
    salida: 'bg-emerald-100 text-emerald-800',
    acampada: 'bg-amber-100 text-amber-800',
    campamento: 'bg-brand-100 text-brand-800',
    consejo: 'bg-violet-100 text-violet-800',
}

// ─── Accesos directos a las tareas del día a día ───
const accesos = computed(() =>
    [
        { label: 'Pasar lista', icon: 'check', href: ruta('attendance.index'), permiso: 'attendance.manage' },
        { label: 'Nuevo evento', icon: 'calendarPlus', href: ruta('events.create'), permiso: 'events.manage' },
        { label: 'Nuevo miembro', icon: 'userPlus', href: ruta('members.create'), permiso: 'members.manage' },
        { label: 'Nuevo cobro', icon: 'euro', href: ruta('charges.index'), permiso: 'charges.manage' },
        { label: 'Subir documento', icon: 'upload', href: ruta('documents.index'), permiso: 'documents.manage' },
        { label: 'Nueva actividad', icon: 'sparkles', href: ruta('activities.create'), permiso: 'activities.manage' },
    ].filter((a) => a.href && can(a.permiso)),
)

function ruta(nombre, ...args) {
    try {
        return route().has(nombre) ? route(nombre, ...args) : null
    } catch (e) {
        return null
    }
}

const euros = (n) =>
    new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 }).format(n ?? 0)
</script>

<template>
    <Head title="Inicio" />

    <AppLayout>
        <div class="space-y-6">
            <!-- ═══ Cabecera ═══ -->
            <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500">{{ today }}</p>
                    <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
                        {{ saludo }}<span v-if="nombreCorto">, {{ nombreCorto }}</span>
                    </h1>
                </div>

                <!-- Resumen en una línea: lo que hay que mirar hoy -->
                <p
                    v-if="pendientes.length"
                    class="inline-flex items-center gap-2 self-start rounded-full border border-amber-200 bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-900 sm:self-auto"
                >
                    <span class="relative flex h-2 w-2">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-amber-500 opacity-60"></span>
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-amber-500"></span>
                    </span>
                    {{ pendientes.length }} {{ pendientes.length === 1 ? 'asunto requiere' : 'asuntos requieren' }} tu atención
                </p>
                <p
                    v-else
                    class="inline-flex items-center gap-2 self-start rounded-full border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-800 sm:self-auto"
                >
                    <DashIcon name="check" class="h-4 w-4" />
                    Todo al día
                </p>
            </header>

            <!-- ═══ Accesos directos ═══ -->
            <section v-if="accesos.length" aria-label="Acciones rápidas">
                <ul class="grid grid-cols-2 gap-2.5 sm:grid-cols-3 lg:grid-cols-6">
                    <li v-for="accion in accesos" :key="accion.label">
                        <Link
                            :href="accion.href"
                            class="group flex h-full flex-col items-start gap-2 rounded-xl border border-slate-200 bg-white p-3 shadow-2xs transition-all duration-200 hover:-translate-y-0.5 hover:border-brand-200 hover:shadow-md focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-500"
                        >
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-600 transition-colors duration-200 group-hover:bg-brand-50 group-hover:text-brand-700">
                                <DashIcon :name="accion.icon" class="h-4 w-4" />
                            </span>
                            <span class="text-sm font-semibold leading-tight text-slate-800">{{ accion.label }}</span>
                        </Link>
                    </li>
                </ul>
            </section>

            <!-- ═══ Requiere atención ═══ -->
            <section aria-labelledby="titulo-atencion">
                <div class="flex items-center justify-between">
                    <h2 id="titulo-atencion" class="text-sm font-bold uppercase tracking-wide text-slate-500">
                        Requiere atención
                    </h2>
                    <span v-if="resueltos.length" class="text-xs font-medium text-slate-400">
                        {{ resueltos.length }} {{ resueltos.length === 1 ? 'control en verde' : 'controles en verde' }}
                    </span>
                </div>

                <ul v-if="pendientes.length" class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <li v-for="aviso in pendientes" :key="aviso.key">
                        <Link
                            :href="aviso.href"
                            class="group flex h-full items-start gap-4 rounded-2xl border p-4 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-500"
                            :class="estiloAviso(aviso.severity).marco"
                        >
                            <span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full" :class="estiloAviso(aviso.severity).punto"></span>
                            <span class="min-w-0 flex-1">
                                <span class="flex items-baseline gap-2">
                                    <span class="text-3xl font-extrabold leading-none tracking-tight" :class="estiloAviso(aviso.severity).cifra">
                                        {{ aviso.value }}
                                    </span>
                                    <span class="text-sm font-bold" :class="estiloAviso(aviso.severity).etiqueta">{{ aviso.label }}</span>
                                </span>
                                <span class="mt-1.5 block truncate text-xs font-medium text-slate-600">{{ aviso.hint }}</span>
                                <span class="mt-2 inline-flex items-center gap-1 text-xs font-bold text-slate-700 transition-colors group-hover:text-brand-700">
                                    {{ aviso.cta }}
                                    <DashIcon name="arrow" class="h-3 w-3 transition-transform duration-200 group-hover:translate-x-0.5" />
                                </span>
                            </span>
                        </Link>
                    </li>
                </ul>

                <!-- Todo en verde -->
                <div
                    v-else
                    class="mt-3 flex items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50/70 p-5"
                >
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-emerald-500 text-white">
                        <DashIcon name="check" class="h-5 w-5" />
                    </span>
                    <div>
                        <p class="text-sm font-bold text-emerald-900">No hay nada pendiente</p>
                        <p class="text-xs font-medium text-emerald-700">
                            Certificados, autorizaciones, cuotas y material están al corriente.
                        </p>
                    </div>
                </div>

                <!-- Controles en verde, plegados para no robar atención -->
                <ul v-if="pendientes.length && resueltos.length" class="mt-3 flex flex-wrap gap-2">
                    <li v-for="ok in resueltos" :key="ok.key">
                        <Link
                            :href="ok.href"
                            class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-500 transition-colors hover:border-emerald-200 hover:text-emerald-700"
                        >
                            <DashIcon name="check" class="h-3 w-3 text-emerald-500" />
                            {{ ok.label }}
                        </Link>
                    </li>
                </ul>
            </section>

            <!-- ═══ Tu semana ═══ -->
            <section v-if="week && weekHasContent" aria-labelledby="titulo-semana">
                <div class="flex items-baseline justify-between">
                    <h2 id="titulo-semana" class="text-sm font-bold uppercase tracking-wide text-slate-500">Tu semana</h2>
                    <span class="text-xs font-medium text-slate-400">
                        {{ week.range_label }}<template v-if="week.tasks_due"> · {{ week.tasks_due }} {{ week.tasks_due === 1 ? 'tarea vence' : 'tareas vencen' }} esta semana</template>
                    </span>
                </div>
                <div class="mt-3 grid grid-cols-7 gap-1.5 overflow-hidden rounded-2xl border border-slate-200 bg-white p-2 shadow-2xs">
                    <div v-for="d in weekDays" :key="d.iso"
                        class="min-h-[76px] rounded-xl p-1.5 text-center"
                        :class="d.isToday ? 'bg-brand-50 ring-1 ring-brand-200' : 'bg-slate-50/60'">
                        <p class="text-[11px] font-bold uppercase" :class="d.isToday ? 'text-brand-700' : 'text-slate-400'">
                            {{ d.label }}<span v-if="d.dayNum" class="ml-0.5 font-semibold text-slate-500">{{ d.dayNum }}</span>
                        </p>
                        <ul class="mt-1 space-y-1">
                            <li v-for="e in d.events" :key="e.id">
                                <Link :href="e.href"
                                    class="block truncate rounded-md bg-white px-1.5 py-1 text-[11px] font-semibold text-slate-700 shadow-2xs hover:bg-brand-50 hover:text-brand-700"
                                    :title="`${e.time} · ${e.title} (${e.type})`">
                                    {{ e.time }} {{ e.title }}
                                </Link>
                            </li>
                        </ul>
                    </div>
                </div>
            </section>

            <!-- ═══ Mis tareas del kraal (reparto de preparación de salidas) ═══ -->
            <section v-if="tasks.length" aria-labelledby="titulo-tareas">
                <h2 id="titulo-tareas" class="text-sm font-bold uppercase tracking-wide text-slate-500">Mis tareas</h2>
                <ul class="mt-3 divide-y divide-slate-100 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xs">
                    <li v-for="t in tasks" :key="t.id">
                        <Link :href="t.href || '#'" class="flex items-center justify-between gap-3 px-4 py-3 transition-colors hover:bg-slate-50">
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-semibold text-slate-800">{{ t.label }}</span>
                                <span class="block truncate text-xs text-slate-500">{{ t.event_title }}</span>
                            </span>
                            <span v-if="t.due_at" class="shrink-0 text-xs font-semibold"
                                :class="new Date(t.due_at) < new Date() ? 'text-rose-600' : 'text-slate-500'">
                                {{ new Date(t.due_at).toLocaleDateString('es-ES', { day: '2-digit', month: 'short' }) }}
                            </span>
                        </Link>
                    </li>
                </ul>
            </section>

            <!-- ═══ Agenda + dinero ═══ -->
            <div class="grid gap-5 lg:grid-cols-3">
                <!-- Agenda -->
                <section class="rounded-2xl border border-slate-200 bg-white shadow-2xs lg:col-span-2">
                    <header class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                        <h2 class="flex items-center gap-2 text-base font-bold text-slate-900">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-sky-50 text-sky-700">
                                <DashIcon name="calendar" class="h-4 w-4" />
                            </span>
                            Próximas actividades
                        </h2>
                        <Link
                            :href="ruta('events.index')"
                            class="group inline-flex items-center gap-1 text-xs font-bold text-slate-500 transition-colors hover:text-brand-700"
                        >
                            Ver calendario
                            <DashIcon name="arrow" class="h-3 w-3 transition-transform duration-200 group-hover:translate-x-0.5" />
                        </Link>
                    </header>

                    <ul v-if="agenda.length" class="divide-y divide-slate-100">
                        <li v-for="evento in agenda" :key="evento.id">
                            <Link
                                :href="evento.href"
                                class="flex items-center gap-4 px-5 py-3.5 transition-colors duration-150 hover:bg-slate-50"
                            >
                                <!-- Cuándo -->
                                <span class="w-20 shrink-0">
                                    <span
                                        class="block text-xs font-bold uppercase tracking-wide"
                                        :class="diaRelativo(evento.start_at).destacado ? 'text-brand-700' : 'text-slate-500'"
                                    >
                                        {{ diaRelativo(evento.start_at).texto }}
                                    </span>
                                    <span class="block text-sm font-semibold text-slate-700">{{ evento.time }}</span>
                                </span>

                                <!-- Qué -->
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-bold text-slate-900">{{ evento.title }}</span>
                                    <span class="mt-0.5 flex items-center gap-1.5 text-xs font-medium text-slate-500">
                                        <span
                                            class="rounded-md px-1.5 py-0.5 text-[11px] font-bold"
                                            :class="colorEvento[evento.type_key] ?? 'bg-slate-100 text-slate-700'"
                                        >
                                            {{ evento.type }}
                                        </span>
                                        <span class="truncate">{{ evento.location || 'Sin ubicación' }}</span>
                                    </span>
                                </span>

                                <DashIcon name="chevron" class="h-4 w-4 shrink-0 text-slate-300" />
                            </Link>
                        </li>
                    </ul>

                    <p v-else class="px-5 py-10 text-center text-sm font-medium text-slate-400">
                        No hay actividades programadas.
                        <Link v-if="ruta('events.create') && can('events.manage')" :href="ruta('events.create')" class="font-bold text-brand-700 hover:underline">
                            Crea la primera.
                        </Link>
                    </p>
                </section>

                <!-- Columna lateral -->
                <div class="space-y-5">
                    <!-- Tesorería -->
                    <section v-if="finance" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs">
                        <header class="flex items-center justify-between">
                            <h2 class="flex items-center gap-2 text-base font-bold text-slate-900">
                                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700">
                                    <DashIcon name="euro" class="h-4 w-4" />
                                </span>
                                Cobros del curso
                            </h2>
                            <Link :href="finance.href" class="text-xs font-bold text-slate-500 transition-colors hover:text-brand-700">Ver</Link>
                        </header>

                        <p class="mt-4 text-3xl font-extrabold tracking-tight text-slate-900">{{ euros(finance.pending_amount) }}</p>
                        <p class="text-xs font-semibold text-slate-500">
                            pendientes de cobro · {{ finance.pending_count }} recibos
                        </p>

                        <!-- Progreso del curso -->
                        <div class="mt-4">
                            <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                                <div
                                    class="h-full rounded-full bg-emerald-500 transition-[width] duration-700 ease-out"
                                    :style="{ width: `${finance.paid_ratio}%` }"
                                ></div>
                            </div>
                            <p class="mt-1.5 text-xs font-medium text-slate-500">
                                {{ finance.paid_ratio }}% cobrado · {{ euros(finance.paid_amount) }} ingresados
                            </p>
                        </div>

                        <ul v-if="finance.recent.length" class="mt-4 space-y-2.5 border-t border-slate-100 pt-4">
                            <li v-for="cobro in finance.recent" :key="cobro.id" class="flex items-center justify-between gap-3">
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-semibold text-slate-800">{{ cobro.member }}</span>
                                    <span class="block truncate text-xs text-slate-500">{{ cobro.charge }} · {{ cobro.amount }} €</span>
                                </span>
                                <BadgeEstado :label="cobro.status" :color="cobro.status_color" />
                            </li>
                        </ul>
                    </section>

                    <!-- Miembros por rama -->
                    <section v-if="members" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs">
                        <header class="flex items-center justify-between">
                            <h2 class="flex items-center gap-2 text-base font-bold text-slate-900">
                                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-violet-50 text-violet-700">
                                    <DashIcon name="users" class="h-4 w-4" />
                                </span>
                                Censo
                            </h2>
                            <Link :href="members.href" class="text-xs font-bold text-slate-500 transition-colors hover:text-brand-700">Ver</Link>
                        </header>

                        <p class="mt-3 text-3xl font-extrabold tracking-tight text-slate-900">{{ members.total }}</p>
                        <p class="text-xs font-semibold text-slate-500">personas en el grupo</p>

                        <ul class="mt-4 space-y-2">
                            <li v-for="rama in members.branches" :key="rama.key" class="flex items-center gap-3">
                                <span class="w-24 shrink-0 text-xs font-bold text-slate-600">{{ rama.label }}</span>
                                <span class="h-2 flex-1 overflow-hidden rounded-full bg-slate-100">
                                    <span
                                        class="block h-full rounded-full transition-[width] duration-700 ease-out"
                                        :style="{
                                            width: `${Math.max(6, Math.round((rama.count / members.total) * 100))}%`,
                                            backgroundColor: colorRama[rama.key] ?? '#94a3b8',
                                        }"
                                    ></span>
                                </span>
                                <span class="w-8 shrink-0 text-right text-xs font-bold tabular-nums text-slate-700">{{ rama.count }}</span>
                            </li>
                        </ul>
                    </section>

                    <!-- Inventario -->
                    <section v-if="inventory" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs">
                        <header class="flex items-center justify-between">
                            <h2 class="flex items-center gap-2 text-base font-bold text-slate-900">
                                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-amber-50 text-amber-700">
                                    <DashIcon name="box" class="h-4 w-4" />
                                </span>
                                Material
                            </h2>
                            <Link :href="inventory.href" class="text-xs font-bold text-slate-500 transition-colors hover:text-brand-700">Ver</Link>
                        </header>
                        <div class="mt-4 grid grid-cols-2 gap-3">
                            <div class="rounded-xl bg-slate-50 p-3">
                                <p class="text-2xl font-extrabold" :class="inventory.review_due > 0 ? 'text-amber-600' : 'text-slate-800'">
                                    {{ inventory.review_due }}
                                </p>
                                <p class="mt-0.5 text-xs font-semibold text-slate-500">Por revisar</p>
                            </div>
                            <div class="rounded-xl bg-slate-50 p-3">
                                <p class="text-2xl font-extrabold" :class="inventory.overdue_checkouts > 0 ? 'text-rose-600' : 'text-slate-800'">
                                    {{ inventory.overdue_checkouts }}
                                </p>
                                <p class="mt-0.5 text-xs font-semibold text-slate-500">Sin devolver</p>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
