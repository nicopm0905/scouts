<script setup>
import { Head } from '@inertiajs/vue3'
import PortalLayout from '@/Layouts/PortalLayout.vue'

defineProps({
    pending: { type: Array, default: () => [] },
    settled: { type: Array, default: () => [] },
    pendingTotal: { type: Number, default: 0 },
    paymentInfo: { type: Object, default: () => ({}) },
})

const eur = (n) => new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(n)
</script>

<template>
    <Head title="Pagos" />
    <PortalLayout>
        <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Pagos</h1>
        <p class="mt-1 text-slate-500">Cuotas y salidas de tus scouts.</p>

        <div class="mt-6 rounded-xl border border-slate-200 bg-white p-5">
            <div class="flex items-baseline justify-between">
                <p class="text-sm font-semibold text-slate-500">Total pendiente</p>
                <p class="text-2xl font-extrabold tabular-nums" :class="pendingTotal > 0 ? 'text-amber-600' : 'text-emerald-600'">
                    {{ eur(pendingTotal) }}
                </p>
            </div>
            <p v-if="pendingTotal > 0" class="mt-3 border-t border-slate-100 pt-3 text-sm text-slate-500">
                Para regularizar un pago, contacta con tesorería
                <span v-if="paymentInfo.email">en <a :href="`mailto:${paymentInfo.email}`" class="font-semibold text-brand-700">{{ paymentInfo.email }}</a></span>
                indicando el concepto y el nombre del scout.
            </p>
        </div>

        <section class="mt-8">
            <h2 class="text-lg font-bold text-slate-800">Pendientes</h2>
            <div v-if="pending.length" class="mt-3 overflow-x-auto rounded-xl border border-slate-200 bg-white">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-2">Concepto</th>
                            <th class="px-4 py-2">Scout</th>
                            <th class="px-4 py-2">Vencimiento</th>
                            <th class="px-4 py-2 text-right">Importe</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="p in pending" :key="p.id" class="border-t border-slate-100">
                            <td class="px-4 py-3 font-semibold text-slate-800">{{ p.concept }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ p.child }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ p.due_date ?? '—' }}</td>
                            <td class="px-4 py-3 text-right font-semibold tabular-nums text-slate-800">{{ eur(p.amount) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p v-else class="mt-3 rounded-xl border border-dashed border-slate-200 bg-white px-4 py-6 text-center text-sm text-slate-400">
                Nada pendiente.
            </p>
        </section>

        <section class="mt-8">
            <h2 class="text-lg font-bold text-slate-800">Historial</h2>
            <div v-if="settled.length" class="mt-3 overflow-x-auto rounded-xl border border-slate-200 bg-white">
                <table class="w-full text-sm">
                    <tbody>
                        <tr v-for="p in settled" :key="p.id" class="border-t border-slate-100 first:border-0">
                            <td class="px-4 py-3">
                                <span class="font-semibold text-slate-700">{{ p.concept }}</span>
                                <span class="block text-xs text-slate-400">{{ p.child }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">
                                    {{ p.status_label }}<span v-if="p.paid_at"> · {{ p.paid_at }}</span>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums text-slate-500">{{ eur(p.amount) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p v-else class="mt-3 text-sm text-slate-400">Todavía no hay pagos registrados.</p>
        </section>
    </PortalLayout>
</template>
