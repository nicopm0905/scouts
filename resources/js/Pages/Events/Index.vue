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

const view = ref('list') // 'list' | 'month'
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
    router.post(route('events.ical.regenerate'), {}, {
        onSuccess: () => toast.success('Enlace regenerado.'),
    })
}

const today = new Date()
const cursor = ref({ year: today.getFullYear(), month: today.getMonth() })

const monthLabel = computed(() =>
    new Date(cursor.value.year, cursor.value.month, 1).toLocaleDateString('es-ES', { month: 'long', year: 'numeric' })
)

const eventsByDay = computed(() => {
    const map = {}
    for (const e of props.events) {
        const d = new Date(e.start_at)
        if (d.getFullYear() === cursor.value.year && d.getMonth() === cursor.value.month) {
            const key = d.getDate()
            map[key] = map[key] ?? []
            map[key].push(e)
        }
    }
    return map
})

const daysInMonth = computed(() => new Date(cursor.value.year, cursor.value.month + 1, 0).getDate())
const firstWeekday = computed(() => {
    const wd = new Date(cursor.value.year, cursor.value.month, 1).getDay()
    return wd === 0 ? 6 : wd - 1 // lunes=0
})

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

function typeColor(type) {
    const map = {
        reunion: 'blue',
        salida: 'orange',
        acampada: 'orange',
        campamento: 'red',
        consejo_grupo: 'gray',
        asamblea: 'gray',
        otro: 'gray',
    }
    return map[type] ?? 'gray'
}

function typeHex(type) {
    const map = {
        reunion: '#3b82f6',
        salida: '#f97316',
        acampada: '#f97316',
        campamento: '#ef4444',
        consejo_grupo: '#64748b',
        asamblea: '#64748b',
        otro: '#64748b',
    }
    return map[type] ?? '#64748b'
}

function formatDate(iso) {
    return new Date(iso).toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}
</script>

<template>
    <Head title="Calendario" />

    <AppLayout>
        <PageHeader title="Calendario de eventos" subtitle="Reuniones, salidas, acampadas y campamentos del grupo.">
            <template #actions>
                <button
                    type="button"
                    class="rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-600 hover:bg-slate-50"
                    @click="showIcal = !showIcal"
                >
                    📆 Suscribirme (iCal)
                </button>
                <Link
                    v-if="can('events.manage')"
                    :href="route('events.create')"
                    class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700"
                >
                    + Nuevo evento
                </Link>
            </template>
        </PageHeader>

        <div v-if="showIcal" class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 p-4">
            <p class="text-sm font-medium text-emerald-900">Suscripción al calendario</p>
            <p class="mt-1 text-xs text-emerald-800">
                Añade este enlace a Google Calendar (Otros calendarios → Desde URL) para ver los eventos
                automáticamente, en modo solo lectura.
            </p>
            <div class="mt-2 flex flex-col gap-2 sm:flex-row sm:items-center">
                <input readonly :value="icalUrl" class="w-full rounded-md border-slate-300 text-xs text-slate-600 shadow-sm sm:max-w-md" />
                <div class="flex gap-2">
                    <button type="button" class="rounded-md border border-slate-300 px-3 py-1.5 text-xs hover:bg-white" @click="copyIcal">
                        Copiar
                    </button>
                    <button type="button" class="rounded-md border border-slate-300 px-3 py-1.5 text-xs hover:bg-white" @click="regenerate">
                        Regenerar
                    </button>
                </div>
            </div>
        </div>

        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex gap-2">
                <button
                    type="button"
                    class="rounded-md px-3 py-1.5 text-sm font-medium"
                    :class="view === 'list' ? 'bg-emerald-600 text-white' : 'bg-white text-slate-600 border border-slate-300'"
                    @click="view = 'list'"
                >
                    Lista
                </button>
                <button
                    type="button"
                    class="rounded-md px-3 py-1.5 text-sm font-medium"
                    :class="view === 'month' ? 'bg-emerald-600 text-white' : 'bg-white text-slate-600 border border-slate-300'"
                    @click="view = 'month'"
                >
                    Mes
                </button>
            </div>

            <select v-model="branch" @change="applyBranch" class="rounded-md border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                <option value="">Todas las ramas</option>
                <option v-for="b in branchOptions" :key="b.value" :value="b.value">{{ b.label }}</option>
            </select>
        </div>

        <!-- Vista lista -->
        <div v-if="view === 'list'" class="space-y-2">
            <Link
                v-for="e in events"
                :key="e.id"
                :href="route('events.show', e.id)"
                class="flex flex-col gap-1 rounded-lg border border-slate-200 bg-white p-4 hover:border-emerald-300 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <div class="flex items-center gap-2">
                        <span class="font-semibold text-slate-800">{{ e.title }}</span>
                        <BadgeEstado :label="e.type_label" :color="typeColor(e.type)" />
                    </div>
                    <p class="text-sm text-slate-500">
                        {{ formatDate(e.start_at) }}
                        <span v-if="e.location"> · {{ e.location }}</span>
                    </p>
                </div>
                <div class="flex flex-wrap gap-1">
                    <BadgeEstado v-for="b in e.branches" :key="b" :label="b" color="gray" />
                </div>
            </Link>

            <p v-if="events.length === 0" class="rounded-lg border border-dashed border-slate-300 bg-white p-8 text-center text-slate-400">
                No hay eventos que coincidan con el filtro.
            </p>
        </div>

        <!-- Vista mensual -->
        <div v-else class="rounded-lg border border-slate-200 bg-white p-4">
            <div class="mb-3 flex items-center justify-between">
                <button type="button" class="rounded-md px-2 py-1 text-slate-500 hover:bg-slate-100" @click="prevMonth">‹</button>
                <span class="font-semibold capitalize text-slate-700">{{ monthLabel }}</span>
                <button type="button" class="rounded-md px-2 py-1 text-slate-500 hover:bg-slate-100" @click="nextMonth">›</button>
            </div>

            <div class="grid grid-cols-7 gap-1 text-center text-xs font-medium text-slate-400">
                <span v-for="d in ['L', 'M', 'X', 'J', 'V', 'S', 'D']" :key="d">{{ d }}</span>
            </div>

            <div class="mt-1 grid grid-cols-7 gap-1">
                <div v-for="n in firstWeekday" :key="'empty-' + n" />
                <div
                    v-for="day in daysInMonth"
                    :key="day"
                    class="min-h-[70px] rounded-md border border-slate-100 p-1 text-left"
                >
                    <div class="text-xs text-slate-400">{{ day }}</div>
                    <Link
                        v-for="e in (eventsByDay[day] ?? []).slice(0, 3)"
                        :key="e.id"
                        :href="route('events.show', e.id)"
                        class="mt-0.5 block truncate rounded px-1 text-[10px] text-white"
                        :style="{ backgroundColor: typeHex(e.type) }"
                        :title="e.title"
                    >
                        {{ e.title }}
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
