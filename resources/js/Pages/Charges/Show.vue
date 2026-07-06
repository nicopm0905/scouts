<script setup>
import { ref, computed } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import DataTable from '@/Components/Shared/DataTable.vue'
import Modal from '@/Components/Shared/Modal.vue'
import BadgeEstado from '@/Components/Shared/BadgeEstado.vue'
import { useToast } from '@/composables/useToast'
import { useAuth } from '@/composables/useAuth'

const props = defineProps({
    charge: { type: Object, required: true },
    assignments: { type: Array, default: () => [] },
    paymentMethods: { type: Array, default: () => [] },
})

const toast = useToast()
const { can } = useAuth()

const columns = [
    { key: 'member_name', label: 'Miembro', sortable: true },
    { key: 'branch', label: 'Rama', sortable: true },
    { key: 'amount', label: 'Importe', sortable: true },
    { key: 'status_label', label: 'Estado' },
]

const paying = ref(null)
const payForm = useForm({ payment_method: props.paymentMethods[0]?.value ?? '', notes: '' })

function openPay(row) {
    paying.value = row
    payForm.payment_method = props.paymentMethods[0]?.value ?? ''
    payForm.notes = ''
}

function markPaid() {
    payForm.post(route('charges.members.mark-paid', paying.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Pago registrado.')
            paying.value = null
        },
    })
}

const paidCount = computed(() => props.assignments.filter((a) => a.status === 'paid').length)
</script>

<template>
    <Head :title="charge.title" />
    <AppLayout>
        <PageHeader :title="charge.title" :subtitle="`${charge.type_label} · ${paidCount}/${assignments.length} pagados`">
            <template #actions>
                <Link :href="route('charges.index')" class="text-sm text-slate-500 hover:underline">← Volver a cobros</Link>
            </template>
        </PageHeader>

        <p v-if="charge.description" class="mb-4 text-sm text-slate-600">{{ charge.description }}</p>

        <DataTable :columns="columns" :rows="assignments" persist-key="charge-assignments" placeholder="Buscar miembro…">
            <template #cell-amount="{ value }">{{ Number(value).toFixed(2) }} €</template>
            <template #cell-status_label="{ row }">
                <BadgeEstado :label="row.status_label" :color="row.status_color" />
            </template>
            <template #actions="{ row }">
                <div class="flex items-center justify-end gap-3">
                    <Link :href="route('charges.members.history', row.member_id)" class="text-sm text-slate-500 hover:underline">
                        Historial
                    </Link>
                    <button
                        v-if="can('charges.manage') && row.status !== 'paid'"
                        class="rounded-md bg-brand-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-brand-700"
                        @click="openPay(row)"
                    >
                        Marcar pagado
                    </button>
                </div>
            </template>
        </DataTable>

        <Modal :show="!!paying" title="Marcar como pagado" max-width="sm" @close="paying = null">
            <p class="mb-3 text-sm text-slate-600">{{ paying?.member_name }} · {{ Number(paying?.amount ?? 0).toFixed(2) }} €</p>
            <label class="block text-sm font-medium text-slate-700">Método de pago</label>
            <select v-model="payForm.payment_method" class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm">
                <option v-for="m in paymentMethods" :key="m.value" :value="m.value">{{ m.label }}</option>
            </select>

            <template #footer>
                <button class="rounded-md px-4 py-2 text-sm text-slate-600 hover:bg-slate-100" @click="paying = null">
                    Cancelar
                </button>
                <button
                    class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700"
                    :disabled="payForm.processing"
                    @click="markPaid"
                >
                    Confirmar pago
                </button>
            </template>
        </Modal>
    </AppLayout>
</template>
