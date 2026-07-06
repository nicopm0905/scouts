<script setup>
import { Link } from '@inertiajs/vue3'
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import DataTable from '@/Components/Shared/DataTable.vue'

defineProps({
    families: { type: Array, default: () => [] },
    canManage: { type: Boolean, default: false },
})

const columns = [
    { key: 'name', label: 'Familia', sortable: true },
    { key: 'members_count', label: 'Miembros', sortable: true },
    { key: 'contact_phone', label: 'Teléfono' },
    { key: 'contact_email', label: 'Correo' },
]
</script>

<template>
    <Head title="Familias" />
    <AppLayout>
        <PageHeader title="Familias" subtitle="Grupos familiares y sus vínculos con los miembros.">
            <template #actions>
                <Link :href="route('members.index')" class="text-sm text-slate-500 hover:underline">Volver a miembros</Link>
                <Link
                    v-if="canManage"
                    :href="route('families.create')"
                    class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700"
                >
                    + Nueva familia
                </Link>
            </template>
        </PageHeader>

        <DataTable :columns="columns" :rows="families" persist-key="families" placeholder="Buscar familia…">
            <template #cell-name="{ row }">
                <Link :href="route('families.edit', row.id)" class="font-medium text-emerald-700 hover:underline">
                    {{ row.name }}
                </Link>
            </template>
            <template #empty>Todavía no hay familias creadas.</template>
        </DataTable>
    </AppLayout>
</template>
