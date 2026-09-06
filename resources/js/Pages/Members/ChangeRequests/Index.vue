<script setup>
import { ref } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import BadgeEstado from '@/Components/Shared/BadgeEstado.vue'
import EmptyState from '@/Components/Shared/EmptyState.vue'
import { useToast } from '@/composables/useToast'

const props = defineProps({
    requests: { type: Array, default: () => [] },
    canSensitive: { type: Boolean, default: false },
})

const toast = useToast()
const fmtDate = (iso) => (iso ? new Date(iso).toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' }) : '')

const rejecting = ref(null)
const rejectForm = useForm({ review_note: '' })

function approve(r) {
    router.post(route('member-change-requests.approve', r.id), {}, {
        preserveScroll: true,
        onSuccess: () => toast.success('Cambios aplicados a la ficha.'),
    })
}
function openReject(r) {
    rejecting.value = r.id
    rejectForm.reset()
}
function confirmReject(r) {
    rejectForm.post(route('member-change-requests.reject', r.id), {
        preserveScroll: true,
        onSuccess: () => { toast.success('Solicitud rechazada.'); rejecting.value = null },
    })
}
</script>

<template>
    <Head title="Revisiones de familias" />
    <AppLayout>
        <PageHeader title="Revisiones de familias"
            subtitle="Cambios de datos propuestos por las familias desde el portal. Aprobar los aplica a la ficha."
            icon="inbox" />

        <EmptyState v-if="!requests.length" title="No hay revisiones"
            description="Cuando una familia proponga cambios desde el portal, aparecerán aquí." />

        <div v-else class="space-y-4">
            <article v-for="r in requests" :key="r.id"
                class="overflow-hidden rounded-xl border shadow-card"
                :class="r.status === 'pending' ? 'border-amber-200' : 'border-ink-200'">
                <header class="flex flex-wrap items-center justify-between gap-2 border-b border-ink-100 bg-slate-50/70 p-4">
                    <div class="min-w-0">
                        <Link :href="route('members.show', r.member_id)" class="text-base font-bold text-ink-900 hover:underline">
                            {{ r.member_name }}
                        </Link>
                        <p class="text-xs text-ink-500">
                            {{ r.branch }} · enviado por {{ r.submitted_by || '—' }} el {{ fmtDate(r.submitted_at) }}
                        </p>
                    </div>
                    <BadgeEstado :label="r.status_label" :color="r.status_color" />
                </header>

                <div class="p-4">
                    <p v-if="r.note" class="mb-3 rounded-lg bg-slate-50 p-3 text-sm text-ink-600">
                        <span class="font-semibold">Nota de la familia:</span> {{ r.note }}
                    </p>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs uppercase text-ink-400">
                                    <th class="py-1.5 pr-4">Campo</th>
                                    <th class="py-1.5 pr-4">Actual</th>
                                    <th class="py-1.5 pr-4">Propuesto</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-ink-100">
                                <tr v-for="(c, i) in r.changes" :key="i">
                                    <td class="py-2 pr-4 font-medium text-ink-700">{{ c.label }}</td>
                                    <td class="py-2 pr-4 text-ink-500">
                                        <span v-if="c.hidden" class="italic text-ink-400">(dato médico oculto)</span>
                                        <span v-else>{{ c.current || '—' }}</span>
                                    </td>
                                    <td class="py-2 pr-4 font-semibold text-emerald-700">
                                        <span v-if="c.hidden" class="italic text-ink-400">requiere permiso de datos sensibles</span>
                                        <span v-else>{{ c.proposed || '—' }}</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <p v-if="!canSensitive && r.changes.some((c) => c.hidden)" class="mt-2 text-xs text-amber-700">
                        Esta solicitud incluye datos médicos. Al aprobarla se aplicarán solo los campos no sensibles.
                    </p>

                    <div v-if="r.status === 'pending'" class="mt-4 flex flex-wrap items-center gap-2">
                        <button type="button" class="btn-primary btn-sm" @click="approve(r)">Aprobar y aplicar</button>
                        <button type="button" class="btn-ghost btn-sm" @click="openReject(r)">Rechazar</button>
                        <div v-if="rejecting === r.id" class="flex flex-1 items-center gap-2">
                            <input v-model="rejectForm.review_note" type="text" class="input min-w-0 flex-1 py-1.5 text-sm"
                                placeholder="Motivo (opcional)" @keyup.enter="confirmReject(r)" />
                            <button type="button" class="btn-secondary btn-sm" :disabled="rejectForm.processing" @click="confirmReject(r)">
                                Confirmar rechazo
                            </button>
                        </div>
                    </div>
                    <p v-else class="mt-4 text-xs text-ink-400">
                        {{ r.status_label }}<span v-if="r.reviewed_by"> por {{ r.reviewed_by }}</span>
                        <span v-if="r.review_note"> · {{ r.review_note }}</span>
                    </p>
                </div>
            </article>
        </div>
    </AppLayout>
</template>
