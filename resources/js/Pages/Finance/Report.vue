<script setup>
import { ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'

const props = defineProps({
    report: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
})

const from = ref(props.filters.from ?? props.report.from)
const to = ref(props.filters.to ?? props.report.to)

function applyFilters() {
    router.get(route('finance.report'), { from: from.value, to: to.value }, { preserveState: true })
}

function exportCsv() {
    window.location = route('finance.report.export', { from: from.value, to: to.value })
}
</script>

<template>
    <Head title="Informe económico" />
    <AppLayout>
        <PageHeader title="Informe económico" subtitle="Ingresos y gastos por categoría y periodo (memoria económica).">
            <template #actions>
                <button
                    class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700"
                    @click="exportCsv"
                >
                    Exportar CSV
                </button>
            </template>
        </PageHeader>

        <div class="mb-6 flex flex-wrap items-end gap-3 rounded-lg border border-slate-200 bg-white p-4">
            <div>
                <label class="block text-xs font-medium text-slate-500">Desde</label>
                <input v-model="from" type="date" class="mt-1 rounded-md border-slate-300 text-sm" />
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500">Hasta</label>
                <input v-model="to" type="date" class="mt-1 rounded-md border-slate-300 text-sm" />
            </div>
            <button class="rounded-md bg-slate-700 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800" @click="applyFilters">
                Aplicar
            </button>
        </div>

        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="rounded-lg border border-slate-200 bg-white p-4">
                <p class="text-xs text-slate-500">Ingresos</p>
                <p class="text-2xl font-bold text-brand-700">{{ Number(report.total_income).toFixed(2) }} €</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white p-4">
                <p class="text-xs text-slate-500">Gastos</p>
                <p class="text-2xl font-bold text-red-600">{{ Number(report.total_expense).toFixed(2) }} €</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white p-4">
                <p class="text-xs text-slate-500">Balance</p>
                <p class="text-2xl font-bold" :class="report.balance >= 0 ? 'text-brand-700' : 'text-red-600'">
                    {{ Number(report.balance).toFixed(2) }} €
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <div class="rounded-lg border border-slate-200 bg-white p-4">
                <h2 class="mb-3 text-sm font-semibold text-slate-700">Ingresos por categoría</h2>
                <table class="w-full text-sm">
                    <tbody>
                        <tr v-for="row in report.income" :key="row.category + row.source" class="border-b border-slate-100">
                            <td class="py-1.5 text-slate-600">{{ row.category }} <span class="text-xs text-slate-400">({{ row.source }})</span></td>
                            <td class="py-1.5 text-right font-medium">{{ Number(row.amount).toFixed(2) }} €</td>
                        </tr>
                        <tr v-if="!report.income.length">
                            <td class="py-3 text-center text-slate-400" colspan="2">Sin datos en el periodo.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-4">
                <h2 class="mb-3 text-sm font-semibold text-slate-700">Gastos por categoría</h2>
                <table class="w-full text-sm">
                    <tbody>
                        <tr v-for="row in report.expense" :key="row.category + row.source" class="border-b border-slate-100">
                            <td class="py-1.5 text-slate-600">{{ row.category }} <span class="text-xs text-slate-400">({{ row.source }})</span></td>
                            <td class="py-1.5 text-right font-medium">{{ Number(row.amount).toFixed(2) }} €</td>
                        </tr>
                        <tr v-if="!report.expense.length">
                            <td class="py-3 text-center text-slate-400" colspan="2">Sin datos en el periodo.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
