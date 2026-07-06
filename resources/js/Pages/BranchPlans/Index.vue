<script setup>
import { ref } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import DataTable from '@/Components/Shared/DataTable.vue'
import Modal from '@/Components/Shared/Modal.vue'
import FormField from '@/Components/Shared/FormField.vue'
import { useToast } from '@/composables/useToast'

defineOptions({ layout: AppLayout })

const props = defineProps({
    plans: { type: Array, default: () => [] },
    branches: { type: Array, default: () => [] },
    canManage: { type: Boolean, default: false },
})

const toast = useToast()
const showCreate = ref(false)

const form = useForm({
    branch: '',
    school_year: '',
    description: '',
})

const columns = [
    { key: 'branch_label', label: 'Rama', sortable: true },
    { key: 'school_year', label: 'Curso', sortable: true },
    { key: 'completion_percentage', label: 'Progreso', sortable: true },
]

function submit() {
    form.post(route('branch-plans.store'), {
        onSuccess: () => {
            showCreate.value = false
            form.reset()
            toast.success('Plan de rama creado correctamente.')
        },
    })
}
</script>

<template>
    <Head title="Plan de rama" />

    <PageHeader title="Plan de rama" subtitle="Objetivos educativos por curso escolar y rama.">
        <template #actions>
            <button
                v-if="canManage"
                class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700"
                @click="showCreate = true"
            >
                + Nuevo plan
            </button>
        </template>
    </PageHeader>

    <DataTable :columns="columns" :rows="plans" persist-key="branch-plans" placeholder="Buscar rama o curso…">
        <template #cell-completion_percentage="{ row }">
            <div class="flex items-center gap-2">
                <div class="h-2 w-32 overflow-hidden rounded-full bg-slate-200">
                    <div class="h-full bg-brand-500" :style="{ width: row.completion_percentage + '%' }" />
                </div>
                <span class="text-xs text-slate-500">{{ row.completion_percentage }}%</span>
            </div>
        </template>
        <template #actions="{ row }">
            <Link :href="route('branch-plans.show', row.id)" class="text-brand-700 hover:underline">Ver</Link>
        </template>
        <template #empty>Todavía no hay planes de rama creados.</template>
    </DataTable>

    <Modal :show="showCreate" title="Nuevo plan de rama" @close="showCreate = false">
        <form class="space-y-4" @submit.prevent="submit">
            <FormField
                v-model="form.branch"
                type="select"
                label="Rama"
                :options="branches"
                :error="form.errors.branch"
                required
            />
            <FormField
                v-model="form.school_year"
                label="Curso escolar"
                placeholder="2026-2027"
                :error="form.errors.school_year"
                required
            />
            <FormField
                v-model="form.description"
                type="textarea"
                label="Descripción"
                :error="form.errors.description"
            />
        </form>
        <template #footer>
            <button class="rounded-md px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100" @click="showCreate = false">
                Cancelar
            </button>
            <button
                class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700"
                :disabled="form.processing"
                @click="submit"
            >
                Guardar
            </button>
        </template>
    </Modal>
</template>
