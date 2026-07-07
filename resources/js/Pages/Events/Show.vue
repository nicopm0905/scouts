<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import BadgeEstado from '@/Components/Shared/BadgeEstado.vue'
import ConfirmButton from '@/Components/Shared/ConfirmButton.vue'
import Modal from '@/Components/Shared/Modal.vue'
import FormField from '@/Components/Shared/FormField.vue'
import { useToast } from '@/composables/useToast'

const props = defineProps({
    event: { type: Object, required: true },
    enrollments: { type: Array, required: true },
    checklistItems: { type: Array, required: true },
    charges: { type: Array, required: true },
    campRatio: { type: Object, default: null },
    can: { type: Object, required: true },
})

const toast = useToast()
const formatDate = (iso) => iso ? new Date(iso).toLocaleString('es-ES', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : null
const eur = (n) => Number(n ?? 0).toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' €'

const statusIcon = { ok: '✅', warning: '⚠️', fail: '❌' }
const semaphore = computed(() => {
    const s = props.campRatio?.status
    return {
        ok: { verdict: 'Cumple la normativa', ring: 'border-emerald-300', bg: 'bg-emerald-50', text: 'text-emerald-800', dot: 'bg-emerald-500' },
        warning: { verdict: 'Cumple con avisos', ring: 'border-amber-300', bg: 'bg-amber-50', text: 'text-amber-800', dot: 'bg-amber-500' },
        fail: { verdict: 'No cumple la normativa', ring: 'border-brand-300', bg: 'bg-brand-50', text: 'text-brand-800', dot: 'bg-brand-500' },
    }[s] ?? {}
})
const checkColor = { ok: 'bg-emerald-500', warning: 'bg-amber-500', fail: 'bg-brand-500' }

const enrollStats = computed(() => {
    const enrolled = props.enrollments.filter((e) => e.enrolled)
    return {
        enrolled: enrolled.length,
        withAuth: enrolled.filter((e) => e.has_authorization).length,
        total: props.enrollments.length,
    }
})
const checklistDone = computed(() => props.checklistItems.filter((i) => i.done).length)

function toggleEnrolled(en) {
    router.patch(route('events.enrollments.update', [props.event.id, en.id]), { enrolled: !en.enrolled }, {
        preserveScroll: true, onSuccess: () => toast.success('Inscripción actualizada.'),
    })
}
function copyLink(url) { navigator.clipboard?.writeText(url); toast.success('Enlace copiado.') }

const newChecklistLabel = ref('')
function addChecklistItem() {
    if (!newChecklistLabel.value.trim()) return
    router.post(route('events.checklist.store', props.event.id), { label: newChecklistLabel.value }, {
        preserveScroll: true, onSuccess: () => { newChecklistLabel.value = ''; toast.success('Punto añadido.') },
    })
}
function toggleChecklist(item) {
    router.patch(route('events.checklist.update', [props.event.id, item.id]), { done: !item.done }, { preserveScroll: true })
}
function removeChecklistItem(item) {
    router.delete(route('events.checklist.destroy', [props.event.id, item.id]), { preserveScroll: true })
}

const showChargeModal = ref(false)
const defaultChargeType = props.event.type === 'campamento' ? 'campamento' : (props.event.type === 'salida' || props.event.type === 'acampada' ? 'salida' : 'otro')
const chargeForm = useForm({ title: props.event.title, description: '', amount: '', due_date: '', type: defaultChargeType })
function submitCharge() {
    chargeForm.post(route('events.charges.store', props.event.id), {
        preserveScroll: true, onSuccess: () => { showChargeModal.value = false; toast.success('Cobro creado y repartido entre los inscritos.') },
    })
}
</script>

<template>
    <Head :title="event.title" />
    <AppLayout>
        <PageHeader :title="event.title" :subtitle="event.type_label">
            <template #actions>
                <Link :href="route('events.index')" class="btn-ghost btn-sm">← Calendario</Link>
                <Link v-if="can.update" :href="route('events.edit', event.id)" class="btn-secondary btn-sm">Editar</Link>
                <ConfirmButton v-if="can.delete" title="Eliminar evento"
                    message="¿Seguro que quieres eliminar este evento? Esta acción no se puede deshacer." confirm-label="Eliminar"
                    @confirm="router.delete(route('events.destroy', event.id))">
                    <span class="rounded-lg border border-brand-200 px-3 py-1.5 text-sm text-brand-600 hover:bg-brand-50">Eliminar</span>
                </ConfirmButton>
            </template>
        </PageHeader>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <!-- Detalles -->
                <section class="card-pad">
                    <dl class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                        <div><dt class="section-title">Inicio</dt><dd class="mt-1 text-sm font-medium text-ink-800">{{ formatDate(event.start_at) }}</dd></div>
                        <div><dt class="section-title">Fin</dt><dd class="mt-1 text-sm font-medium text-ink-800">{{ formatDate(event.end_at) ?? '—' }}</dd></div>
                        <div><dt class="section-title">Lugar</dt><dd class="mt-1 text-sm font-medium text-ink-800">{{ event.location ?? '—' }}</dd></div>
                        <div>
                            <dt class="section-title">Ramas</dt>
                            <dd class="mt-1 flex flex-wrap gap-1">
                                <BadgeEstado v-for="b in event.branches" :key="b" :label="b" color="gray" />
                                <span v-if="!event.branches?.length" class="text-sm text-ink-400">Grupo</span>
                            </dd>
                        </div>
                    </dl>
                    <p v-if="event.description" class="mt-4 whitespace-pre-line border-t border-ink-100 pt-4 text-sm text-ink-600">{{ event.description }}</p>
                </section>

                <!-- Validador legal (semáforo) -->
                <section v-if="campRatio" class="overflow-hidden rounded-xl border shadow-card" :class="semaphore.ring">
                    <div class="flex items-center gap-3 p-4" :class="semaphore.bg">
                        <span class="text-3xl">{{ statusIcon[campRatio.status] }}</span>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide" :class="semaphore.text">Validación legal de ratios</p>
                            <p class="text-lg font-bold" :class="semaphore.text">{{ semaphore.verdict }}</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-3 divide-x divide-ink-100 border-y border-ink-100 bg-white text-center">
                        <div class="p-3"><p class="text-2xl font-bold text-ink-800">{{ campRatio.participants }}</p><p class="text-xs text-ink-400">Participantes</p></div>
                        <div class="p-3"><p class="text-2xl font-bold" :class="campRatio.leaders >= campRatio.required_leaders ? 'text-emerald-600' : 'text-brand-600'">{{ campRatio.leaders }}</p><p class="text-xs text-ink-400">Responsables</p></div>
                        <div class="p-3"><p class="text-2xl font-bold text-ink-800">{{ campRatio.required_leaders }}</p><p class="text-xs text-ink-400">Necesarios (1:{{ campRatio.ratio }})</p></div>
                    </div>
                    <ul class="space-y-2 bg-white p-4">
                        <li v-for="check in campRatio.checks" :key="check.key" class="flex items-start gap-2.5 text-sm">
                            <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full" :class="checkColor[check.status]" />
                            <span class="text-ink-600">{{ check.message }}</span>
                        </li>
                    </ul>
                </section>

                <!-- Inscripciones -->
                <section v-if="event.requires_enrollment" class="card-pad">
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="text-base font-bold text-ink-800">Inscripciones</h2>
                        <span class="text-xs text-ink-500">{{ enrollStats.enrolled }} inscritos · {{ enrollStats.withAuth }} con autorización</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-ink-200 text-sm">
                            <thead>
                                <tr class="text-left text-xs uppercase text-ink-400">
                                    <th class="py-2 pr-3">Nombre</th><th class="py-2 pr-3">Rama</th>
                                    <th class="py-2 pr-3">Inscrito</th><th class="py-2 pr-3">Estado</th><th class="py-2 pr-3">Familia</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-ink-100">
                                <tr v-for="en in enrollments" :key="en.id">
                                    <td class="py-2 pr-3 font-medium text-ink-800">{{ en.member_name }}</td>
                                    <td class="py-2 pr-3 text-ink-600">{{ en.role_label }}</td>
                                    <td class="py-2 pr-3">
                                        <input type="checkbox" :checked="en.enrolled" @change="toggleEnrolled(en)" class="rounded border-ink-300 text-brand-600 focus:ring-brand-500" />
                                    </td>
                                    <td class="py-2 pr-3">
                                        <BadgeEstado v-if="en.has_authorization" label="Autorización ✓" color="green" />
                                        <BadgeEstado v-else-if="en.confirmed_at" label="Sin autorización" color="yellow" />
                                        <BadgeEstado v-else label="Pendiente" color="gray" />
                                    </td>
                                    <td class="py-2 pr-3">
                                        <button type="button" class="text-xs font-medium text-brand-700 hover:underline" @click="copyLink(en.public_url)">Copiar enlace</button>
                                    </td>
                                </tr>
                                <tr v-if="enrollments.length === 0">
                                    <td colspan="5" class="py-6 text-center text-ink-400">No hay miembros inscribibles para las ramas de este evento.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- Checklist -->
                <section v-if="event.requires_enrollment" class="card-pad">
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="text-base font-bold text-ink-800">Checklist de documentación</h2>
                        <span class="text-xs font-medium text-ink-500">{{ checklistDone }}/{{ checklistItems.length }}</span>
                    </div>
                    <ul class="space-y-2">
                        <li v-for="item in checklistItems" :key="item.id" class="flex items-center justify-between gap-2">
                            <label class="flex items-center gap-2 text-sm text-ink-700">
                                <input type="checkbox" :checked="item.done" @change="toggleChecklist(item)" class="rounded border-ink-300 text-brand-600 focus:ring-brand-500" />
                                <span :class="item.done ? 'text-ink-400 line-through' : ''">{{ item.label }}</span>
                            </label>
                            <button type="button" class="text-xs text-ink-400 hover:text-brand-600" @click="removeChecklistItem(item)">✕</button>
                        </li>
                    </ul>
                    <div class="mt-3 flex gap-2">
                        <input v-model="newChecklistLabel" type="text" placeholder="Nuevo punto…" class="input flex-1" @keyup.enter="addChecklistItem" />
                        <button type="button" class="btn-secondary btn-sm" @click="addChecklistItem">Añadir</button>
                    </div>
                </section>
            </div>

            <div class="space-y-6">
                <section v-if="event.requires_enrollment" class="card-pad">
                    <h2 class="section-title mb-3">Documentos</h2>
                    <div class="flex flex-col gap-2">
                        <a :href="route('events.pdf.attendees', event.id)" target="_blank" class="btn-secondary justify-start">📋 Listado de asistentes (PDF)</a>
                        <a :href="route('events.pdf.circular', event.id)" target="_blank" class="btn-secondary justify-start">📄 Circular (PDF)</a>
                    </div>
                </section>

                <section v-if="event.requires_enrollment" class="card-pad">
                    <h2 class="section-title mb-3">Cobros del evento</h2>
                    <ul class="mb-3 space-y-1.5 text-sm">
                        <li v-for="c in charges" :key="c.id" class="flex justify-between">
                            <span class="text-ink-600">{{ c.title }}</span>
                            <span class="font-medium text-ink-800">{{ eur(c.amount) }}</span>
                        </li>
                        <li v-if="charges.length === 0" class="text-ink-400">Sin cobros asociados todavía.</li>
                    </ul>
                    <button v-if="can.update" type="button" class="btn-primary w-full" @click="showChargeModal = true">+ Generar cobro</button>
                </section>
            </div>
        </div>

        <Modal :show="showChargeModal" title="Generar cobro" @close="showChargeModal = false">
            <form @submit.prevent="submitCharge" class="space-y-3">
                <FormField v-model="chargeForm.title" label="Concepto" required :error="chargeForm.errors.title" />
                <FormField v-model="chargeForm.amount" type="number" label="Importe (€)" required :error="chargeForm.errors.amount" />
                <FormField v-model="chargeForm.due_date" type="date" label="Fecha límite de pago" :error="chargeForm.errors.due_date" />
                <FormField v-model="chargeForm.description" type="textarea" label="Descripción" :error="chargeForm.errors.description" />
            </form>
            <template #footer>
                <button type="button" class="btn-ghost" @click="showChargeModal = false">Cancelar</button>
                <button type="button" class="btn-primary" :disabled="chargeForm.processing" @click="submitCharge">Crear cobro</button>
            </template>
        </Modal>
    </AppLayout>
</template>
