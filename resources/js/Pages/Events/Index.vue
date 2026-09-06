<script setup>
import { computed, ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import BadgeEstado from '@/Components/Shared/BadgeEstado.vue'
import BadgeRama from '@/Components/Shared/BadgeRama.vue'
import WhatsAppCircularModal from '@/Components/Shared/WhatsAppCircularModal.vue'
import { useAuth } from '@/composables/useAuth'
import { useToast } from '@/composables/useToast'
import { FileDown, MessageCircle, CalendarPlus, Plus, ChevronLeft, ChevronRight, Tent, Calendar, MapPin, ExternalLink, FileSignature } from 'lucide-vue-next'

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
const showWhatsAppModal = ref(false)

const filteredIcalUrl = computed(() => {
    if (!branch.value) return props.icalUrl
    try {
        const url = new URL(props.icalUrl)
        url.searchParams.set('branch', branch.value)
        return url.toString()
    } catch (e) {
        return `${props.icalUrl}?branch=${branch.value}`
    }
})

function applyBranch() {
    router.get(route('events.index'), branch.value ? { branch: branch.value } : {}, {
        preserveState: true,
        replace: true,
    })
}
function copyIcal() {
    navigator.clipboard?.writeText(filteredIcalUrl.value)
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

const DAY_MS = 86400000
const dayStart = (d) => new Date(d.getFullYear(), d.getMonth(), d.getDate())

// Rejilla de 6 semanas. Cada semana calcula las "barras" de eventos (con carriles),
// de modo que los eventos de varios días se pintan como una barra continua.
const weeks = computed(() => {
    const first = new Date(cursor.value.year, cursor.value.month, 1)
    const startOffset = (first.getDay() + 6) % 7 // lunes = 0
    const gridStart = dayStart(new Date(cursor.value.year, cursor.value.month, 1 - startOffset))

    const rows = []
    for (let wk = 0; wk < 6; wk++) {
        const weekStart = new Date(gridStart)
        weekStart.setDate(gridStart.getDate() + wk * 7)

        const cells = []
        for (let i = 0; i < 7; i++) {
            const d = new Date(weekStart)
            d.setDate(weekStart.getDate() + i)
            const key = `${d.getFullYear()}-${d.getMonth()}-${d.getDate()}`
            cells.push({
                day: d.getDate(),
                inMonth: d.getMonth() === cursor.value.month,
                isToday: key === todayKey,
            })
        }

        const weekEndExcl = new Date(weekStart)
        weekEndExcl.setDate(weekStart.getDate() + 7)

        // Segmentos de eventos que intersectan esta semana.
        const bars = []
        for (const e of props.events) {
            const eStart = dayStart(new Date(e.start_at))
            const eEnd = dayStart(new Date(e.end_at ?? e.start_at))
            if (eEnd < weekStart || eStart >= weekEndExcl) continue

            const segStart = eStart < weekStart ? weekStart : eStart
            const lastDay = new Date(weekEndExcl.getTime() - DAY_MS)
            const segEnd = eEnd > lastDay ? lastDay : eEnd
            const startCol = Math.round((segStart - weekStart) / DAY_MS)
            const endCol = Math.round((segEnd - weekStart) / DAY_MS)

            bars.push({
                event: e,
                startCol,
                span: endCol - startCol + 1,
                continuesLeft: eStart < weekStart,
                continuesRight: eEnd > lastDay,
                multiDay: eEnd.getTime() !== eStart.getTime(),
                lane: 0,
            })
        }

        // Asignación de carriles (greedy) para que no se solapen.
        bars.sort((a, b) => a.startCol - b.startCol || b.span - a.span)
        const lanes = []
        for (const bar of bars) {
            let lane = 0
            for (;;) {
                const occ = lanes[lane] ?? (lanes[lane] = [])
                const end = bar.startCol + bar.span - 1
                const clash = occ.some((o) => !(bar.startCol > o.end || end < o.start))
                if (!clash) {
                    occ.push({ start: bar.startCol, end })
                    bar.lane = lane
                    break
                }
                lane++
            }
        }

        rows.push({ cells, bars, laneCount: Math.max(lanes.length, 2) })
    }
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

// Ocultar reuniones ordinarias en la vista lista para evitar ruido visual,
// mostrando únicamente salidas, acampadas, campamentos, asambleas, etc.
const listEvents = computed(() => props.events.filter((e) => e.type !== 'reunion'))
</script>

<template>
    <Head title="Calendario" />

    <AppLayout>
        <PageHeader title="Calendario de eventos" subtitle="Reuniones, salidas, acampadas y campamentos del grupo." icon="calendar">
            <template #actions>
                <a :href="route('events.pdf.calendar', branch ? { branch } : {})" target="_blank" class="btn-secondary btn-sm group">
                    <FileDown class="mr-1.5 h-4 w-4 text-slate-500 group-hover:text-slate-700 transition" />
                    Exportar PDF
                </a>
                <button type="button" class="btn-secondary btn-sm group" @click="showWhatsAppModal = true">
                    <MessageCircle class="h-4 w-4 text-emerald-600 transition group-hover:text-emerald-700" />
                    Circular WhatsApp
                </button>
                <button type="button" class="btn-secondary btn-sm group" @click="showIcal = !showIcal">
                    <CalendarPlus class="mr-1.5 h-4 w-4 text-slate-500 group-hover:text-slate-700 transition" />
                    Suscribirme
                </button>
                <Link v-if="can('events.manage')" :href="route('events.create')" class="btn-primary btn-sm group">
                    <Plus class="mr-1 h-4 w-4 text-white/90 group-hover:text-white transition" />
                    Nuevo evento
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
                <input readonly :value="filteredIcalUrl" class="input text-xs sm:max-w-md" />
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
                        <ChevronLeft class="h-5 w-5" />
                    </button>
                    <button type="button" class="rounded-lg p-1.5 text-ink-500 hover:bg-ink-100" @click="nextMonth" aria-label="Mes siguiente">
                        <ChevronRight class="h-5 w-5" />
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
                <div
                    v-for="(week, wi) in weeks"
                    :key="wi"
                    class="relative grid border-b border-ink-100 last:border-b-0"
                    :style="{
                        gridTemplateColumns: 'repeat(7, minmax(0, 1fr))',
                        gridTemplateRows: `28px repeat(${week.laneCount}, 22px) 6px`,
                    }"
                >
                    <!-- Fondos de celda (columna completa) -->
                    <div
                        v-for="(cell, ci) in week.cells"
                        :key="'bg-' + ci"
                        class="border-r border-ink-100 last:border-r-0"
                        :class="cell.inMonth ? 'bg-white' : 'bg-ink-50/60'"
                        :style="{ gridColumn: ci + 1, gridRow: '1 / -1' }"
                    />

                    <!-- Números de día -->
                    <div
                        v-for="(cell, ci) in week.cells"
                        :key="'num-' + ci"
                        class="z-10 flex justify-end p-1"
                        :style="{ gridColumn: ci + 1, gridRow: 1 }"
                    >
                        <span
                            class="flex h-6 w-6 items-center justify-center rounded-full text-xs font-medium"
                            :class="cell.isToday ? 'bg-brand-600 font-bold text-white' : cell.inMonth ? 'text-ink-600' : 'text-ink-300'"
                        >{{ cell.day }}</span>
                    </div>

                    <!-- Barras de eventos -->
                    <Link
                        v-for="bar in week.bars"
                        :key="bar.event.id + '-' + wi"
                        :href="route('events.show', bar.event.id)"
                        class="z-10 mx-0.5 flex items-center gap-1 overflow-hidden whitespace-nowrap px-1.5 text-[11px] font-medium text-white transition hover:opacity-90"
                        :class="[
                            bar.continuesLeft ? 'rounded-l-none' : 'rounded-l',
                            bar.continuesRight ? 'rounded-r-none' : 'rounded-r',
                        ]"
                        :style="{
                            gridColumn: `${bar.startCol + 1} / span ${bar.span}`,
                            gridRow: bar.lane + 2,
                            backgroundColor: style(bar.event.type).hex,
                        }"
                        :title="`${bar.event.title} · ${formatDate(bar.event.start_at)}`"
                    >
                        <span v-if="bar.continuesLeft">‹</span>
                        <span v-if="!bar.multiDay" class="opacity-90">{{ timeOf(bar.event.start_at) }}</span>
                        <span class="truncate">{{ bar.event.title }}</span>
                        <span v-if="bar.continuesRight" class="ml-auto">›</span>
                    </Link>
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

        <!-- Vista lista (Salidas, Acampadas, Campamentos y Actividades Especiales) -->
        <div v-else class="space-y-3">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 rounded-2xl bg-slate-50/80 border border-slate-200/80 px-4 py-2.5 text-xs font-semibold text-slate-600 shadow-sm">
                <span class="flex items-center gap-1.5 font-bold text-slate-800">
                    <Tent class="h-4 w-4 text-slate-500" />
                    <span>Salidas, Acampadas y Campamentos ({{ listEvents.length }})</span>
                </span>
                <span class="text-[11px] text-slate-500 font-normal">Las reuniones ordinarias de sábado se consultan en la vista Calendario</span>
            </div>

            <Link
                v-for="e in listEvents"
                :key="e.id"
                :href="route('events.show', e.id)"
                class="block rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm hover:border-emerald-500 hover:shadow-md transition space-y-3 cursor-pointer group"
            >
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-100 pb-3">
                    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                        <span class="hidden sm:block h-10 w-2 shrink-0 rounded-full" :style="{ backgroundColor: style(e.type).hex }" />
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-base font-black text-slate-900 group-hover:text-emerald-700 transition truncate" :title="e.title">{{ e.title }}</h3>
                                <span class="rounded-full px-2.5 py-0.5 text-xs font-bold text-white shadow-sm shrink-0" :style="{ backgroundColor: style(e.type).hex }">
                                    {{ style(e.type).label }}
                                </span>
                            </div>
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-1 text-xs font-medium text-slate-500">
                                <span class="flex items-center gap-1 whitespace-nowrap"><Calendar class="h-3.5 w-3.5" /> {{ formatDate(e.start_at) }}</span>
                                <span v-if="e.location" class="flex items-center gap-1 truncate max-w-[200px]" :title="e.location"><MapPin class="h-3.5 w-3.5" /> {{ e.location }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex shrink-0 items-center gap-2 mt-2 sm:mt-0">
                        <span class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white group-hover:bg-emerald-700 transition shadow-sm w-full sm:w-auto justify-center">
                            <ExternalLink class="h-3.5 w-3.5" /> Abrir Ficha del Evento
                        </span>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                    <div class="flex flex-wrap items-center gap-1.5">
                        <span class="font-bold text-slate-500 mr-1">Ramas Afectadas:</span>
                        <BadgeRama v-for="b in e.branches" :key="b" :rama="b" />
                    </div>

                    <div v-if="e.requires_enrollment" class="flex items-center gap-1.5 text-slate-500 font-medium">
                        <FileSignature class="h-4 w-4" /> Requiere inscripción oficial
                    </div>
                </div>
            </Link>

            <p v-if="listEvents.length === 0" class="rounded-2xl border border-dashed border-slate-300 bg-white p-12 text-center text-sm font-semibold text-slate-400">
                No hay salidas, acampadas ni campamentos registrados con el filtro actual.
            </p>
        </div>

        <WhatsAppCircularModal
            :show="showWhatsAppModal"
            :default-branch="branch || 'castor'"
            @close="showWhatsAppModal = false"
        />
    </AppLayout>
</template>
