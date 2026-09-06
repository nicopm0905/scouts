<script setup>
import { ref, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import DataTable from '@/Components/Shared/DataTable.vue'
import BadgeEstado from '@/Components/Shared/BadgeEstado.vue'
import ConfirmButton from '@/Components/Shared/ConfirmButton.vue'
import StatCard from '@/Components/Shared/StatCard.vue'
import AppButton from '@/Components/Shared/AppButton.vue'
import SectionCard from '@/Components/Shared/SectionCard.vue'
import FilterSelect from '@/Components/Shared/FilterSelect.vue'
import { useToast } from '@/composables/useToast'
import { useAuth } from '@/composables/useAuth'

const props = defineProps({
    invoices: { type: Array, default: () => [] },
    direction: { type: String, default: null },
    branch: { type: String, default: null },
    categories: { type: Array, default: () => [] },
    directions: { type: Array, default: () => [] },
    branches: { type: Array, default: () => [] },
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
    { key: 'branch', label: 'Rama' },
    { key: 'category', label: 'Categoría' },
]

function filterInvoices(dir, br) {
    const params = {}
    if (dir) params.direction = dir
    if (br) params.branch = br
    router.get(route('invoices.index'), params, { preserveState: true })
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

const inp = 'w-full bg-transparent border-transparent hover:bg-slate-50 focus:bg-white focus:border-brand-500 focus:ring-1 focus:ring-brand-500 rounded text-sm px-2 py-1 transition-colors cursor-pointer focus:cursor-text truncate'

</script>

<template>
    <Head title="Facturas" />
    <AppLayout>
        <PageHeader title="Facturas" subtitle="Recibidas (gastos) y emitidas, con archivo en Drive." icon="receipt" />

        <!-- Totales -->
        <div class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <StatCard label="Gastos (recibidas)" :value="eur(totals.received)" tone="danger" icon="download" />
            <StatCard label="Emitidas" :value="eur(totals.issued)" tone="positive" icon="upload" />
            <StatCard label="Facturas" :value="totals.count" icon="receipt" />
        </div>

        <!-- Subida masiva -->
        <SectionCard
            v-if="can('invoices.manage')"
            class="mb-5"
            title="Añadir facturas"
            subtitle="Arrastra los ficheros o selecciónalos; se archivan en Drive."
            icon="upload"
            tone="brand"
        >
            <template #actions>
                <FilterSelect v-model="uploadDirection" :options="directions" label="Registrar como" />
            </template>

            <div
                class="flex flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed p-8 text-center text-sm transition-colors duration-200"
                :class="dragging ? 'border-brand-400 bg-brand-50' : 'border-slate-200 bg-slate-50/60'"
                @dragover.prevent="dragging = true"
                @dragleave.prevent="dragging = false"
                @drop.prevent="onDrop"
            >
                <p class="font-medium text-slate-500">Arrastra aquí tus facturas en PDF o imagen</p>
                <label class="btn-primary btn-sm cursor-pointer">
                    Seleccionar ficheros
                    <input type="file" multiple class="hidden" accept=".pdf,.jpg,.jpeg,.png" @change="onPick" />
                </label>
            </div>
        </SectionCard>

        <DataTable :columns="columns" :rows="invoices" persist-key="invoices" placeholder="Buscar factura…" empty-title="No hay facturas">
            <template #filters>
                <FilterSelect
                    :model-value="direction || ''"
                    :options="[{ value: '', label: 'Todas las direcciones' }, ...directions]"
                    @update:model-value="filterInvoices($event || null, branch)"
                />
                <FilterSelect
                    :model-value="branch || ''"
                    :options="[{ value: '', label: 'Todas las ramas' }, ...branches]"
                    @update:model-value="filterInvoices(direction, $event || null)"
                />
            </template>
            <template #cell-date="{ row }">
                <input v-if="can('invoices.manage')" type="date" :class="inp" style="min-width: 110px" :value="row.date"
                    @change="updateInvoice(row, { date: $event.target.value })" />
                <span v-else>{{ row.date }}</span>
            </template>
            <template #cell-direction="{ row }">
                <BadgeEstado :label="row.direction === 'issued' ? 'Emitida' : 'Recibida'" :color="row.direction === 'issued' ? 'blue' : 'gray'" />
            </template>
            <template #cell-supplier_or_client="{ row }">
                <input v-if="can('invoices.manage')" :class="inp" style="min-width: 140px" :value="row.supplier_or_client"
                    @change="updateInvoice(row, { supplier_or_client: $event.target.value })" placeholder="Proveedor/Cliente" />
                <span v-else>{{ row.supplier_or_client }}</span>
            </template>
            <template #cell-concept="{ row }">
                <input v-if="can('invoices.manage')" :class="inp" style="min-width: 150px" :value="row.concept"
                    @change="updateInvoice(row, { concept: $event.target.value })" placeholder="Concepto" />
                <span v-else>{{ row.concept }}</span>
            </template>
            <template #cell-amount="{ row }">
                <input v-if="can('invoices.manage')" type="number" step="0.01" :class="inp" style="min-width: 80px" :value="row.amount"
                    @change="updateInvoice(row, { amount: $event.target.value })" />
                <span v-else>{{ eur(row.amount) }}</span>
            </template>
            <template #cell-vat="{ row }">
                <input v-if="can('invoices.manage')" type="number" step="0.01" :class="inp" style="min-width: 80px" :value="row.vat"
                    @change="updateInvoice(row, { vat: $event.target.value })" />
                <span v-else>{{ eur(row.vat) }}</span>
            </template>
            <template #cell-total="{ row }"><span class="font-medium text-ink-800 whitespace-nowrap">{{ eur(rowTotal(row)) }}</span></template>
            <template #cell-branch="{ row }">
                <select v-if="can('invoices.manage')" :class="[inp, 'cursor-pointer']" style="min-width: 120px" :value="row.branch || ''"
                    @change="updateInvoice(row, { branch: $event.target.value || null })">
                    <option value="">(Ninguna)</option>
                    <option v-for="b in branches" :key="b.value" :value="b.value">{{ b.label }}</option>
                </select>
                <span v-else>{{ row.branch_label || '-' }}</span>
            </template>
            <template #cell-category="{ row }">
                <select v-if="can('invoices.manage')" :class="[inp, 'cursor-pointer']" style="min-width: 150px" :value="row.category"
                    @change="updateInvoice(row, { category: $event.target.value })">
                    <option v-for="c in categories" :key="c.value" :value="c.value">{{ c.label }}</option>
                </select>
                <span v-else>{{ row.category_label }}</span>
            </template>
            <template #actions="{ row }">
                <div class="flex items-center justify-end gap-1.5">
                    <AppButton v-if="row.view_url" :href="row.view_url" external size="sm" icon="eye">Ver</AppButton>
                    <AppButton
                        v-if="can('invoices.manage') && row.direction === 'issued'"
                        size="sm"
                        icon="document"
                        @click="generatePdf(row)"
                    >
                        PDF
                    </AppButton>
                    <ConfirmButton
                        v-if="can('invoices.manage')"
                        message="¿Eliminar esta factura?"
                        confirm-label="Eliminar"
                        @confirm="destroyInvoice(row)"
                    >
                        <span class="inline-flex items-center rounded-lg px-2.5 py-1.5 text-xs font-semibold text-rose-600 transition-colors hover:bg-rose-50">
                            Eliminar
                        </span>
                    </ConfirmButton>
                </div>
            </template>
        </DataTable>
    </AppLayout>
</template>
