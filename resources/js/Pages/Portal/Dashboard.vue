<script setup>
import { Head, Link } from '@inertiajs/vue3'
import PortalLayout from '@/Layouts/PortalLayout.vue'

defineProps({
    children: { type: Array, default: () => [] },
    pendingPayments: { type: Array, default: () => [] },
    pendingTotal: { type: Number, default: 0 },
    upcomingEvents: { type: Array, default: () => [] },
})

const eur = (n) => new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(n)
</script>

<template>
    <Head title="Portal de familias" />
    <PortalLayout>
        <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Hola</h1>
        <p class="mt-1 text-slate-500">Un resumen de lo que necesita tu atención.</p>

        <div class="mt-6 grid gap-4 sm:grid-cols-2">
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <p class="text-sm font-semibold text-slate-500">Pendiente de pago</p>
                <p class="mt-1 text-3xl font-extrabold" :class="pendingTotal > 0 ? 'text-amber-600' : 'text-emerald-600'">
                    {{ eur(pendingTotal) }}
                </p>
                <Link href="/portal/pagos" class="mt-2 inline-block text-sm font-semibold text-brand-700 hover:underline">
                    Ver pagos
                </Link>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <p class="text-sm font-semibold text-slate-500">Tus scouts</p>
                <ul class="mt-2 space-y-1">
                    <li v-for="c in children" :key="c.id" class="text-sm">
                        <Link :href="`/portal/scouts/${c.id}`" class="font-semibold text-slate-800 hover:underline">{{ c.name }}</Link>
                        <span class="text-slate-400"> · {{ c.branch }}</span>
                    </li>
                    <li v-if="!children.length" class="text-sm text-slate-400">Sin scouts vinculados a tu cuenta.</li>
                </ul>
            </div>
        </div>

        <section class="mt-8">
            <h2 class="text-lg font-bold text-slate-800">Pagos pendientes</h2>
            <div v-if="pendingPayments.length" class="mt-3 overflow-hidden rounded-xl border border-slate-200 bg-white">
                <table class="w-full text-sm">
                    <tbody>
                        <tr v-for="p in pendingPayments" :key="p.id" class="border-b border-slate-100 last:border-0">
                            <td class="px-4 py-3">
                                <span class="font-semibold text-slate-800">{{ p.concept }}</span>
                                <span class="block text-xs text-slate-400">{{ p.child }}<span v-if="p.due_date"> · vence {{ p.due_date }}</span></span>
                            </td>
                            <td class="px-4 py-3 text-right font-semibold tabular-nums text-slate-800">{{ eur(p.amount) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p v-else class="mt-3 rounded-xl border border-dashed border-slate-200 bg-white px-4 py-6 text-center text-sm text-slate-400">
                No tienes pagos pendientes. ¡Gracias!
            </p>
        </section>

        <section class="mt-8">
            <h2 class="text-lg font-bold text-slate-800">Próximas actividades</h2>
            <div v-if="upcomingEvents.length" class="mt-3 space-y-2">
                <Link
                    v-for="e in upcomingEvents"
                    :key="e.id"
                    href="/portal/calendario"
                    class="flex items-center justify-between rounded-xl border border-slate-200 bg-white px-4 py-3 hover:border-brand-300"
                >
                    <span>
                        <span class="font-semibold text-slate-800">{{ e.title }}</span>
                        <span class="block text-xs text-slate-400">{{ e.type }}<span v-if="e.location"> · {{ e.location }}</span></span>
                    </span>
                    <span class="text-sm font-semibold text-slate-500">{{ e.start_at }}</span>
                </Link>
            </div>
            <p v-else class="mt-3 rounded-xl border border-dashed border-slate-200 bg-white px-4 py-6 text-center text-sm text-slate-400">
                No hay actividades programadas.
            </p>
        </section>
    </PortalLayout>
</template>
