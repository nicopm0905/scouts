<script setup>
import { ref, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import AppButton from '@/Components/Shared/AppButton.vue'

const props = defineProps({
    report: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
})

const from = ref(props.filters.from ?? props.report.from)
const to = ref(props.filters.to ?? props.report.to)

const eur = (n) => Number(n ?? 0).toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' €'

function applyFilters() {
    router.get(route('finance.report'), { from: from.value, to: to.value }, { preserveState: true })
}
function exportCsv() {
    window.location = route('finance.report.export', { from: from.value, to: to.value })
}

// Escalado de la gráfica mensual
const monthly = computed(() => props.report.monthly ?? [])
const maxMonthly = computed(() => Math.max(1, ...monthly.value.flatMap((m) => [m.income, m.expense])))
const barH = (v) => `${Math.round((v / maxMonthly.value) * 100)}%`

// Desglose por categoría con barras horizontales
const maxIncome = computed(() => Math.max(1, ...props.report.income.map((r) => r.amount)))
const maxExpense = computed(() => Math.max(1, ...props.report.expense.map((r) => r.amount)))
</script>

<template>
    <Head title="Informe económico" />
    <AppLayout>
        <PageHeader title="Informe económico" subtitle="Ingresos y gastos por categoría y periodo (base de la memoria económica anual)." icon="chart">
            <template #actions>
                <AppButton size="sm" icon="download" @click="exportCsv">Exportar CSV</AppButton>
            </template>
        </PageHeader>

        <!-- Filtro de periodo -->
        <div class="mb-6 flex flex-wrap items-end gap-3 card p-4">
            <div>
                <label class="text-xs font-medium text-ink-500">Desde</label>
                <input v-model="from" type="date" class="input mt-1" />
            </div>
            <div>
                <label class="text-xs font-medium text-ink-500">Hasta</label>
                <input v-model="to" type="date" class="input mt-1" />
            </div>
            <button class="btn-primary" @click="applyFilters">Aplicar</button>
        </div>

        <!-- KPIs -->
        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="card p-4">
                <p class="section-title">Ingresos</p>
                <p class="mt-1 text-2xl font-bold text-emerald-600">{{ eur(report.total_income) }}</p>
            </div>
            <div class="card p-4">
                <p class="section-title">Gastos</p>
                <p class="mt-1 text-2xl font-bold text-brand-600">{{ eur(report.total_expense) }}</p>
            </div>
            <div class="card p-4">
                <p class="section-title">Balance</p>
                <p class="mt-1 text-2xl font-bold" :class="report.balance >= 0 ? 'text-emerald-600' : 'text-brand-600'">
                    {{ eur(report.balance) }}
                </p>
            </div>
        </div>

        <!-- Gráfica mensual -->
        <div class="mb-6 card-pad">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="section-title">Evolución mensual</h2>
                <div class="flex items-center gap-4 text-xs text-ink-500">
                    <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-emerald-500" /> Ingresos</span>
                    <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-brand-500" /> Gastos</span>
                </div>
            </div>

            <div v-if="monthly.length" class="flex h-56 items-end gap-2 overflow-x-auto pb-1">
                <div v-for="m in monthly" :key="m.label" class="flex min-w-[38px] flex-1 flex-col items-center gap-1">
                    <div class="flex h-44 w-full items-end justify-center gap-1">
                        <div class="w-1/2 max-w-[16px] rounded-t bg-emerald-500 transition-all hover:bg-emerald-600"
                            :style="{ height: barH(m.income) }" :title="`Ingresos: ${eur(m.income)}`" />
                        <div class="w-1/2 max-w-[16px] rounded-t bg-brand-500 transition-all hover:bg-brand-600"
                            :style="{ height: barH(m.expense) }" :title="`Gastos: ${eur(m.expense)}`" />
                    </div>
                    <span class="whitespace-nowrap text-[10px] capitalize text-ink-400">{{ m.label }}</span>
                </div>
            </div>
            <p v-else class="py-8 text-center text-sm text-ink-400">Sin movimientos en el periodo.</p>
        </div>

        <!-- Desglose por categoría -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <div class="card-pad">
                <h2 class="section-title mb-4">Ingresos por categoría</h2>
                <div v-if="report.income.length" class="space-y-3">
                    <div v-for="row in report.income" :key="row.category + row.source">
                        <div class="mb-1 flex items-center justify-between text-sm">
                            <span class="text-ink-700">{{ row.category }} <span class="text-xs text-ink-400">· {{ row.source }}</span></span>
                            <span class="font-medium text-ink-800">{{ eur(row.amount) }}</span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-ink-100">
                            <div class="h-full rounded-full bg-emerald-500" :style="{ width: (row.amount / maxIncome * 100) + '%' }" />
                        </div>
                    </div>
                </div>
                <p v-else class="py-6 text-center text-sm text-ink-400">Sin ingresos en el periodo.</p>
            </div>

            <div class="card-pad">
                <h2 class="section-title mb-4">Gastos por categoría</h2>
                <div v-if="report.expense.length" class="space-y-3">
                    <div v-for="row in report.expense" :key="row.category + row.source">
                        <div class="mb-1 flex items-center justify-between text-sm">
                            <span class="text-ink-700">{{ row.category }} <span class="text-xs text-ink-400">· {{ row.source }}</span></span>
                            <span class="font-medium text-ink-800">{{ eur(row.amount) }}</span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-ink-100">
                            <div class="h-full rounded-full bg-brand-500" :style="{ width: (row.amount / maxExpense * 100) + '%' }" />
                        </div>
                    </div>
                </div>
                <p v-else class="py-6 text-center text-sm text-ink-400">Sin gastos en el periodo.</p>
            </div>
        </div>
    </AppLayout>
</template>
