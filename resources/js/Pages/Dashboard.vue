<script setup>
import { Head } from '@inertiajs/vue3'
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

function card(label, value, hint, dangerColor = 'red') {
    return { label, value, hint, color: value > 0 ? dangerColor : 'green' }
}

const semaphoreCards = [
    card('Cuotas pendientes', props.semaphore.pending_charges ?? 0, 'Miembros con pagos sin cerrar', 'amber'),
    card(
        'Autorizaciones que faltan',
        props.semaphore.missing_authorizations ?? 0,
        props.semaphore.next_event_title ? `Para: ${props.semaphore.next_event_title}` : 'Sin próximos eventos con inscripción'
    ),
    card('Certificados por caducar', props.semaphore.expiring_certificates ?? 0, 'Delitos sexuales (≤30 días)'),
    card('Documentos por caducar', props.semaphore.expiring_documents ?? 0, 'Seguros, censo… (≤30 días)'),
]

const styles = {
    green: { ring: 'border-emerald-200', dot: 'bg-emerald-500', icon: '✓', chip: 'text-emerald-600' },
    amber: { ring: 'border-amber-200', dot: 'bg-amber-500', icon: '!', chip: 'text-amber-600' },
    red: { ring: 'border-brand-200', dot: 'bg-brand-500', icon: '!', chip: 'text-brand-600' },
}
</script>

<template>
    <Head title="Inicio" />

    <AppLayout>
        <PageHeader
            title="Panel de control"
            :subtitle="`Hola, ${user?.name}. Este es el estado del grupo de un vistazo.`"
        />

        <!-- Semáforo documental -->
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div v-for="c in semaphoreCards" :key="c.label" class="card p-4" :class="styles[c.color].ring">
                <div class="flex items-start justify-between">
                    <span class="text-sm font-medium text-ink-500">{{ c.label }}</span>
                    <span class="flex h-6 w-6 items-center justify-center rounded-full text-xs font-bold text-white" :class="styles[c.color].dot">
                        {{ styles[c.color].icon }}
                    </span>
                </div>
                <p class="mt-3 text-3xl font-bold text-ink-900">{{ c.value }}</p>
                <p class="mt-1 text-xs text-ink-400">{{ c.hint }}</p>
            </div>
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            <!-- Próximos eventos -->
            <section class="card-pad">
                <h2 class="section-title mb-3">Próximos eventos</h2>
                <ul v-if="upcomingEvents.length" class="divide-y divide-ink-100">
                    <li v-for="e in upcomingEvents" :key="e.id" class="flex items-center justify-between py-2.5">
                        <div>
                            <p class="text-sm font-medium text-ink-800">{{ e.title }}</p>
                            <p class="text-xs text-ink-400">{{ e.type }} · {{ e.location || 'Sin lugar' }}</p>
                        </div>
                        <span class="whitespace-nowrap text-xs font-medium text-ink-500">{{ e.start_at }}</span>
                    </li>
                </ul>
                <p v-else class="py-6 text-center text-sm text-ink-400">No hay eventos próximos.</p>
            </section>

            <!-- Últimos cobros -->
            <section v-if="recentCharges.length" class="card-pad">
                <h2 class="section-title mb-3">Últimos cobros</h2>
                <ul class="divide-y divide-ink-100">
                    <li v-for="c in recentCharges" :key="c.id" class="flex items-center justify-between py-2.5">
                        <div>
                            <p class="text-sm font-medium text-ink-800">{{ c.member }}</p>
                            <p class="text-xs text-ink-400">{{ c.charge }} · {{ c.amount }} €</p>
                        </div>
                        <BadgeEstado :label="c.status" :color="c.status_color" />
                    </li>
                </ul>
            </section>

            <!-- Alertas de inventario -->
            <section class="card-pad">
                <h2 class="section-title mb-3">Inventario</h2>
                <div class="flex gap-8">
                    <div>
                        <p class="text-3xl font-bold" :class="(inventoryAlerts.review_due ?? 0) > 0 ? 'text-amber-600' : 'text-ink-800'">
                            {{ inventoryAlerts.review_due ?? 0 }}
                        </p>
                        <p class="mt-1 text-xs text-ink-400">Ítems por revisar</p>
                    </div>
                    <div>
                        <p class="text-3xl font-bold" :class="(inventoryAlerts.overdue_checkouts ?? 0) > 0 ? 'text-brand-600' : 'text-ink-800'">
                            {{ inventoryAlerts.overdue_checkouts ?? 0 }}
                        </p>
                        <p class="mt-1 text-xs text-ink-400">Préstamos sin devolver</p>
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
