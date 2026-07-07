<script setup>
import { computed, ref } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import { useToast } from '@/composables/useToast'

const props = defineProps({
    branches: { type: Array, default: () => [] },
    branch: { type: String, default: null },
    date: { type: String, default: null },
    members: { type: Array, default: () => [] },
})

const toast = useToast()
const date = ref(props.date)

const form = useForm({
    branch: props.branch,
    date: props.date,
    attendance: props.members.map((m) => ({ member_id: m.id, present: m.present })),
})

const nameOf = (id) => props.members.find((m) => m.id === id)?.full_name
const presentCount = computed(() => form.attendance.filter((r) => r.present).length)

function changeFilters(branch) {
    router.get(route('attendance.index'), { branch, date: date.value }, { preserveState: true })
}
function toggle(row) { row.present = !row.present }
function markAll(present) { form.attendance.forEach((r) => (r.present = present)) }
function save() {
    form.date = date.value
    form.post(route('attendance.store'), { preserveScroll: true, onSuccess: () => toast.success('Asistencia guardada.') })
}
</script>

<template>
    <Head title="Control de asistencia" />
    <AppLayout>
        <PageHeader title="Control de asistencia" subtitle="Toca para marcar presente/ausente y guarda en un clic.">
            <template #actions>
                <input v-model="date" type="date" class="input py-1.5" @change="changeFilters(branch)" />
            </template>
        </PageHeader>

        <!-- Ramas -->
        <div class="mb-4 flex flex-wrap gap-2">
            <button v-for="b in branches" :key="b.value" type="button"
                class="rounded-full border px-4 py-1.5 text-sm font-medium transition"
                :class="branch === b.value ? 'border-brand-600 bg-brand-50 text-brand-700' : 'border-ink-300 text-ink-600 hover:bg-ink-50'"
                @click="changeFilters(b.value)">{{ b.label }}</button>
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
            <button v-for="row in form.attendance" :key="row.member_id" type="button"
                class="flex items-center justify-between rounded-xl border px-4 py-3 text-left text-sm font-medium shadow-sm transition"
                :class="row.present ? 'border-emerald-500 bg-emerald-50 text-emerald-800' : 'border-ink-200 bg-white text-ink-600 hover:border-ink-300'"
                @click="toggle(row)">
                <span>{{ nameOf(row.member_id) }}</span>
                <span class="shrink-0">{{ row.present ? '✓ Presente' : 'Ausente' }}</span>
            </button>
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
