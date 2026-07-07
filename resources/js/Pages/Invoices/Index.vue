<script setup>
import { ref, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import DataTable from '@/Components/Shared/DataTable.vue'
import BadgeEstado from '@/Components/Shared/BadgeEstado.vue'
import ConfirmButton from '@/Components/Shared/ConfirmButton.vue'
import { useToast } from '@/composables/useToast'
import { useAuth } from '@/composables/useAuth'

const props = defineProps({
    invoices: { type: Array, default: () => [] },
    direction: { type: String, default: null },
    categories: { type: Array, default: () => [] },
    directions: { type: Array, default: () => [] },
})

const toast = useToast()
const { can } = useAuth()
const dragging = ref(false)
const uploadDirection = ref('received')

const eur = (n) => Number(n ?? 0).toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' €'
const rowTotal = (r) => Number(r.amount || 0) + Number(r.vat || 0)

const totals = computed(() => {
    let received = 0, issued = 0
    for (const i of props.invoices) {
        if (i.direction === 'issued') issued += rowTotal(i)
        else received += rowTotal(i)
    }
    return { received, issued, count: props.invoices.length }
})

const columns = [
    { key: 'date', label: 'Fecha', sortable: true },
    { key: 'direction', label: 'Tipo', sortable: true },
    { key: 'supplier_or_client', label: 'Proveedor/Cliente', sortable: true },
    { key: 'concept', label: 'Concepto' },
    { key: 'amount', label: 'Base' },
    { key: 'vat', label: 'IVA' },
    { key: 'total', label: 'Total' },
    { key: 'category', label: 'Categoría' },
]

function filterDirection(dir) {
    router.get(route('invoices.index'), dir ? { direction: dir } : {}, { preserveState: true })
}
function onDrop(e) { dragging.value = false; const f = [...e.dataTransfer.files]; if (f.length) uploadFiles(f) }
function onPick(e) { const f = [...e.target.files]; if (f.length) uploadFiles(f); e.target.value = '' }
function uploadFiles(files) {
    const form = new FormData()
    files.forEach((f) => form.append('files[]', f))
    form.append('direction', uploadDirection.value)
    router.post(route('invoices.upload'), form, {
        forceFormData: true,
        onSuccess: () => toast.success('Facturas subidas. Completa los datos en la tabla.'),
    })
}
function updateInvoice(row, patch) {
    router.patch(route('invoices.update', row.id), { ...row, ...patch }, {
        preserveScroll: true, onSuccess: () => toast.success('Factura actualizada.'),
    })
}
function destroyInvoice(row) {
    router.delete(route('invoices.destroy', row.id), { preserveScroll: true, onSuccess: () => toast.success('Factura eliminada.') })
}
function generatePdf(row) {
    router.post(route('invoices.generate-pdf', row.id), {}, {
        preserveScroll: true, onSuccess: () => toast.success('PDF generado y guardado en Drive.'),
    })
}

const inp = 'w-full rounded border-ink-200 text-xs focus:border-brand-500 focus:ring-brand-500'
</script>

<template>
    <Head title="Facturas" />
    <AppLayout>
        <PageHeader title="Facturas" subtitle="Recibidas (gastos) y emitidas, con archivo en Drive.">
            <template #actions>
                <div class="inline-flex rounded-lg border border-ink-200 bg-white p-0.5">
                    <button v-for="opt in [{ value: null, label: 'Todas' }, ...directions]" :key="opt.value ?? 'all'"
                        class="rounded-md px-3 py-1.5 text-sm font-medium transition"
                        :class="direction === opt.value ? 'bg-brand-600 text-white' : 'text-ink-600 hover:bg-ink-100'"
                        @click="filterDirection(opt.value)">{{ opt.label }}</button>
                </div>
            </template>
        </PageHeader>

        <!-- Totales -->
        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="card p-4">
                <p class="section-title">Gastos (recibidas)</p>
                <p class="mt-1 text-2xl font-bold text-brand-600">{{ eur(totals.received) }}</p>
            </div>
            <div class="card p-4">
                <p class="section-title">Emitidas</p>
                <p class="mt-1 text-2xl font-bold text-emerald-600">{{ eur(totals.issued) }}</p>
            </div>
            <div class="card p-4">
                <p class="section-title">Facturas</p>
                <p class="mt-1 text-2xl font-bold text-ink-800">{{ totals.count }}</p>
            </div>
        </div>

        <!-- Subida masiva -->
        <div v-if="can('invoices.manage')" class="mb-6">
            <div class="mb-2 flex items-center gap-3 text-sm">
                <span class="text-ink-600">Nuevas facturas como:</span>
                <select v-model="uploadDirection" class="input w-auto py-1 text-sm">
                    <option v-for="d in directions" :key="d.value" :value="d.value">{{ d.label }}</option>
                </select>
            </div>
            <div class="flex flex-col items-center justify-center rounded-xl border-2 border-dashed p-8 text-center text-sm transition"
                :class="dragging ? 'border-brand-500 bg-brand-50' : 'border-ink-300 bg-white'"
                @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false" @drop.prevent="onDrop">
                <p class="text-ink-500">Arrastra aquí tus facturas (PDF/imagen) o</p>
                <label class="btn-primary btn-sm mt-2 cursor-pointer">
                    Seleccionar ficheros
                    <input type="file" multiple class="hidden" accept=".pdf,.jpg,.jpeg,.png" @change="onPick" />
                </label>
            </div>
        </div>

        <DataTable :columns="columns" :rows="invoices" persist-key="invoices" placeholder="Buscar factura…">
            <template #cell-date="{ row }">
                <input v-if="can('invoices.manage')" type="date" :class="inp" style="width:9rem" :value="row.date"
                    @change="updateInvoice(row, { date: $event.target.value })" />
                <span v-else>{{ row.date }}</span>
            </template>
            <template #cell-direction="{ row }">
                <BadgeEstado :label="row.direction === 'issued' ? 'Emitida' : 'Recibida'" :color="row.direction === 'issued' ? 'blue' : 'gray'" />
            </template>
            <template #cell-supplier_or_client="{ row }">
                <input v-if="can('invoices.manage')" :class="inp" style="width:10rem" :value="row.supplier_or_client"
                    @change="updateInvoice(row, { supplier_or_client: $event.target.value })" />
                <span v-else>{{ row.supplier_or_client }}</span>
            </template>
            <template #cell-concept="{ row }">
                <input v-if="can('invoices.manage')" :class="inp" style="width:10rem" :value="row.concept"
                    @change="updateInvoice(row, { concept: $event.target.value })" />
                <span v-else>{{ row.concept }}</span>
            </template>
            <template #cell-amount="{ row }">
                <input v-if="can('invoices.manage')" type="number" step="0.01" :class="inp" style="width:6rem" :value="row.amount"
                    @change="updateInvoice(row, { amount: $event.target.value })" />
                <span v-else>{{ eur(row.amount) }}</span>
            </template>
            <template #cell-vat="{ row }">
                <input v-if="can('invoices.manage')" type="number" step="0.01" :class="inp" style="width:5rem" :value="row.vat"
                    @change="updateInvoice(row, { vat: $event.target.value })" />
                <span v-else>{{ eur(row.vat) }}</span>
            </template>
            <template #cell-total="{ row }"><span class="font-medium text-ink-800">{{ eur(rowTotal(row)) }}</span></template>
            <template #cell-category="{ row }">
                <select v-if="can('invoices.manage')" :class="inp" :value="row.category"
                    @change="updateInvoice(row, { category: $event.target.value })">
                    <option v-for="c in categories" :key="c.value" :value="c.value">{{ c.label }}</option>
                </select>
                <span v-else>{{ row.category_label }}</span>
            </template>
            <template #actions="{ row }">
                <div class="flex items-center justify-end gap-2">
                    <a v-if="row.view_url" :href="row.view_url" target="_blank" class="text-xs font-medium text-brand-700 hover:underline">Ver</a>
                    <button v-if="can('invoices.manage') && row.direction === 'issued'" class="btn-secondary btn-sm" @click="generatePdf(row)">PDF</button>
                    <ConfirmButton v-if="can('invoices.manage')" message="¿Eliminar esta factura?" confirm-label="Eliminar" @confirm="destroyInvoice(row)">
                        <span class="rounded-md bg-brand-50 px-2 py-1 text-xs font-semibold text-brand-700 hover:bg-brand-100">Eliminar</span>
                    </ConfirmButton>
                </div>
            </template>
        </DataTable>
    </AppLayout>
</template>
