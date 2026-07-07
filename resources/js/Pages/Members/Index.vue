<script setup>
import { computed, ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import DataTable from '@/Components/Shared/DataTable.vue'
import BadgeEstado from '@/Components/Shared/BadgeEstado.vue'
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
        <PageHeader title="Miembros" subtitle="Fichas de scouts y responsables del grupo.">
            <template #actions>
                <Link :href="route('attendance.index')" class="btn-secondary btn-sm">Asistencia</Link>
                <Link :href="route('families.index')" class="btn-secondary btn-sm">Familias</Link>
                <Link v-if="canImport" :href="route('members.import')" class="btn-secondary btn-sm">Importar CSV</Link>
                <Link v-if="canManage" :href="route('members.create')" class="btn-primary btn-sm">+ Nuevo miembro</Link>
            </template>
        </PageHeader>

        <!-- KPIs -->
        <div class="mb-6 grid grid-cols-3 gap-4">
            <div class="card p-4">
                <p class="section-title">Total</p>
                <p class="mt-1 text-2xl font-bold text-ink-800">{{ stats.total }}</p>
            </div>
            <div class="card p-4">
                <p class="section-title">Activos</p>
                <p class="mt-1 text-2xl font-bold text-emerald-600">{{ stats.active }}</p>
            </div>
            <div class="card p-4">
                <p class="section-title">Responsables</p>
                <p class="mt-1 text-2xl font-bold text-brand-600">{{ stats.leaders }}</p>
            </div>
        </div>

        <DataTable :columns="columns" :rows="filteredRows" persist-key="members" placeholder="Buscar por nombre…">
            <template #filters>
                <select v-model="branchFilter" class="input w-auto py-1.5 text-sm">
                    <option value="">Todas las ramas</option>
                    <option v-for="b in branches" :key="b.value" :value="b.value">{{ b.label }}</option>
                </select>
                <label class="flex items-center gap-2 text-sm text-ink-600">
                    <input v-model="onlyActive" type="checkbox" class="rounded border-ink-300 text-brand-600 focus:ring-brand-500" />
                    Solo activos
                </label>
            </template>

            <template #cell-full_name="{ row }">
                <Link :href="route('members.show', row.id)" class="flex items-center gap-2.5">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand-100 text-xs font-semibold text-brand-700">
                        {{ initials(row.full_name) }}
                    </span>
                    <span class="font-medium text-ink-800 hover:text-brand-700">{{ row.full_name }}</span>
                </Link>
            </template>
            <template #cell-age="{ value }">{{ value ?? '—' }}</template>
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
