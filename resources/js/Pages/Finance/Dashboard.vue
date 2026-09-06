<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import StatCard from '@/Components/Shared/StatCard.vue';
import DashIcon from '@/Components/Shared/DashIcon.vue';
import SectionCard from '@/Components/Shared/SectionCard.vue';
import EmptyState from '@/Components/Shared/EmptyState.vue';
import { useAuth } from '@/composables/useAuth';

const props = defineProps({
    stats: Object,
    budgets: Array,
    chartData: Object,
});

const { user } = useAuth();

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(value);
};

import { Bar } from 'vue-chartjs'
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale } from 'chart.js'
ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale)

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { position: 'bottom' }
    }
}

const chartDataConfig = computed(() => {
    return {
        labels: props.chartData?.labels || [],
        datasets: [
            {
                label: 'Ingresos',
                backgroundColor: '#10b981', // emerald-500
                data: props.chartData?.income || []
            },
            {
                label: 'Gastos',
                backgroundColor: '#f43f5e', // rose-500
                data: props.chartData?.expense || []
            }
        ]
    }
})
</script>

<template>
    <Head title="Tesorería" />

    <AppLayout>
        <div class="space-y-6">
            <PageHeader
                title="Panel de Tesorería"
                subtitle="Resumen financiero mensual y estado de los presupuestos."
                icon="chart"
            />

            <!-- Cifras principales -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <StatCard
                    label="Fondo total"
                    :value="formatCurrency(stats.total_balance)"
                    hint="Balance acumulado de la entidad"
                    :tone="stats.total_balance >= 0 ? 'positive' : 'danger'"
                    icon="euro"
                />

                <!-- El balance del mes muestra dos cifras, así que va a medida -->
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-2xs sm:p-5">
                    <div class="flex items-start gap-3">
                        <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-sky-50 text-sky-700">
                            <DashIcon name="calendar" class="h-4 w-4" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Balance del mes</p>
                            <div class="mt-1.5 flex items-baseline justify-between gap-3">
                                <span>
                                    <span class="block text-xl font-extrabold text-emerald-600">+ {{ formatCurrency(stats.monthly_income) }}</span>
                                    <span class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Ingresos</span>
                                </span>
                                <span class="text-right">
                                    <span class="block text-xl font-extrabold text-rose-600">- {{ formatCurrency(stats.monthly_expense) }}</span>
                                    <span class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Gastos</span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Facturas: dos contadores -->
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-2xs sm:p-5">
                    <div class="flex items-start gap-3">
                        <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                            <DashIcon name="receipt" class="h-4 w-4" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Facturas a revisar</p>
                            <div class="mt-1.5 flex items-center gap-5">
                                <span>
                                    <span class="block text-2xl font-extrabold text-amber-600">{{ stats.pending_invoices }}</span>
                                    <span class="text-xs font-semibold text-slate-500">Pendientes</span>
                                </span>
                                <span class="h-8 w-px bg-slate-200"></span>
                                <span>
                                    <span class="block text-2xl font-extrabold text-sky-600">{{ stats.submitted_invoices }}</span>
                                    <span class="text-xs font-semibold text-slate-500">Entregadas MSC</span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gráfico de balance mensual -->
            <SectionCard title="Evolución de ingresos y gastos" subtitle="Últimos 6 meses" icon="chart" tone="info">
                <div class="h-72 w-full">
                    <Bar v-if="chartDataConfig.labels.length" :data="chartDataConfig" :options="chartOptions" />
                </div>
            </SectionCard>

            <SectionCard title="Presupuestos activos" icon="target" tone="brand">
                <EmptyState
                    v-if="budgets.length === 0"
                    title="No hay presupuestos activos"
                    description="Los presupuestos se crean desde la ficha de cada evento."
                    icon="target"
                />

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                    <Link v-for="budget in budgets" :key="budget.id" :href="route('budgets.show', budget.id)" class="block rounded-xl border border-slate-200/80 bg-slate-50/50 p-5 shadow-sm transition hover:shadow-md hover:border-brand-300">
                        <h4 class="font-extrabold text-lg text-slate-800 mb-4">{{ budget.name }}</h4>
                        
                        <div class="grid grid-cols-2 gap-6 text-sm">
                            <!-- Ingresos -->
                            <div class="bg-white rounded-lg p-3 border border-slate-100 shadow-2xs">
                                <p class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2 border-b border-slate-100 pb-1">Ingresos</p>
                                <div class="space-y-1">
                                    <div class="flex justify-between items-center text-slate-600">
                                        <span>Previsto:</span>
                                        <span class="font-medium">{{ formatCurrency(budget.expected_income) }}</span>
                                    </div>
                                    <div class="flex justify-between items-center text-emerald-700">
                                        <span class="font-bold">Real:</span>
                                        <span class="font-bold">{{ formatCurrency(budget.real_income) }}</span>
                                    </div>
                                </div>
                                <div class="mt-2 w-full bg-slate-200 rounded-full h-1.5">
                                    <div class="bg-emerald-500 h-1.5 rounded-full" :style="{ width: Math.min(100, (budget.real_income / (budget.expected_income || 1)) * 100) + '%' }"></div>
                                </div>
                            </div>
                            <!-- Gastos -->
                            <div class="bg-white rounded-lg p-3 border border-slate-100 shadow-2xs">
                                <p class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2 border-b border-slate-100 pb-1">Gastos</p>
                                <div class="space-y-1">
                                    <div class="flex justify-between items-center text-slate-600">
                                        <span>Previsto:</span>
                                        <span class="font-medium">{{ formatCurrency(budget.expected_expense) }}</span>
                                    </div>
                                    <div class="flex justify-between items-center text-rose-700">
                                        <span class="font-bold">Real:</span>
                                        <span class="font-bold">{{ formatCurrency(budget.real_expense) }}</span>
                                    </div>
                                </div>
                                <div class="mt-2 w-full bg-slate-200 rounded-full h-1.5">
                                    <div class="bg-rose-500 h-1.5 rounded-full" :style="{ width: Math.min(100, (budget.real_expense / (budget.expected_expense || 1)) * 100) + '%' }"></div>
                                </div>
                            </div>
                        </div>
                    </Link>
                </div>
            </SectionCard>
        </div>
    </AppLayout>
</template>
