<script setup>
import { ref, computed } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
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
    summary: { type: Object, default: () => ({}) },
    paymentMethods: { type: Array, default: () => [] },
    can: { type: Object, default: () => ({}) },
})

const toast = useToast()
const eur = (n) => Number(n ?? 0).toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' €'

const statusFilter = ref('all')
const filtered = computed(() =>
    statusFilter.value === 'all' ? props.assignments : props.assignments.filter((a) => a.status === statusFilter.value)
)

const pct = computed(() => (props.summary.expected > 0 ? Math.round((props.summary.collected / props.summary.expected) * 100) : 0))

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
        onSuccess: () => { toast.success('Pago registrado.'); paying.value = null },
    })
}
function remind() {
    router.post(route('charges.remind', props.charge.id), {}, {
        preserveScroll: true,
        onSuccess: () => toast.success('Recordatorios enviados a los pendientes.'),
    })
}
</script>

<template>
    <Head :title="charge.title" />
    <AppLayout>
        <PageHeader :title="charge.title" :subtitle="charge.type_label">
            <template #actions>
                <button v-if="can.manage && summary.pending_count > 0" class="btn-secondary btn-sm" @click="remind">
                    🔔 Recordar a pendientes ({{ summary.pending_count }})
                </button>
                <Link :href="route('charges.index')" class="btn-ghost btn-sm">← Volver</Link>
            </template>
        </PageHeader>

        <p v-if="charge.description" class="mb-4 text-sm text-ink-600">{{ charge.description }}</p>

        <!-- Resumen de recaudación -->
        <div class="mb-6 card p-5">
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <p class="section-title">Recaudado</p>
                    <p class="mt-1 text-xl font-bold text-emerald-600">{{ eur(summary.collected) }}</p>
                    <p class="text-xs text-ink-400">{{ summary.paid_count }} de {{ summary.total_count }} pagados</p>
                </div>
                <div>
                    <p class="section-title">Pendiente</p>
                    <p class="mt-1 text-xl font-bold text-brand-600">{{ eur(summary.pending) }}</p>
                    <p class="text-xs text-ink-400">{{ summary.pending_count }} sin cerrar</p>
                </div>
                <div>
                    <p class="section-title">Previsto</p>
                    <p class="mt-1 text-xl font-bold text-ink-800">{{ eur(summary.expected) }}</p>
                    <p class="text-xs text-ink-400">{{ pct }}% cobrado</p>
                </div>
            </div>
            <div class="mt-4 h-2.5 w-full overflow-hidden rounded-full bg-ink-100">
                <div class="h-full rounded-full bg-emerald-500 transition-all" :style="{ width: pct + '%' }" />
            </div>
        </div>

        <DataTable :columns="columns" :rows="filtered" persist-key="charge-assignments" placeholder="Buscar miembro…">
            <template #filters>
                <div class="inline-flex rounded-lg border border-ink-200 bg-white p-0.5 text-sm">
                    <button v-for="f in [['all','Todos'],['pending','Pendientes'],['paid','Pagados'],['exempt','Exentos']]" :key="f[0]"
                        class="rounded-md px-3 py-1 font-medium transition"
                        :class="statusFilter === f[0] ? 'bg-brand-600 text-white' : 'text-ink-600 hover:bg-ink-100'"
                        @click="statusFilter = f[0]">{{ f[1] }}</button>
                </div>
            </template>
            <template #cell-amount="{ value }">{{ eur(value) }}</template>
            <template #cell-status_label="{ row }">
                <BadgeEstado :label="row.status_label" :color="row.status_color" />
            </template>
            <template #actions="{ row }">
                <div class="flex items-center justify-end gap-3">
                    <Link :href="route('charges.members.history', row.member_id)" class="text-sm text-ink-500 hover:underline">Historial</Link>
                    <button v-if="can.manage && row.status !== 'paid'" class="btn-primary btn-sm" @click="openPay(row)">Marcar pagado</button>
                </div>
            </template>
        </DataTable>

        <Modal :show="!!paying" title="Marcar como pagado" max-width="sm" @close="paying = null">
            <p class="mb-3 text-sm text-ink-600">{{ paying?.member_name }} · {{ eur(paying?.amount) }}</p>
            <label class="label">Método de pago</label>
            <select v-model="payForm.payment_method" class="input mt-1">
                <option v-for="m in paymentMethods" :key="m.value" :value="m.value">{{ m.label }}</option>
            </select>
            <template #footer>
                <button class="btn-ghost" @click="paying = null">Cancelar</button>
                <button class="btn-primary" :disabled="payForm.processing" @click="markPaid">Confirmar pago</button>
            </template>
        </Modal>
    </AppLayout>
</template>
