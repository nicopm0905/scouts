<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import BadgeEstado from '@/Components/Shared/BadgeEstado.vue'
import { useAuth } from '@/composables/useAuth'

const props = defineProps({
    semaphore: { type: Object, default: () => ({}) },
    upcomingEvents: { type: Array, default: () => [] },
    recentCharges: { type: Array, default: () => [] },
    inventoryAlerts: { type: Object, default: () => ({}) },
})

const { user } = useAuth()

// Tarjetas del semáforo documental. color: verde si 0, ámbar/rojo si hay pendientes.
function card(label, value, hint, dangerColor = 'red') {
    return {
        label,
        value,
        hint,
        color: value > 0 ? dangerColor : 'green',
    }
}

const semaphoreCards = [
    card('Cuotas/cobros pendientes', props.semaphore.pending_charges ?? 0, 'Miembros con pagos sin cerrar', 'yellow'),
    card(
        'Autorizaciones que faltan',
        props.semaphore.missing_authorizations ?? 0,
        props.semaphore.next_event_title ? `Para: ${props.semaphore.next_event_title}` : 'Sin próximos eventos con inscripción'
    ),
    card('Certificados por caducar', props.semaphore.expiring_certificates ?? 0, 'Delitos sexuales (≤30 días)'),
    card('Documentos por caducar', props.semaphore.expiring_documents ?? 0, 'Seguros, censo… (≤30 días)'),
]
</script>

<template>
    <Head title="Inicio" />

    <AppLayout>
        <PageHeader
            title="Panel de control"
            :subtitle="`Hola, ${user?.name}. Este es el estado del grupo de un vistazo.`"
        />

        <!-- Semáforo documental -->
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <div
                v-for="c in semaphoreCards"
                :key="c.label"
                class="rounded-lg border bg-white p-4 shadow-sm"
                :class="c.color === 'green' ? 'border-emerald-200' : c.color === 'yellow' ? 'border-amber-300' : 'border-red-300'"
            >
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-slate-600">{{ c.label }}</span>
                    <span
                        class="text-2xl"
                        :class="c.color === 'green' ? 'text-emerald-500' : c.color === 'yellow' ? 'text-amber-500' : 'text-red-500'"
                    >
                        {{ c.color === 'green' ? '✅' : c.color === 'yellow' ? '⚠️' : '❗' }}
                    </span>
                </div>
                <p class="mt-2 text-3xl font-bold text-slate-800">{{ c.value }}</p>
                <p class="mt-1 text-xs text-slate-400">{{ c.hint }}</p>
            </div>
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            <!-- Próximos eventos -->
            <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Próximos eventos</h2>
                <ul v-if="upcomingEvents.length" class="divide-y divide-slate-100">
                    <li v-for="e in upcomingEvents" :key="e.id" class="flex items-center justify-between py-2">
                        <div>
                            <p class="text-sm font-medium text-slate-700">{{ e.title }}</p>
                            <p class="text-xs text-slate-400">{{ e.type }} · {{ e.location || 'Sin lugar' }}</p>
                        </div>
                        <span class="text-xs text-slate-500">{{ e.start_at }}</span>
                    </li>
                </ul>
                <p v-else class="text-sm text-slate-400">No hay eventos próximos.</p>
            </section>

            <!-- Últimos cobros -->
            <section v-if="recentCharges.length" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Últimos cobros</h2>
                <ul class="divide-y divide-slate-100">
                    <li v-for="c in recentCharges" :key="c.id" class="flex items-center justify-between py-2">
                        <div>
                            <p class="text-sm font-medium text-slate-700">{{ c.member }}</p>
                            <p class="text-xs text-slate-400">{{ c.charge }} · {{ c.amount }} €</p>
                        </div>
                        <BadgeEstado :label="c.status" :color="c.status_color" />
                    </li>
                </ul>
            </section>

            <!-- Alertas de inventario -->
            <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Inventario</h2>
                <div class="flex gap-6">
                    <div>
                        <p class="text-2xl font-bold" :class="(inventoryAlerts.review_due ?? 0) > 0 ? 'text-amber-600' : 'text-slate-700'">
                            {{ inventoryAlerts.review_due ?? 0 }}
                        </p>
                        <p class="text-xs text-slate-400">Ítems por revisar</p>
                    </div>
                    <div>
                        <p class="text-2xl font-bold" :class="(inventoryAlerts.overdue_checkouts ?? 0) > 0 ? 'text-red-600' : 'text-slate-700'">
                            {{ inventoryAlerts.overdue_checkouts ?? 0 }}
                        </p>
                        <p class="text-xs text-slate-400">Préstamos sin devolver</p>
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
