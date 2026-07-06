<script setup>
import { ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import DataTable from '@/Components/Shared/DataTable.vue'
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

const columns = [
    { key: 'date', label: 'Fecha', sortable: true },
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

function onDrop(e) {
    dragging.value = false
    const files = [...e.dataTransfer.files]
    if (!files.length) return
    uploadFiles(files)
}

function onPick(e) {
    const files = [...e.target.files]
    if (files.length) uploadFiles(files)
    e.target.value = ''
}

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
        preserveScroll: true,
        onSuccess: () => toast.success('Factura actualizada.'),
    })
}

function destroyInvoice(row) {
    router.delete(route('invoices.destroy', row.id), {
        preserveScroll: true,
        onSuccess: () => toast.success('Factura eliminada.'),
    })
}

function generatePdf(row) {
    router.post(route('invoices.generate-pdf', row.id), {}, {
        preserveScroll: true,
        onSuccess: () => toast.success('PDF generado y guardado en Drive.'),
    })
}
</script>

<template>
    <Head title="Facturas" />
    <AppLayout>
        <PageHeader title="Facturas" subtitle="Recibidas (gastos) y emitidas, con archivo en Drive.">
            <template #actions>
                <div class="flex gap-2">
                    <button
                        v-for="opt in [{ value: null, label: 'Todas' }, ...directions]"
                        :key="opt.value ?? 'all'"
                        class="rounded-full border px-3 py-1 text-sm"
                        :class="direction === opt.value ? 'border-emerald-600 bg-emerald-50 text-emerald-700' : 'border-slate-300 text-slate-600'"
                        @click="filterDirection(opt.value)"
                    >
                        {{ opt.label }}
                    </button>
                </div>
            </template>
        </PageHeader>

        <div v-if="can('invoices.manage')" class="mb-6">
            <div class="mb-2 flex items-center gap-3 text-sm">
                <span class="text-slate-600">Nuevas facturas como:</span>
                <select v-model="uploadDirection" class="rounded-md border-slate-300 text-sm">
                    <option v-for="d in directions" :key="d.value" :value="d.value">{{ d.label }}</option>
                </select>
            </div>
            <div
                class="flex flex-col items-center justify-center rounded-lg border-2 border-dashed p-8 text-center text-sm transition"
                :class="dragging ? 'border-emerald-500 bg-emerald-50' : 'border-slate-300 bg-white'"
                @dragover.prevent="dragging = true"
                @dragleave.prevent="dragging = false"
                @drop.prevent="onDrop"
            >
                <p class="text-slate-500">Arrastra aquí tus facturas (PDF/imagen) o</p>
                <label class="mt-2 cursor-pointer rounded-md bg-emerald-600 px-4 py-2 text-xs font-semibold text-white hover:bg-emerald-700">
                    Seleccionar ficheros
                    <input type="file" multiple class="hidden" accept=".pdf,.jpg,.jpeg,.png" @change="onPick" />
                </label>
            </div>
        </div>

        <DataTable :columns="columns" :rows="invoices" persist-key="invoices" placeholder="Buscar factura…">
            <template #cell-date="{ row }">
                <input
                    v-if="can('invoices.manage')"
                    type="date"
                    class="w-36 rounded border-slate-200 text-xs"
                    :value="row.date"
                    @change="updateInvoice(row, { date: $event.target.value })"
                />
                <span v-else>{{ row.date }}</span>
            </template>
            <template #cell-supplier_or_client="{ row }">
                <input
                    v-if="can('invoices.manage')"
                    class="w-40 rounded border-slate-200 text-xs"
                    :value="row.supplier_or_client"
                    @change="updateInvoice(row, { supplier_or_client: $event.target.value })"
                />
                <span v-else>{{ row.supplier_or_client }}</span>
            </template>
            <template #cell-concept="{ row }">
                <input
                    v-if="can('invoices.manage')"
                    class="w-40 rounded border-slate-200 text-xs"
                    :value="row.concept"
                    @change="updateInvoice(row, { concept: $event.target.value })"
                />
                <span v-else>{{ row.concept }}</span>
            </template>
            <template #cell-amount="{ row }">
                <input
                    v-if="can('invoices.manage')"
                    type="number" step="0.01"
                    class="w-24 rounded border-slate-200 text-xs"
                    :value="row.amount"
                    @change="updateInvoice(row, { amount: $event.target.value })"
                />
                <span v-else>{{ Number(row.amount).toFixed(2) }} €</span>
            </template>
            <template #cell-vat="{ row }">
                <input
                    v-if="can('invoices.manage')"
                    type="number" step="0.01"
                    class="w-20 rounded border-slate-200 text-xs"
                    :value="row.vat"
                    @change="updateInvoice(row, { vat: $event.target.value })"
                />
                <span v-else>{{ Number(row.vat).toFixed(2) }} €</span>
            </template>
            <template #cell-total="{ value }">{{ Number(value).toFixed(2) }} €</template>
            <template #cell-category="{ row }">
                <select
                    v-if="can('invoices.manage')"
                    class="rounded border-slate-200 text-xs"
                    :value="row.category"
                    @change="updateInvoice(row, { category: $event.target.value })"
                >
                    <option v-for="c in categories" :key="c.value" :value="c.value">{{ c.label }}</option>
                </select>
                <span v-else>{{ row.category_label }}</span>
            </template>
            <template #actions="{ row }">
                <div class="flex items-center justify-end gap-2">
                    <a v-if="row.view_url" :href="row.view_url" target="_blank" class="text-xs text-emerald-700 hover:underline">Ver fichero</a>
                    <button
                        v-if="can('invoices.manage') && row.direction === 'issued'"
                        class="rounded-md bg-slate-700 px-2 py-1 text-xs font-semibold text-white hover:bg-slate-800"
                        @click="generatePdf(row)"
                    >
                        Generar PDF
                    </button>
                    <ConfirmButton
                        v-if="can('invoices.manage')"
                        message="¿Eliminar esta factura?"
                        confirm-label="Eliminar"
                        @confirm="destroyInvoice(row)"
                    >
                        <span class="rounded-md bg-red-50 px-2 py-1 text-xs font-semibold text-red-700 hover:bg-red-100">Eliminar</span>
                    </ConfirmButton>
                </div>
            </template>
        </DataTable>
    </AppLayout>
</template>
