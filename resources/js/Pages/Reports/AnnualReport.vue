<script setup>
import { ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import AppButton from '@/Components/Shared/AppButton.vue'
import StatCard from '@/Components/Shared/StatCard.vue'
import SectionCard from '@/Components/Shared/SectionCard.vue'
import EmptyState from '@/Components/Shared/EmptyState.vue'

const props = defineProps({
    report: { type: Object, required: true },
    year: { type: String, required: true },
    years: { type: Array, default: () => [] },
})

const selectedYear = ref(props.year)

function changeYear() {
    router.get(route('reports.annual'), { year: selectedYear.value }, { preserveState: true, replace: true })
}
function downloadPdf() {
    window.open(route('reports.annual.pdf', { year: selectedYear.value }), '_blank')
}
</script>

<template>
    <Head title="Memoria del curso" />
    <AppLayout>
        <PageHeader title="Memoria del curso"
            subtitle="Cierre de curso: progreso de los planes de rama, eventos realizados, asistencia y censo."
            icon="file">
            <template #actions>
                <select v-model="selectedYear" class="input py-1.5 text-sm" @change="changeYear">
                    <option v-for="y in years" :key="y" :value="y">Curso {{ y }}</option>
                </select>
                <AppButton size="sm" icon="download" @click="downloadPdf">Descargar PDF</AppButton>
            </template>
        </PageHeader>

        <div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <StatCard label="Eventos realizados" :value="report.events_total" icon="calendar" tone="brand"
                :hint="`del ${report.from} al ${report.to}`" />
            <StatCard label="Planes de rama" :value="report.plans.length" icon="target" />
            <StatCard label="Miembros activos" :value="report.census.reduce((s, r) => s + r.count, 0)" icon="users" tone="positive" />
        </div>

        <div v-if="report.retention" class="mb-6 flex flex-wrap items-center gap-6 rounded-xl border border-ink-200 bg-white p-4 text-sm">
            <span class="font-semibold uppercase tracking-wide text-ink-400">Altas y bajas del curso</span>
            <span><span class="text-lg font-bold text-emerald-600">+{{ report.retention.altas }}</span> altas</span>
            <span><span class="text-lg font-bold text-rose-600">−{{ report.retention.bajas }}</span> bajas</span>
            <span class="text-ink-500">
                Balance neto
                <span class="font-bold" :class="report.retention.net < 0 ? 'text-rose-600' : 'text-emerald-600'">
                    {{ report.retention.net > 0 ? '+' : '' }}{{ report.retention.net }}
                </span>
            </span>
        </div>

        <EmptyState v-if="!report.plans.length" title="Sin planes de rama para este curso"
            description="Crea un plan de rama con objetivos para que la memoria tenga contenido." />

        <div v-else class="space-y-5">
            <SectionCard v-for="plan in report.plans" :key="plan.branch" :title="plan.branch_label" icon="target">
                <p v-if="plan.description" class="mb-3 text-sm text-ink-500">{{ plan.description }}</p>

                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div>
                        <p class="text-2xl font-bold text-emerald-600">{{ plan.objectives.percentage }}%</p>
                        <p class="text-xs text-ink-500">objetivos logrados ({{ plan.objectives.logrado }}/{{ plan.objectives.total }})</p>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-ink-800">{{ plan.objectives.en_curso }}</p>
                        <p class="text-xs text-ink-500">en curso · {{ plan.objectives.pendiente }} pendientes</p>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-ink-800">{{ plan.events_count }}</p>
                        <p class="text-xs text-ink-500">eventos de la rama</p>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-ink-800">
                            {{ plan.attendance.avg_present !== null ? plan.attendance.avg_present + '%' : '—' }}
                        </p>
                        <p class="text-xs text-ink-500">asistencia media ({{ plan.attendance.sessions }} reuniones)</p>
                    </div>
                </div>

                <div class="mt-3 h-2 overflow-hidden rounded-full bg-ink-100">
                    <div class="h-full rounded-full bg-emerald-500" :style="{ width: `${plan.objectives.percentage}%` }" />
                </div>

                <div v-if="plan.objectives.by_area.length" class="mt-4 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs uppercase text-ink-400">
                                <th class="py-1.5 pr-4">Ámbito de desarrollo</th>
                                <th class="py-1.5 pr-4">Logrados</th>
                                <th class="py-1.5 pr-4">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-ink-100">
                            <tr v-for="a in plan.objectives.by_area" :key="a.area">
                                <td class="py-1.5 pr-4 text-ink-700">{{ a.area }}</td>
                                <td class="py-1.5 pr-4">{{ a.logrado }}</td>
                                <td class="py-1.5 pr-4">{{ a.total }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <details v-if="plan.events.length" class="mt-3">
                    <summary class="cursor-pointer text-sm font-semibold text-brand-600">Ver los {{ plan.events.length }} eventos de la rama</summary>
                    <ul class="mt-2 divide-y divide-ink-100 text-sm">
                        <li v-for="(e, i) in plan.events" :key="i" class="flex justify-between py-1.5">
                            <span class="text-ink-700">{{ e.title }}</span>
                            <span class="text-ink-400">{{ e.type }} · {{ e.date }}</span>
                        </li>
                    </ul>
                </details>
            </SectionCard>
        </div>
    </AppLayout>
</template>
