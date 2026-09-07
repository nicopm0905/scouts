<script setup>
import { computed, ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import DataTable from '@/Components/Shared/DataTable.vue'
import StatCard from '@/Components/Shared/StatCard.vue'
import AppButton from '@/Components/Shared/AppButton.vue'
import FilterSelect from '@/Components/Shared/FilterSelect.vue'
import BadgeEstado from '@/Components/Shared/BadgeEstado.vue'
import BadgeRama from '@/Components/Shared/BadgeRama.vue'
import ConfirmButton from '@/Components/Shared/ConfirmButton.vue'
import { useToast } from '@/composables/useToast'

const props = defineProps({
    members: { type: Array, default: () => [] },
    branches: { type: Array, default: () => [] },
    canManage: { type: Boolean, default: false },
    canImport: { type: Boolean, default: false },
})

const toast = useToast()
const branchFilter = ref('')
const onlyActive = ref(false)

const initials = (name) => (name ?? '?').split(' ').map((p) => p[0]).slice(0, 2).join('').toUpperCase()

const stats = computed(() => ({
    total: props.members.length,
    active: props.members.filter((m) => m.active).length,
    leaders: props.members.filter((m) => m.role === 'responsable').length,
}))

const columns = [
    { key: 'full_name', label: 'Nombre', sortable: true },
    { key: 'role_label', label: 'Rama', sortable: true },
    { key: 'age', label: 'Edad', sortable: true },
    { key: 'phone', label: 'Teléfono' },
    { key: 'active', label: 'Estado' },
]

const filteredRows = computed(() =>
    props.members.filter((m) => {
        if (branchFilter.value && m.role !== branchFilter.value) return false
        if (onlyActive.value && !m.active) return false
        return true
    })
)

function destroy(member) {
    router.delete(route('members.destroy', member.id), { onSuccess: () => toast.success('Miembro eliminado.') })
}
</script>

<template>
    <Head title="Miembros" />
    <AppLayout>
        <PageHeader title="Miembros" subtitle="Fichas de scouts y responsables del grupo." icon="users">
            <template #actions>
                <AppButton :href="route('attendance.index')" size="sm" icon="check">Asistencia</AppButton>
                <AppButton :href="route('families.index')" size="sm" icon="heart">Familias</AppButton>
                <AppButton :href="route('members.export')" external size="sm" icon="download">Exportar CSV</AppButton>
                <AppButton :href="route('members.census-msc')" external size="sm" variant="secondary" icon="download">Censo MSC</AppButton>
                <AppButton v-if="canImport" :href="route('members.import')" size="sm" icon="upload">Importar CSV</AppButton>
                <AppButton v-if="canManage" :href="route('members.create')" variant="primary" size="sm" icon="plus">
                    Nuevo miembro
                </AppButton>
            </template>
        </PageHeader>

        <!-- Cifras del censo -->
        <div class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <StatCard label="Total" :value="stats.total" icon="users" />
            <StatCard label="Activos" :value="stats.active" tone="positive" icon="check" />
            <StatCard label="Responsables" :value="stats.leaders" tone="brand" icon="star" />
        </div>

        <DataTable :columns="columns" :rows="filteredRows" persist-key="members" placeholder="Buscar por nombre…">
            <template #filters>
                <FilterSelect
                    v-model="branchFilter"
                    :options="[{ value: '', label: 'Todas las ramas' }, ...branches]"
                />
                <label class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-medium text-slate-600 shadow-2xs">
                    <input v-model="onlyActive" type="checkbox" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500" />
                    Solo activos
                </label>
            </template>

            <template #cell-full_name="{ row }">
                <Link :href="route('members.show', row.id)" class="flex items-center gap-2.5">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-slate-700 ring-1 ring-slate-200">
                        {{ initials(row.full_name) }}
                    </span>
                    <span class="font-bold text-slate-900 hover:text-emerald-700 transition">{{ row.full_name }}</span>
                </Link>
            </template>
            <template #cell-role_label="{ row }">
                <BadgeRama :rama="row.role" />
            </template>
            <template #cell-age="{ row }">
                <div v-if="row.age !== null" class="leading-tight">
                    <span class="font-bold text-slate-800">{{ row.age }} años</span>
                    <p v-if="row.birth_date" class="text-[11px] font-medium text-slate-500 flex items-center gap-1 mt-0.5">
                        <svg class="w-3 h-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5m-9-6h.008v.008H12v-.008zM12 15h.008v.008H12V15zm0 2.25h.008v.008H12v-.008zM9.75 15h.008v.008H9.75V15zm0 2.25h.008v.008H9.75v-.008zM7.5 15h.008v.008H7.5V15zm0 2.25h.008v.008H7.5v-.008zm6.75-4.5h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V15zm0 2.25h.008v.008h-.008v-.008zm2.25-4.5h.008v.008H16.5v-.008zm0 2.25h.008v.008H16.5V15z" />
                        </svg>
                        {{ row.birth_date }}
                    </p>
                </div>
                <span v-else class="text-slate-400">—</span>
            </template>
            <template #cell-phone="{ value }">{{ value ?? '—' }}</template>
            <template #cell-active="{ value }">
                <BadgeEstado :label="value ? 'Activo' : 'Baja'" :color="value ? 'green' : 'gray'" />
            </template>
            <template #actions="{ row }">
                <div class="flex justify-end gap-3 text-sm">
                    <Link :href="route('members.show', row.id)" class="font-medium text-brand-700 hover:underline">Ver</Link>
                    <Link v-if="canManage" :href="route('members.edit', row.id)" class="text-ink-500 hover:underline">Editar</Link>
                    <ConfirmButton v-if="canManage" title="Eliminar miembro"
                        :message="`¿Seguro que quieres eliminar a ${row.full_name}?`" confirm-label="Eliminar" @confirm="destroy(row)">
                        <span class="text-brand-600 hover:underline">Eliminar</span>
                    </ConfirmButton>
                </div>
            </template>
            <template #empty>No se han encontrado miembros.</template>
        </DataTable>
    </AppLayout>
</template>
