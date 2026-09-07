<script setup>
import { computed, ref, watch } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import BadgeEstado from '@/Components/Shared/BadgeEstado.vue'
import Modal from '@/Components/Shared/Modal.vue'
import FormField from '@/Components/Shared/FormField.vue'
import ConfirmButton from '@/Components/Shared/ConfirmButton.vue'
import { useToast } from '@/composables/useToast'

defineOptions({ layout: AppLayout })

const props = defineProps({
    plan: { type: Object, required: true },
    canManage: { type: Boolean, default: false },
    catalog: { type: Object, required: true },
})

const toast = useToast()
const showObjectiveModal = ref(false)
const editingObjective = ref(null)

const terms = [1, 2, 3]

const objectivesByTerm = computed(() => {
    const map = { 1: [], 2: [], 3: [], sin_trimestre: [] }
    for (const o of props.plan.objectives) {
        const key = o.term ?? 'sin_trimestre'
        map[key] = map[key] ?? []
        map[key].push(o)
    }
    return map
})

const statusOptions = [
    { value: 'pendiente', label: 'Pendiente' },
    { value: 'en_curso', label: 'En curso' },
    { value: 'logrado', label: 'Logrado' },
]

const form = useForm({
    scope: '',
    line: '',
    content: '',
    current_situation: '',
    goal_verb: '',
    goal_complement: '',
    description: '',
    evaluation: '',
    term: '',
    status: 'pendiente',
})

// Catálogo oficial MSC: Ámbito → Línea → Contenido (llega del servidor).
const scopeOptions = props.catalog.scopes
const goalVerbOptions = props.catalog.goal_verbs.map((v) => ({ value: v, label: v }))

const lineOptions = computed(() => {
    const lines = props.catalog.lines[form.scope]
    return lines ? Object.keys(lines).map((l) => ({ value: l, label: l })) : []
})

const contentOptions = computed(() => {
    const contents = props.catalog.lines[form.scope]?.[form.line]
    return (contents ?? []).map((c) => ({ value: c, label: c }))
})

// Al cambiar de ámbito o de línea, lo que colgaba de ellos deja de ser válido.
// No se limpia al abrir el formulario de edición, que rellena los tres a la vez.
watch(() => form.scope, () => {
    if (!lineOptions.value.some((o) => o.value === form.line)) {
        form.line = ''
        form.content = ''
    }
})

watch(() => form.line, () => {
    if (!contentOptions.value.some((o) => o.value === form.content)) {
        form.content = ''
    }
})


function openCreate() {
    editingObjective.value = null
    form.reset()
    form.status = 'pendiente'
    showObjectiveModal.value = true
}

function openEdit(objective) {
    editingObjective.value = objective
    form.scope = objective.scope ?? ''
    form.line = objective.line ?? ''
    form.content = objective.content ?? ''
    form.current_situation = objective.current_situation ?? ''
    form.goal_verb = objective.goal_verb ?? ''
    form.goal_complement = objective.goal_complement ?? ''
    form.description = objective.description ?? ''
    form.evaluation = objective.evaluation ?? ''
    form.term = objective.term ?? ''
    form.status = objective.status
    showObjectiveModal.value = true
}

function submit() {
    const onSuccess = () => {
        showObjectiveModal.value = false
        toast.success('Guardado correctamente.')
    }

    if (editingObjective.value) {
        form.put(route('branch-plans.objectives.update', editingObjective.value.id), { onSuccess })
    } else {
        form.post(route('branch-plans.objectives.store', props.plan.id), { onSuccess })
    }
}

function destroyObjective(objective) {
    form.delete(route('branch-plans.objectives.destroy', objective.id), {
        onSuccess: () => toast.success('Objetivo eliminado.'),
    })
}

function badgeColor(status) {
    return { pendiente: 'gray', en_curso: 'blue', logrado: 'green' }[status] ?? 'gray'
}

// --- Programar el trimestre: reuniones semanales en bloque ---
const showScheduleModal = ref(false)
const weekdayOptions = [
    { value: 1, label: 'Lunes' }, { value: 2, label: 'Martes' }, { value: 3, label: 'Miércoles' },
    { value: 4, label: 'Jueves' }, { value: 5, label: 'Viernes' }, { value: 6, label: 'Sábado' }, { value: 7, label: 'Domingo' },
]
const scheduleForm = useForm({
    weekday: 6,
    start_date: '',
    end_date: '',
    time: '17:30',
    duration_minutes: 90,
    location: '',
})

