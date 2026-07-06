<script setup>
import { ref } from 'vue'
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

function changeFilters(branch) {
    router.get(route('attendance.index'), { branch, date: date.value }, { preserveState: true })
}

function toggle(row) {
    row.present = !row.present
}

function markAll(present) {
    form.attendance.forEach((row) => (row.present = present))
}

function save() {
    form.date = date.value
    form.post(route('attendance.store'), {
        preserveScroll: true,
        onSuccess: () => toast.success('Asistencia guardada.'),
    })
}
</script>

<template>
    <Head title="Control de asistencia" />
    <AppLayout>
        <PageHeader title="Control de asistencia" subtitle="Marca presente/ausente y guarda en un clic.">
            <template #actions>
                <input
                    v-model="date"
                    type="date"
                    class="rounded-md border-slate-300 text-sm shadow-sm"
                    @change="changeFilters(branch)"
                />
            </template>
        </PageHeader>

        <div class="mb-4 flex flex-wrap gap-2">
            <button
                v-for="b in branches"
                :key="b.value"
                type="button"
                class="rounded-full border px-4 py-1.5 text-sm font-medium"
                :class="branch === b.value ? 'border-emerald-600 bg-emerald-50 text-emerald-700' : 'border-slate-300 text-slate-600'"
                @click="changeFilters(b.value)"
            >
                {{ b.label }}
            </button>
        </div>

        <div class="mb-3 flex gap-2">
            <button class="text-sm text-emerald-700 hover:underline" @click="markAll(true)">Marcar todos presentes</button>
            <button class="text-sm text-slate-500 hover:underline" @click="markAll(false)">Marcar todos ausentes</button>
        </div>

        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
            <button
                v-for="row in form.attendance"
                :key="row.member_id"
                type="button"
                class="flex items-center justify-between rounded-lg border px-4 py-3 text-left text-sm font-medium shadow-sm transition"
                :class="row.present ? 'border-emerald-600 bg-emerald-50 text-emerald-800' : 'border-slate-200 bg-white text-slate-600'"
                @click="toggle(row)"
            >
                <span>{{ members.find((m) => m.id === row.member_id)?.full_name }}</span>
                <span>{{ row.present ? '✅ Presente' : '⬜ Ausente' }}</span>
            </button>
        </div>

        <p v-if="!form.attendance.length" class="mt-6 text-sm text-slate-400">No hay miembros en esta rama.</p>

        <div class="sticky bottom-4 mt-6 flex justify-end">
            <button
                class="rounded-md bg-emerald-600 px-6 py-3 text-sm font-semibold text-white shadow-lg hover:bg-emerald-700 disabled:opacity-50"
                :disabled="form.processing || !form.attendance.length"
                @click="save"
            >
                Guardar asistencia
            </button>
        </div>
    </AppLayout>
</template>
