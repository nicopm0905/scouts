<script setup>
import { computed, ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import BadgeEstado from '@/Components/Shared/BadgeEstado.vue'
import { useAuth } from '@/composables/useAuth'
import { useToast } from '@/composables/useToast'

const props = defineProps({
    events: { type: Array, required: true },
    filters: { type: Object, default: () => ({}) },
    branchOptions: { type: Array, required: true },
    typeOptions: { type: Array, required: true },
    icalUrl: { type: String, required: true },
})

const { can } = useAuth()
const toast = useToast()

const view = ref('month') // 'month' | 'list'
const branch = ref(props.filters.branch ?? '')
const showIcal = ref(false)

function applyBranch() {
    router.get(route('events.index'), branch.value ? { branch: branch.value } : {}, {
        preserveState: true,
        replace: true,
    })
}
function copyIcal() {
    navigator.clipboard?.writeText(props.icalUrl)
    toast.success('Enlace copiado al portapapeles.')
}
function regenerate() {
    router.post(route('events.ical.regenerate'), {}, { onSuccess: () => toast.success('Enlace regenerado.') })
}

// --- Paleta por tipo de evento ---
const TYPE_STYLE = {
    reunion: { hex: '#2563eb', badge: 'blue', label: 'Reunión' },
    salida: { hex: '#f59e0b', badge: 'orange', label: 'Salida' },
    acampada: { hex: '#ea580c', badge: 'orange', label: 'Acampada' },
    campamento: { hex: '#dc2626', badge: 'red', label: 'Campamento' },
    consejo_grupo: { hex: '#64748b', badge: 'gray', label: 'Consejo' },
    asamblea: { hex: '#64748b', badge: 'gray', label: 'Asamblea' },
    otro: { hex: '#94a3b8', badge: 'gray', label: 'Otro' },
}
const style = (t) => TYPE_STYLE[t] ?? TYPE_STYLE.otro

// --- Calendario ---
const today = new Date()
const todayKey = `${today.getFullYear()}-${today.getMonth()}-${today.getDate()}`
const cursor = ref({ year: today.getFullYear(), month: today.getMonth() })

const monthLabel = computed(() =>
    new Date(cursor.value.year, cursor.value.month, 1).toLocaleDateString('es-ES', { month: 'long', year: 'numeric' })
)

// Mapa "año-mes-día" -> eventos, ordenados por hora
const eventsByDay = computed(() => {
    const map = {}
    for (const e of props.events) {
        const d = new Date(e.start_at)
        const key = `${d.getFullYear()}-${d.getMonth()}-${d.getDate()}`
        ;(map[key] = map[key] ?? []).push(e)
    }
    for (const k in map) map[k].sort((a, b) => new Date(a.start_at) - new Date(b.start_at))
    return map
})

// Rejilla de 6 semanas (42 celdas) incluyendo días de meses colindantes
const weeks = computed(() => {
    const first = new Date(cursor.value.year, cursor.value.month, 1)
    const startOffset = (first.getDay() + 6) % 7 // lunes = 0
    const start = new Date(cursor.value.year, cursor.value.month, 1 - startOffset)

    const cells = []
    for (let i = 0; i < 42; i++) {
        const d = new Date(start)
        d.setDate(start.getDate() + i)
        const key = `${d.getFullYear()}-${d.getMonth()}-${d.getDate()}`
        cells.push({
            date: d,
            day: d.getDate(),
            inMonth: d.getMonth() === cursor.value.month,
            isToday: key === todayKey,
            events: eventsByDay.value[key] ?? [],
        })
    }
    const rows = []
    for (let i = 0; i < 42; i += 7) rows.push(cells.slice(i, i + 7))
    return rows
})

function goToday() {
    cursor.value = { year: today.getFullYear(), month: today.getMonth() }
}
function prevMonth() {
    cursor.value = cursor.value.month === 0
        ? { year: cursor.value.year - 1, month: 11 }
        : { year: cursor.value.year, month: cursor.value.month - 1 }
}
function nextMonth() {
    cursor.value = cursor.value.month === 11
        ? { year: cursor.value.year + 1, month: 0 }
        : { year: cursor.value.year, month: cursor.value.month + 1 }
}

const timeOf = (iso) => new Date(iso).toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' })
const formatDate = (iso) =>
    new Date(iso).toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })

// Tipos presentes (para la leyenda)
const presentTypes = computed(() => [...new Set(props.events.map((e) => e.type))])
</script>