const previewDates = computed(() => {
    const { start_date, end_date, weekday } = scheduleForm
    if (!start_date || !end_date) return []
    const cur = new Date(`${start_date}T00:00:00`)
    const end = new Date(`${end_date}T23:59:59`)
    const out = []
    let guard = 0
    while (cur <= end && guard++ < 400) {
        const iso = cur.getDay() === 0 ? 7 : cur.getDay()
        if (iso === Number(weekday)) {
            out.push(cur.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit' }))
            cur.setDate(cur.getDate() + 7)
        } else {
            cur.setDate(cur.getDate() + 1)
        }
    }
    return out
})

function submitSchedule() {
    scheduleForm.post(route('branch-plans.meetings.store', props.plan.id), {
        onSuccess: () => { showScheduleModal.value = false },
    })
}

function termStats(term) {
    const list = objectivesByTerm.value[term] ?? []
    const done = list.filter((o) => o.status === 'logrado').length
    return { done, total: list.length, pct: list.length ? Math.round((done / list.length) * 100) : 0 }
}
</script>

<template>
    <Head :title="`Plan ${plan.branch_label} ${plan.school_year}`" />

    <PageHeader :title="`Plan de ${plan.branch_label} — ${plan.school_year}`" :subtitle="plan.description" icon="target">
        <template #actions>
            <button
                v-if="canManage"
                class="rounded-md border border-brand-200 px-4 py-2 text-sm font-semibold text-brand-700 hover:bg-brand-50"
                @click="showScheduleModal = true"
            >
                Programar reuniones
            </button>
            <button
                v-if="canManage"
                class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700"
                @click="openCreate"
            >
                + Añadir objetivo
            </button>
        </template>
    </PageHeader>

    <div class="mb-6 card-pad">
        <p class="section-title mb-2">Progreso general · base de la memoria anual</p>
        <div class="flex items-center gap-3">
            <div class="h-3 w-full max-w-md overflow-hidden rounded-full bg-ink-100">
                <div class="h-full rounded-full bg-emerald-500 transition-all" :style="{ width: plan.completion_percentage + '%' }" />
            </div>
            <span class="text-lg font-bold text-emerald-600">{{ plan.completion_percentage }}%</span>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div v-for="term in [...terms, 'sin_trimestre']" :key="term" class="card p-4">
            <div class="mb-3">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-ink-800">{{ term === 'sin_trimestre' ? 'Sin trimestre' : `Trimestre ${term}` }}</h3>
                    <span class="text-xs text-ink-400">{{ termStats(term).done }}/{{ termStats(term).total }}</span>
                </div>
                <a
                    v-if="term !== 'sin_trimestre'"
                    :href="route('branch-plans.pdf.term', [plan.id, term])"
                    target="_blank"
                    class="mt-1 inline-block text-xs font-semibold text-brand-700 hover:underline"
                >
                    Hoja del trimestre (PDF)
                </a>
                <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-ink-100">
                    <div class="h-full rounded-full bg-emerald-500" :style="{ width: termStats(term).pct + '%' }" />
                </div>
            </div>
            <ul class="space-y-2">
                <li
                    v-for="o in objectivesByTerm[term]"
                    :key="o.id"
                    class="rounded-md border border-ink-100 p-2 text-sm"
                >
                    <div class="flex flex-col items-start gap-1.5">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span
                                v-if="o.scope_label"
                                :class="o.scope_classes"
                                class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider"
                            >
                                ÁMBITO {{ o.scope_label }}
                            </span>
                            <span v-if="o.line" class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide">{{ o.line }}</span>
                        </div>
                        <span v-if="o.content" class="text-[11px] text-slate-500">{{ o.content }}</span>
                        <span class="text-slate-800 font-bold mt-1 text-[13px] leading-snug">{{ o.description }}</span>
                        <p v-if="o.current_situation" class="text-[11px] text-slate-500 italic">
                            Cómo estamos: {{ o.current_situation }}
                        </p>
                        <ul v-if="o.activities.length" class="mt-1 space-y-0.5">
                            <li v-for="a in o.activities" :key="a.id" class="text-[11px] text-slate-600">
                                <span class="font-semibold">{{ a.type_label || 'Actividad' }}:</span> {{ a.title }}
                                <span v-if="a.owner" class="text-slate-400">· {{ a.owner }}</span>
                                <span v-if="a.date" class="text-slate-400">· {{ a.date }}</span>
                            </li>
                        </ul>
                        <p v-if="o.evaluation" class="text-[11px] text-slate-500">
                            Cómo ha salido: {{ o.evaluation }}
                        </p>
                    </div>
                    <div class="mt-3 flex items-center justify-between border-t border-ink-50 pt-2">
                        <BadgeEstado :label="o.status_label" :color="badgeColor(o.status)" />
                        <div v-if="canManage" class="flex gap-3">
                            <button class="text-xs font-semibold text-brand-700 hover:underline" @click="openEdit(o)">Editar</button>
                            <ConfirmButton
                                message="¿Eliminar este objetivo?"
                                confirm-label="Eliminar"
                                @confirm="destroyObjective(o)"
                            >
                                <span class="text-xs font-semibold text-red-600 hover:underline">Eliminar</span>
                            </ConfirmButton>
                        </div>
                    </div>
                </li>
                <li v-if="objectivesByTerm[term].length === 0" class="text-xs text-ink-400">Sin objetivos.</li>
            </ul>
        </div>
    </div>

    <Modal :show="showObjectiveModal" :title="editingObjective ? 'Editar objetivo' : 'Nuevo objetivo'" @close="showObjectiveModal = false">
        <form class="space-y-4" @submit.prevent="submit">
            <div class="grid gap-4 sm:grid-cols-3">
                <FormField
                    v-model="form.scope"
                    type="select"
                    label="Ámbito"
                    :options="[{ value: '', label: 'Elige uno...' }, ...scopeOptions]"
                    :error="form.errors.scope"
                />
                <FormField
                    v-model="form.line"
                    type="select"
                    label="Línea"
                    :options="[{ value: '', label: 'Elige una...' }, ...lineOptions]"
                    :error="form.errors.line"
                />
                <FormField
                    v-model="form.content"
                    type="select"
                    label="Contenido"
                    :options="[{ value: '', label: 'Elige uno...' }, ...contentOptions]"
                    :error="form.errors.content"
                />
            </div>

            <FormField
                v-model="form.current_situation"
                type="textarea"
                label="¿Cómo estamos?"
                placeholder="El punto de partida de la unidad en este contenido."
                :error="form.errors.current_situation"
            />

            <div class="grid gap-4 sm:grid-cols-3">
                <FormField
                    v-model="form.goal_verb"
                    type="select"
                    label="¿Qué queremos conseguir?"
                    :options="[{ value: '', label: 'Elige un verbo...' }, ...goalVerbOptions]"
                    :error="form.errors.goal_verb"
                />
                <div class="sm:col-span-2">
                    <FormField
                        v-model="form.goal_complement"
                        type="text"
                        label="Complemento"
                        placeholder="La confianza entre los miembros de la unidad"
                        :error="form.errors.goal_complement"
                    />
                </div>
            </div>

            <FormField
                v-model="form.description"
                type="textarea"
                label="Objetivo (se compone solo con el verbo y el complemento)"
                :error="form.errors.description"
            />

            <div class="grid grid-cols-2 gap-4">
                <FormField v-model="form.term" type="select" label="Trimestre" :options="[{ value: '', label: 'Sin trimestre' }, { value: 1, label: '1' }, { value: 2, label: '2' }, { value: 3, label: '3' }]" :error="form.errors.term" />
                <FormField v-model="form.status" type="select" label="Estado" :options="statusOptions" :error="form.errors.status" />
            </div>

            <FormField
                v-model="form.evaluation"
                type="textarea"
                label="¿Cómo ha salido? (al cerrar el trimestre)"
                :error="form.errors.evaluation"
            />
        </form>
        <template #footer>
            <button class="rounded-md px-4 py-2 text-sm font-medium text-ink-600 hover:bg-ink-100" @click="showObjectiveModal = false">
                Cancelar
            </button>
            <button class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700" @click="submit">
                Guardar
            </button>
        </template>
    </Modal>

    <Modal :show="showScheduleModal" title="Programar reuniones del trimestre" @close="showScheduleModal = false">
        <form class="space-y-4" @submit.prevent="submitSchedule">
            <p class="text-sm text-ink-500">
                Crea de golpe las reuniones semanales de {{ plan.branch_label }} en un tramo de fechas.
                Luego puedes editar cada una y añadirle actividades.
            </p>
            <div class="grid grid-cols-2 gap-4">
                <FormField v-model="scheduleForm.start_date" type="date" label="Desde" :error="scheduleForm.errors.start_date" required />
                <FormField v-model="scheduleForm.end_date" type="date" label="Hasta" :error="scheduleForm.errors.end_date" required />
            </div>
            <div class="grid grid-cols-3 gap-4">
                <FormField v-model="scheduleForm.weekday" type="select" label="Día" :options="weekdayOptions" :error="scheduleForm.errors.weekday" />
                <FormField v-model="scheduleForm.time" type="time" label="Hora" :error="scheduleForm.errors.time" />
                <FormField v-model="scheduleForm.duration_minutes" type="number" label="Duración (min)" :error="scheduleForm.errors.duration_minutes" />
            </div>
            <FormField v-model="scheduleForm.location" type="text" label="Lugar (opcional)" :error="scheduleForm.errors.location" />
            <div class="rounded-lg border border-ink-100 bg-slate-50 p-3 text-sm">
                <p v-if="!previewDates.length" class="text-ink-400">Elige un tramo de fechas para ver las reuniones que se crearán.</p>
                <template v-else>
                    <p class="font-semibold text-ink-700">Se crearán {{ previewDates.length }} reuniones:</p>
                    <p class="mt-1 text-ink-500">{{ previewDates.join(' · ') }}</p>
                </template>
            </div>
        </form>
        <template #footer>
            <button class="rounded-md px-4 py-2 text-sm font-medium text-ink-600 hover:bg-ink-100" @click="showScheduleModal = false">
                Cancelar
            </button>
            <button
                class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700 disabled:opacity-50"
                :disabled="scheduleForm.processing || !previewDates.length"
                @click="submitSchedule"
            >
                Crear {{ previewDates.length || '' }} reuniones
            </button>
        </template>
    </Modal>
</template>
