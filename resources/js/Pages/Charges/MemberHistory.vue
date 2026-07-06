<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import DataTable from '@/Components/Shared/DataTable.vue'
import BadgeEstado from '@/Components/Shared/BadgeEstado.vue'

defineProps({
    member: { type: Object, required: true },
    history: { type: Array, default: () => [] },
})

const columns = [
    { key: 'title', label: 'Cobro', sortable: true },
    { key: 'type_label', label: 'Tipo' },
    { key: 'due_date', label: 'Vencimiento', sortable: true },
    { key: 'amount', label: 'Importe' },
    { key: 'status_label', label: 'Estado' },
]
</script>

<template>
    <Head :title="`Historial de ${member.name}`" />
    <AppLayout>
        <PageHeader :title="`Historial de pagos: ${member.name}`" :subtitle="member.branch">
            <template #actions>
                <Link :href="route('charges.index')" class="text-sm text-slate-500 hover:underline">← Volver a cobros</Link>
            </template>
        </PageHeader>

        <DataTable :columns="columns" :rows="history" :searchable="false">
            <template #cell-amount="{ value }">{{ Number(value).toFixed(2) }} €</template>
            <template #cell-status_label="{ row }">
                <BadgeEstado :label="row.status_label" :color="row.status_color" />
            </template>
            <template #actions="{ row }">
                <Link :href="route('charges.show', row.charge_id)" class="text-emerald-700 hover:underline">Ver cobro</Link>
            </template>
        </DataTable>
    </AppLayout>
</template>
