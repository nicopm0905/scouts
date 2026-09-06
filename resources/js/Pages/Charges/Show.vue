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

// Cobro en bloque: tras una salida, cerrar en efectivo a varios de una vez.
const pendingRows = computed(() => props.assignments.filter((a) => a.status === 'pending'))
const selected = ref([])
const bulkForm = useForm({ assignment_ids: [], payment_method: props.paymentMethods[0]?.value ?? '' })
const allPendingSelected = computed(() => pendingRows.value.length > 0 && selected.value.length === pendingRows.value.length)

function toggleAllPending() {
    selected.value = allPendingSelected.value ? [] : pendingRows.value.map((r) => r.id)
}
function submitBulk() {
    if (!selected.value.length) return
    bulkForm.assignment_ids = selected.value
    bulkForm.post(route('charges.members.bulk-mark-paid', props.charge.id), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => { toast.success('Pagos registrados.'); selected.value = [] },
    })
}

function openPay(row) {
    paying.value = row
    payForm.payment_method = props.paymentMethods[0]?.value ?? ''
    payForm.notes = ''
}
function markPaid() {
    payForm.post(route('charges.members.mark-paid', paying.value.id), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => { toast.success('Pago registrado.'); paying.value = null },
    })
}
function remind() {
    router.post(route('charges.remind', props.charge.id), {}, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => toast.success('Recordatorios enviados a los pendientes.'),
    })
}
function whatsAppPaymentReminder(row) {
    if (!row.phone) return '#'
    const cleanPhone = row.phone.replace(/\D/g, '')
    const fullPhone = cleanPhone.length === 9 ? `34${cleanPhone}` : cleanPhone
    const text = `⚜️ *SCOUTS DE SAN JOSÉ* ⚜️\n💶 *RECORDATORIO DE PAGO*\n\nHola! Te escribimos de Scouts de San José para recordarte que está pendiente el pago de *${props.charge.title}* por importe de *${eur(row.amount)}* de *${row.member_name}*.\n\nPor favor, avísanos cuando lo realices. ¡Muchas gracias! ⚜️`
    return `https://wa.me/${fullPhone}?text=${encodeURIComponent(text)}`
}
</script>

<template>
    <Head :title="charge.title" />
    <AppLayout>
        <PageHeader :title="charge.title" :subtitle="charge.type_label" icon="euro">
            <template #actions>
                <Link v-if="charge.event_id" :href="route('events.show', charge.event_id)" class="btn-secondary btn-sm">
                    Ir al evento
                </Link>
                <button v-if="can.manage && summary.pending_count > 0" class="btn-secondary btn-sm" @click="remind">
                    Recordar a pendientes ({{ summary.pending_count }})
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

        <!-- Cobro en bloque (efectivo en mano tras una salida) -->
        <div v-if="can.manage && pendingRows.length" class="mb-4 flex flex-col gap-3 rounded-xl border border-emerald-200 bg-emerald-50/60 p-4 sm:flex-row sm:items-center sm:justify-between">
            <label class="flex items-center gap-2 text-sm font-medium text-ink-700">
                <input type="checkbox" :checked="allPendingSelected" class="rounded border-ink-300 text-emerald-600 focus:ring-emerald-500" @change="toggleAllPending" />
                Seleccionar los {{ pendingRows.length }} pendientes
                <span v-if="selected.length" class="text-emerald-700">· {{ selected.length }} marcados</span>
            </label>
            <div class="flex flex-wrap items-center gap-2">
                <select v-model="bulkForm.payment_method" class="input min-w-0 py-1.5 text-sm">
                    <option v-for="m in paymentMethods" :key="m.value" :value="m.value">{{ m.label }}</option>
                </select>
                <button class="btn-primary btn-sm" :disabled="!selected.length || bulkForm.processing" @click="submitBulk">
                    Marcar como pagados ({{ selected.length }})
                </button>
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
                <div class="flex items-center justify-end gap-2">
                    <label v-if="can.manage && row.status === 'pending'" class="flex items-center" :title="`Seleccionar a ${row.member_name} para cobro en bloque`">
                        <input type="checkbox" :value="row.id" v-model="selected" class="rounded border-ink-300 text-emerald-600 focus:ring-emerald-500" />
                    </label>
                    <a v-if="row.phone && row.status !== 'paid'" :href="whatsAppPaymentReminder(row)" target="_blank" class="rounded-lg bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-800 hover:bg-emerald-200 transition">
                        💬 Recordatorio
                    </a>
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
