<script setup>
import { ref } from 'vue'
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

function formatDate(iso) {
    if (!iso) return null
    return new Date(iso).toLocaleString('es-ES', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

const statusIcon = { ok: '✅', warning: '⚠️', fail: '❌' }

// --- Inscripciones ---
function toggleEnrolled(enrollment) {
    router.patch(route('events.enrollments.update', [props.event.id, enrollment.id]), {
        enrolled: !enrollment.enrolled,
    }, {
        preserveScroll: true,
        onSuccess: () => toast.success('Inscripción actualizada.'),
    })
}

function copyLink(url) {
    navigator.clipboard?.writeText(url)
    toast.success('Enlace copiado.')
}

// --- Checklist ---
const newChecklistLabel = ref('')

function addChecklistItem() {
    if (!newChecklistLabel.value.trim()) return
    router.post(route('events.checklist.store', props.event.id), { label: newChecklistLabel.value }, {
        preserveScroll: true,
        onSuccess: () => {
            newChecklistLabel.value = ''
            toast.success('Punto añadido.')
        },
    })
}

function toggleChecklist(item) {
    router.patch(route('events.checklist.update', [props.event.id, item.id]), { done: !item.done }, {
        preserveScroll: true,
    })
}

function removeChecklistItem(item) {
    router.delete(route('events.checklist.destroy', [props.event.id, item.id]), { preserveScroll: true })
}

// --- Cobro ---
const showChargeModal = ref(false)
const defaultChargeType = props.event.type === 'campamento' ? 'campamento' : (props.event.type === 'salida' || props.event.type === 'acampada' ? 'salida' : 'otro')

const chargeForm = useForm({
    title: `${props.event.title}`,
    description: '',
    amount: '',
    due_date: '',
    type: defaultChargeType,
})

function submitCharge() {
    chargeForm.post(route('events.charges.store', props.event.id), {
        preserveScroll: true,
        onSuccess: () => {
            showChargeModal.value = false
            toast.success('Cobro creado y repartido entre los inscritos.')
        },
    })
}
</script>

<template>
    <Head :title="event.title" />

    <AppLayout>
        <PageHeader :title="event.title" :subtitle="event.type_label">
            <template #actions>
                <Link :href="route('events.index')" class="text-sm text-slate-500 hover:text-slate-700">
                    ← Calendario
                </Link>
                <Link v-if="can.update" :href="route('events.edit', event.id)" class="rounded-md border border-slate-300 px-3 py-2 text-sm hover:bg-slate-50">
                    Editar
                </Link>
                <ConfirmButton
                    v-if="can.delete"
                    title="Eliminar evento"
                    message="¿Seguro que quieres eliminar este evento? Esta acción no se puede deshacer."
                    confirm-label="Eliminar"
                    @confirm="router.delete(route('events.destroy', event.id))"
                >
                    <span class="inline-flex items-center rounded-md border border-red-200 px-3 py-2 text-sm text-red-600 hover:bg-red-50">
                        Eliminar
                    </span>
                </ConfirmButton>
            </template>
        </PageHeader>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <!-- Detalles -->
                <section class="rounded-lg border border-slate-200 bg-white p-5">
                    <dl class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <dt class="text-xs font-medium uppercase text-slate-400">Inicio</dt>
                            <dd class="text-sm text-slate-700">{{ formatDate(event.start_at) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium uppercase text-slate-400">Fin</dt>
                            <dd class="text-sm text-slate-700">{{ formatDate(event.end_at) ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium uppercase text-slate-400">Lugar</dt>
                            <dd class="text-sm text-slate-700">{{ event.location ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium uppercase text-slate-400">Ramas</dt>
                            <dd class="flex flex-wrap gap-1">
                                <BadgeEstado v-for="b in event.branches" :key="b" :label="b" color="gray" />
                                <span v-if="!event.branches?.length" class="text-sm text-slate-400">Evento de grupo</span>
                            </dd>
                        </div>
                    </dl>
                    <p v-if="event.description" class="mt-4 whitespace-pre-line text-sm text-slate-600">{{ event.description }}</p>
                </section>

                <!-- Validador legal -->
                <section v-if="campRatio" class="rounded-lg border border-slate-200 bg-white p-5">
                    <h2 class="mb-3 flex items-center gap-2 text-sm font-semibold text-slate-700">
                        <span>{{ statusIcon[campRatio.status] }}</span>
                        Validación legal de ratios
                    </h2>
                    <p class="mb-3 text-xs text-slate-500">
                        {{ campRatio.leaders }} responsables inscritos / {{ campRatio.required_leaders }} necesarios
                        (ratio 1:{{ campRatio.ratio }}, {{ campRatio.participants }} participantes).
                    </p>
                    <ul class="space-y-1.5">
                        <li v-for="check in campRatio.checks" :key="check.key" class="flex items-start gap-2 text-sm">
                            <span>{{ statusIcon[check.status] }}</span>
                            <span class="text-slate-600">{{ check.message }}</span>
                        </li>
                    </ul>
                </section>

                <!-- Inscripciones -->
                <section v-if="event.requires_enrollment" class="rounded-lg border border-slate-200 bg-white p-5">
                    <h2 class="mb-3 text-sm font-semibold text-slate-700">Inscripciones</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead>
                                <tr class="text-left text-xs uppercase text-slate-400">
                                    <th class="py-2 pr-3">Nombre</th>
                                    <th class="py-2 pr-3">Rama</th>
                                    <th class="py-2 pr-3">Inscrito</th>
                                    <th class="py-2 pr-3">Estado</th>
                                    <th class="py-2 pr-3">Enlace familia</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="en in enrollments" :key="en.id">
                                    <td class="py-2 pr-3">{{ en.member_name }}</td>
                                    <td class="py-2 pr-3">{{ en.role_label }}</td>
                                    <td class="py-2 pr-3">
                                        <label class="inline-flex items-center gap-1.5">
                                            <input type="checkbox" :checked="en.enrolled" @change="toggleEnrolled(en)" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500" />
                                        </label>
                                    </td>
                                    <td class="py-2 pr-3">
                                        <BadgeEstado v-if="en.has_authorization" label="Autorización subida" color="green" />
                                        <BadgeEstado v-else-if="en.confirmed_at" label="Confirmado, sin autorización" color="yellow" />
                                        <BadgeEstado v-else label="Pendiente" color="gray" />
                                    </td>
                                    <td class="py-2 pr-3">
                                        <button type="button" class="text-xs text-brand-700 hover:underline" @click="copyLink(en.public_url)">
                                            Copiar enlace
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="enrollments.length === 0">
                                    <td colspan="5" class="py-6 text-center text-slate-400">
                                        No hay miembros inscribibles para las ramas de este evento.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- Checklist -->
                <section v-if="event.requires_enrollment" class="rounded-lg border border-slate-200 bg-white p-5">
                    <h2 class="mb-3 text-sm font-semibold text-slate-700">Checklist de documentación</h2>
                    <ul class="space-y-2">
                        <li v-for="item in checklistItems" :key="item.id" class="flex items-center justify-between gap-2">
                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                <input type="checkbox" :checked="item.done" @change="toggleChecklist(item)" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500" />
                                <span :class="item.done ? 'line-through text-slate-400' : ''">{{ item.label }}</span>
                            </label>
                            <button type="button" class="text-xs text-slate-400 hover:text-red-600" @click="removeChecklistItem(item)">✕</button>
                        </li>
                    </ul>
                    <div class="mt-3 flex gap-2">
                        <input v-model="newChecklistLabel" type="text" placeholder="Nuevo punto…" class="flex-1 rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500" @keyup.enter="addChecklistItem" />
                        <button type="button" class="rounded-md border border-slate-300 px-3 py-1.5 text-sm hover:bg-slate-50" @click="addChecklistItem">Añadir</button>
                    </div>
                </section>
            </div>

            <div class="space-y-6">
                <!-- PDFs -->
                <section v-if="event.requires_enrollment" class="rounded-lg border border-slate-200 bg-white p-5">
                    <h2 class="mb-3 text-sm font-semibold text-slate-700">Documentos</h2>
                    <div class="flex flex-col gap-2">
                        <a :href="route('events.pdf.attendees', event.id)" target="_blank" class="rounded-md border border-slate-300 px-3 py-2 text-center text-sm hover:bg-slate-50">
                            📋 Listado de asistentes (PDF)
                        </a>
                        <a :href="route('events.pdf.circular', event.id)" target="_blank" class="rounded-md border border-slate-300 px-3 py-2 text-center text-sm hover:bg-slate-50">
                            📄 Circular (PDF)
                        </a>
                    </div>
                </section>

                <!-- Cobros -->
                <section v-if="event.requires_enrollment" class="rounded-lg border border-slate-200 bg-white p-5">
                    <h2 class="mb-3 text-sm font-semibold text-slate-700">Cobros del evento</h2>
                    <ul class="mb-3 space-y-1.5 text-sm">
                        <li v-for="c in charges" :key="c.id" class="flex justify-between">
                            <span class="text-slate-600">{{ c.title }}</span>
                            <span class="font-medium text-slate-800">{{ Number(c.amount).toFixed(2) }} €</span>
                        </li>
                        <li v-if="charges.length === 0" class="text-slate-400">Sin cobros asociados todavía.</li>
                    </ul>
                    <button
                        v-if="can.update"
                        type="button"
                        class="w-full rounded-md bg-brand-600 px-3 py-2 text-sm font-semibold text-white hover:bg-brand-700"
                        @click="showChargeModal = true"
                    >
                        + Generar cobro
                    </button>
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
                <button type="button" class="rounded-md px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100" @click="showChargeModal = false">
                    Cancelar
                </button>
                <button type="button" class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700" :disabled="chargeForm.processing" @click="submitCharge">
                    Crear cobro
                </button>
            </template>
        </Modal>
    </AppLayout>
</template>
