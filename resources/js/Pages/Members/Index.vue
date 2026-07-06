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
    router.delete(route('members.destroy', member.id), {
        onSuccess: () => toast.success('Miembro eliminado.'),
    })
}
</script>

<template>
    <Head title="Miembros" />
    <AppLayout>
        <PageHeader title="Miembros" subtitle="Fichas de scouts y responsables del grupo.">
            <template #actions>
                <Link
                    :href="route('attendance.index')"
                    class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                >
                    Asistencia
                </Link>
                <Link
                    :href="route('families.index')"
                    class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                >
                    Familias
                </Link>
                <Link
                    v-if="canImport"
                    :href="route('members.import')"
                    class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                >
                    Importar CSV
                </Link>
                <Link
                    v-if="canManage"
                    :href="route('members.create')"
                    class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700"
                >
                    + Nuevo miembro
                </Link>
            </template>
        </PageHeader>

        <DataTable :columns="columns" :rows="filteredRows" persist-key="members" placeholder="Buscar por nombre…">
            <template #filters>
                <select v-model="branchFilter" class="rounded-md border-slate-300 text-sm shadow-sm">
                    <option value="">Todas las ramas</option>
                    <option v-for="b in branches" :key="b.value" :value="b.value">{{ b.label }}</option>
                </select>
                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input v-model="onlyActive" type="checkbox" class="rounded border-slate-300 text-emerald-600" />
                    Solo activos
                </label>
            </template>

            <template #cell-full_name="{ row }">
                <Link :href="route('members.show', row.id)" class="font-medium text-emerald-700 hover:underline">
                    {{ row.full_name }}
                </Link>
            </template>
            <template #cell-active="{ value }">
                <BadgeEstado :label="value ? 'Activo' : 'Baja'" :color="value ? 'green' : 'gray'" />
            </template>
            <template #actions="{ row }">
                <div class="flex justify-end gap-3">
                    <Link :href="route('members.show', row.id)" class="text-emerald-700 hover:underline">Ver</Link>
                    <Link v-if="canManage" :href="route('members.edit', row.id)" class="text-slate-600 hover:underline">Editar</Link>
                    <ConfirmButton
                        v-if="canManage"
                        title="Eliminar miembro"
                        :message="`¿Seguro que quieres eliminar a ${row.full_name}?`"
                        confirm-label="Eliminar"
                        @confirm="destroy(row)"
                    >
                        <span class="text-red-600 hover:underline">Eliminar</span>
                    </ConfirmButton>
                </div>
            </template>
            <template #empty>No se han encontrado miembros.</template>
        </DataTable>
    </AppLayout>
</template>