<template>
    <Head title="Calendario" />

    <AppLayout>
        <PageHeader title="Calendario de eventos" subtitle="Reuniones, salidas, acampadas y campamentos del grupo.">
            <template #actions>
                <button type="button" class="btn-secondary btn-sm" @click="showIcal = !showIcal">📆 Suscribirme</button>
                <Link v-if="can('events.manage')" :href="route('events.create')" class="btn-primary btn-sm">
                    + Nuevo evento
                </Link>
            </template>
        </PageHeader>

        <div v-if="showIcal" class="mb-6 rounded-xl border border-brand-200 bg-brand-50 p-4">
            <p class="text-sm font-semibold text-brand-900">Suscripción al calendario</p>
            <p class="mt-1 text-xs text-brand-800">
                Añade este enlace a Google Calendar (Otros calendarios → Desde URL) para ver los eventos
                automáticamente, en modo solo lectura.
            </p>
            <div class="mt-2 flex flex-col gap-2 sm:flex-row sm:items-center">
                <input readonly :value="icalUrl" class="input text-xs sm:max-w-md" />
                <div class="flex gap-2">
                    <button type="button" class="btn-secondary btn-sm" @click="copyIcal">Copiar</button>
                    <button type="button" class="btn-secondary btn-sm" @click="regenerate">Regenerar</button>
                </div>
            </div>
        </div>

        <!-- Barra de control -->
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="inline-flex rounded-lg border border-ink-200 bg-white p-0.5">
                <button type="button" class="rounded-md px-3 py-1.5 text-sm font-medium transition"
                    :class="view === 'month' ? 'bg-brand-600 text-white' : 'text-ink-600 hover:bg-ink-100'"
                    @click="view = 'month'">Mes</button>
                <button type="button" class="rounded-md px-3 py-1.5 text-sm font-medium transition"
                    :class="view === 'list' ? 'bg-brand-600 text-white' : 'text-ink-600 hover:bg-ink-100'"
                    @click="view = 'list'">Lista</button>
            </div>

            <select v-model="branch" @change="applyBranch" class="input sm:w-auto">
                <option value="">Todas las ramas</option>
                <option v-for="b in branchOptions" :key="b.value" :value="b.value">{{ b.label }}</option>
            </select>
        </div>

        <!-- Vista mensual -->
        <div v-if="view === 'month'" class="card overflow-hidden">
            <!-- Cabecera de navegación -->
            <div class="flex items-center justify-between border-b border-ink-100 px-4 py-3">
                <div class="flex items-center gap-1">
                    <button type="button" class="rounded-lg p-1.5 text-ink-500 hover:bg-ink-100" @click="prevMonth" aria-label="Mes anterior">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
                    </button>
                    <button type="button" class="rounded-lg p-1.5 text-ink-500 hover:bg-ink-100" @click="nextMonth" aria-label="Mes siguiente">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                    </button>
                    <h2 class="ml-2 text-base font-bold capitalize text-ink-900">{{ monthLabel }}</h2>
                </div>
                <button type="button" class="btn-secondary btn-sm" @click="goToday">Hoy</button>
            </div>

            <!-- Días de la semana -->
            <div class="grid grid-cols-7 border-b border-ink-100 bg-ink-50 text-center text-xs font-semibold uppercase tracking-wide text-ink-400">
                <span v-for="d in ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom']" :key="d" class="py-2">{{ d }}</span>
            </div>

            <!-- Rejilla -->
            <div>
                <div v-for="(week, wi) in weeks" :key="wi" class="grid grid-cols-7 border-b border-ink-100 last:border-b-0">
                    <div
                        v-for="(cell, ci) in week"
                        :key="ci"
                        class="min-h-[92px] border-r border-ink-100 p-1.5 last:border-r-0 sm:min-h-[112px]"
                        :class="cell.inMonth ? 'bg-white' : 'bg-ink-50/60'"
                    >
                        <div class="mb-1 flex justify-end">
                            <span
                                class="flex h-6 w-6 items-center justify-center rounded-full text-xs font-medium"
                                :class="cell.isToday
                                    ? 'bg-brand-600 font-bold text-white'
                                    : cell.inMonth ? 'text-ink-600' : 'text-ink-300'"
                            >{{ cell.day }}</span>
                        </div>

                        <div class="space-y-1">
                            <Link
                                v-for="e in cell.events.slice(0, 3)"
                                :key="e.id"
                                :href="route('events.show', e.id)"
                                class="block truncate rounded px-1.5 py-0.5 text-[11px] font-medium text-white transition hover:opacity-90"
                                :style="{ backgroundColor: style(e.type).hex }"
                                :title="`${e.title} · ${timeOf(e.start_at)}`"
                            >
                                <span class="opacity-90">{{ timeOf(e.start_at) }}</span> {{ e.title }}
                            </Link>
                            <Link
                                v-if="cell.events.length > 3"
                                :href="route('events.show', cell.events[3].id)"
                                class="block px-1.5 text-[11px] font-medium text-ink-400 hover:text-ink-600"
                            >+{{ cell.events.length - 3 }} más</Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Leyenda -->
        <div v-if="view === 'month' && presentTypes.length" class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 px-1 text-xs text-ink-500">
            <span v-for="t in presentTypes" :key="t" class="inline-flex items-center gap-1.5">
                <span class="h-2.5 w-2.5 rounded-full" :style="{ backgroundColor: style(t).hex }" />
                {{ style(t).label }}
            </span>
        </div>

        <!-- Vista lista -->
        <div v-else class="space-y-2">
            <Link
                v-for="e in events"
                :key="e.id"
                :href="route('events.show', e.id)"
                class="flex flex-col gap-1 rounded-xl border border-ink-200 bg-white p-4 transition hover:border-brand-300 hover:shadow-card sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex items-start gap-3">
                    <span class="mt-0.5 h-9 w-1.5 shrink-0 rounded-full" :style="{ backgroundColor: style(e.type).hex }" />
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-semibold text-ink-800">{{ e.title }}</span>
                            <BadgeEstado :label="e.type_label" :color="style(e.type).badge" />
                        </div>
                        <p class="text-sm text-ink-500">
                            {{ formatDate(e.start_at) }}<span v-if="e.location"> · {{ e.location }}</span>
                        </p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-1">
                    <BadgeEstado v-for="b in e.branches" :key="b" :label="b" color="gray" />
                </div>
            </Link>

            <p v-if="events.length === 0" class="rounded-xl border border-dashed border-ink-300 bg-white p-8 text-center text-ink-400">
                No hay eventos que coincidan con el filtro.
            </p>
        </div>
    </AppLayout>
</template>
