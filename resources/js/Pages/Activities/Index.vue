<script setup>
import { ref, watch } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import DataTable from '@/Components/Shared/DataTable.vue'
import { useToast } from '@/composables/useToast'

defineOptions({ layout: AppLayout })

const props = defineProps({
    activities: { type: Array, default: () => [] },
    branches: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    canManage: { type: Boolean, default: false },
})

const toast = useToast()

const filterForm = ref({
    branch: props.filters.branch ?? '',
    min_duration: props.filters.min_duration ?? '',
    max_duration: props.filters.max_duration ?? '',
    material: props.filters.material ?? '',
})

function applyFilters() {
    router.get(route('activities.index'), filterForm.value, { preserveState: true, replace: true })
}

const columns = [
    { key: 'title', label: 'Actividad', sortable: true },
    { key: 'branch_label', label: 'Rama', sortable: true },
    { key: 'duration_minutes', label: 'Duración (min)', sortable: true },
    { key: 'materials', label: 'Materiales' },
]

function duplicate(activity) {
    router.post(route('activities.duplicate', activity.id), {}, {
        onSuccess: () => toast.success('Actividad duplicada correctamente.'),
    })
}
</script>

<template>
    <Head title="Actividades" />

    <PageHeader title="Biblioteca de actividades" subtitle="Actividades reutilizables por rama, con materiales y adjuntos.">
        <template #actions>
            <Link
                v-if="canManage"
                :href="route('activities.create')"
                class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700"
            >
                + Nueva actividad
            </Link>
        </template>
    </PageHeader>

    <DataTable :columns="columns" :rows="activities" persist-key="activities" placeholder="Buscar por título…">
        <template #filters>
            <select v-model="filterForm.branch" class="rounded-md border-slate-300 text-sm" @change="applyFilters">
                <option value="">Todas las ramas</option>
                <option v-for="b in branches" :key="b.value" :value="b.value">{{ b.label }}</option>
            </select>
            <input
                v-model="filterForm.min_duration"
                type="number"
                placeholder="Min. min"
                class="w-24 rounded-md border-slate-300 text-sm"
                @change="applyFilters"
            />
            <input
                v-model="filterForm.max_duration"
                type="number"
                placeholder="Máx. min"
                class="w-24 rounded-md border-slate-300 text-sm"
                @change="applyFilters"
            />
            <input
                v-model="filterForm.material"
                type="text"
                placeholder="Material…"
                class="w-32 rounded-md border-slate-300 text-sm"
                @change="applyFilters"
            />
        </template>
        <template #cell-materials="{ value }">
            <span class="text-xs text-slate-500">{{ value.join(', ') || '—' }}</span>
        </template>
        <template #actions="{ row }">
            <div class="flex justify-end gap-3">
                <Link :href="route('activities.show', row.id)" class="text-emerald-700 hover:underline">Ver</Link>
                <button v-if="canManage" class="text-slate-500 hover:underline" @click="duplicate(row)">Duplicar</button>
            </div>
        </template>
        <template #empty>No se han encontrado actividades.</template>
    </DataTable>
</template>
