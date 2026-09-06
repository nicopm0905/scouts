<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import { useToast } from '@/composables/useToast'

const props = defineProps({
    branches: { type: Array, default: () => [] },
    branch: { type: String, default: null },
    branchExplicit: { type: Boolean, default: false },
    date: { type: String, default: null },
    members: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({ sessions: 0, avg_present: null }) },
})

// Recordar la última rama usada por esta persona: si entras a "Asistencia" sin
// elegir rama, se salta directamente a la que pasaste lista la última vez.
const BRANCH_KEY = 'attendance:last-branch'
onMounted(() => {
    if (props.branchExplicit) return
    let last = null
    try { last = localStorage.getItem(BRANCH_KEY) } catch (e) { /* modo privado */ }
    if (last && last !== props.branch && props.branches.some((b) => b.value === last)) {
        changeFilters(last)
    }
})

const toast = useToast()
const date = ref(props.date)

const form = useForm({
    branch: props.branch,
    date: props.date,
    attendance: props.members.map((m) => ({ member_id: m.id, present: m.present, notes: m.notes ?? '' })),
})

watch(
    () => props.members,
    (newMembers) => {
        form.attendance = newMembers.map((m) => ({ member_id: m.id, present: m.present, notes: m.notes ?? '' }))
    }
)

const nameOf = (id) => props.members.find((m) => m.id === id)?.full_name
const presentCount = computed(() => form.attendance.filter((r) => r.present).length)

// Incidencias: qué tarjetas tienen el input de nota desplegado.
const openNotes = ref([])
const noteOpen = (id) => openNotes.value.includes(id)
function toggleNote(id) {
    openNotes.value = noteOpen(id) ? openNotes.value.filter((x) => x !== id) : [...openNotes.value, id]
}

function changeFilters(branchValue) {
    if (branchValue) {
        try { localStorage.setItem(BRANCH_KEY, branchValue) } catch (e) { /* modo privado */ }
    }
    router.get(route('attendance.index'), { branch: branchValue, date: date.value }, { preserveState: true, preserveScroll: true, replace: true })
}
function toggle(row) { row.present = !row.present }
function markAll(present) { form.attendance.forEach((r) => (r.present = present)) }
function save() {
    form.date = date.value
    form.post(route('attendance.store'), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => toast.success('Asistencia guardada.'),
    })
}
</script>

<template>
    <Head title="Control de asistencia" />
    <AppLayout>
        <PageHeader title="Control de asistencia" subtitle="Toca para marcar presente/ausente y guarda en un clic." icon="check">
            <template #actions>
                <input v-model="date" type="date" class="input py-1.5" @change="changeFilters(branch)" />
            </template>
        </PageHeader>

        <!-- Ramas -->
        <div class="mb-4 flex flex-wrap items-center gap-2">
            <button v-for="b in branches" :key="b.value" type="button"
                class="rounded-full border px-4 py-1.5 text-sm font-medium transition"
                :class="branch === b.value ? 'border-brand-600 bg-brand-50 text-brand-700' : 'border-ink-300 text-ink-600 hover:bg-ink-50'"
                @click="changeFilters(b.value)">{{ b.label }}</button>
            <span v-if="stats.sessions" class="ml-auto text-xs font-medium text-ink-500">
                Este trimestre: {{ stats.sessions }} {{ stats.sessions === 1 ? 'reunión' : 'reuniones' }}
                <template v-if="stats.avg_present !== null"> · {{ stats.avg_present }}% de asistencia media</template>
            </span>
        </div>

        <div v-if="form.attendance.length" class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <div class="flex gap-3 text-sm">
                <button class="font-medium text-emerald-700 hover:underline" @click="markAll(true)">Todos presentes</button>
                <button class="font-medium text-ink-500 hover:underline" @click="markAll(false)">Todos ausentes</button>
            </div>
            <span class="text-sm font-semibold text-ink-600">
                {{ presentCount }} / {{ form.attendance.length }} presentes
            </span>
        </div>

        <!-- Rejilla -->
        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
            <div v-for="row in form.attendance" :key="row.member_id"
                class="rounded-xl border shadow-sm transition"
                :class="row.present ? 'border-emerald-500 bg-emerald-50' : 'border-ink-200 bg-white hover:border-ink-300'">
                <div class="flex items-center gap-1 pl-4 pr-2">
                    <button type="button"
                        class="flex min-w-0 flex-1 items-center justify-between gap-2 py-3 text-left text-sm font-medium"
                        :class="row.present ? 'text-emerald-800' : 'text-ink-600'"
                        @click="toggle(row)">
                        <span class="truncate">{{ nameOf(row.member_id) }}</span>
                        <span class="shrink-0">{{ row.present ? '✓ Presente' : 'Ausente' }}</span>
                    </button>
                    <button type="button"
                        class="shrink-0 rounded-full p-2 text-sm leading-none transition"
                        :class="row.notes ? 'bg-amber-100 text-amber-700' : 'text-ink-300 hover:bg-ink-100 hover:text-ink-500'"
                        :title="row.notes ? 'Incidencia anotada' : 'Anotar incidencia'"
                        :aria-label="`Incidencia de ${nameOf(row.member_id)}`"
                        @click="toggleNote(row.member_id)">✎</button>
                </div>
                <div v-if="noteOpen(row.member_id)" class="border-t px-3 pb-2.5 pt-2"
                    :class="row.present ? 'border-emerald-200' : 'border-ink-100'">
                    <input v-model="row.notes" type="text" maxlength="500"
                        class="input w-full py-1.5 text-sm"
                        placeholder="Incidencia (ej. se fue antes, pequeña herida…)" />
                </div>
            </div>
        </div>

        <p v-if="!form.attendance.length" class="mt-6 rounded-xl border border-dashed border-ink-300 bg-white p-8 text-center text-sm text-ink-400">
            Selecciona una rama para pasar lista.
        </p>

        <div v-if="form.attendance.length" class="sticky bottom-4 mt-6 flex justify-end">
            <button class="btn-primary px-6 py-3 shadow-lg" :disabled="form.processing" @click="save">
                Guardar asistencia
            </button>
        </div>
    </AppLayout>
</template>
