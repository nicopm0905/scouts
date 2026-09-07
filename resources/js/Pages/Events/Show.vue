<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import BadgeRama from '@/Components/Shared/BadgeRama.vue'
import DashIcon from '@/Components/Shared/DashIcon.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import BadgeEstado from '@/Components/Shared/BadgeEstado.vue'
import ConfirmButton from '@/Components/Shared/ConfirmButton.vue'
import Modal from '@/Components/Shared/Modal.vue'
import FormField from '@/Components/Shared/FormField.vue'
import { useToast } from '@/composables/useToast'
import { ArrowLeft, Coins, Euro, Edit, Trash2, Tent, Users, FileText, UserPlus, MessageCircle, FileDown, Copy, X, FolderArchive, CheckCircle2, AlertTriangle, Circle, ChevronRight, ChevronDown, Box } from 'lucide-vue-next'

const props = defineProps({
    event: { type: Object, required: true },
    enrollments: { type: Array, required: true },
    allMembers: { type: Array, default: () => [] },
    checklistItems: { type: Array, required: true },
    charges: { type: Array, required: true },
    activities: { type: Array, default: () => [] },
    availableActivities: { type: Array, default: () => [] },
    mscReadiness: { type: Object, default: null },
    campRatio: { type: Object, default: null },
    preparation: { type: Object, default: null },
    staffUsers: { type: Array, default: () => [] },
    can: { type: Object, required: true },
})

// "Preparar salida": estilos y salto a la sección donde se completa cada paso.
const prepStyle = {
    ok: { icon: CheckCircle2, dot: 'text-emerald-500', label: 'text-ink-800' },
    warn: { icon: AlertTriangle, dot: 'text-amber-500', label: 'text-ink-800' },
    todo: { icon: Circle, dot: 'text-ink-300', label: 'text-ink-600' },
}
function scrollToAnchor(id) {
    document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' })
}

const toast = useToast()
const isPreparationOpen = ref(true)
const showExceptionalModal = ref(false)
const selectedMemberId = ref('')

const availableExceptionalMembers = computed(() => {
    const existingIds = new Set(props.enrollments.map((e) => e.member_id))
    return props.allMembers.filter((m) => !existingIds.has(m.id))
})

function addExceptionalMember() {
    if (!selectedMemberId.value) return
    router.post(route('events.enrollments.store', props.event.id), { member_id: selectedMemberId.value }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            selectedMemberId.value = ''
            showExceptionalModal.value = false
            toast.success('Asistente añadido al evento de forma excepcional.')
        },
    })
}
const formatDate = (iso) => iso ? new Date(iso).toLocaleString('es-ES', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : null
const eur = (n) => Number(n ?? 0).toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' €'

// Iconos del semáforo legal (nombres de DashIcon).
const statusIcon = { ok: 'check', warning: 'warning', fail: 'close' }
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

const activitiesByDay = computed(() => {
    if (!props.activities) return {}
    const grouped = {}
    props.activities.forEach(act => {
        const day = act.day_number || ''
        if (!grouped[day]) grouped[day] = []
        grouped[day].push(act)
    })
    return grouped
})

function groupBySlot(activities) {
    const grouped = {}
    activities.forEach(act => {
        const slot = act.time_slot || ''
        if (!grouped[slot]) grouped[slot] = []
        grouped[slot].push(act)
    })
    return grouped
}

// --- Actividades del evento: enlazar y quitar sin salir de la ficha ---
const showActivityModal = ref(false)
const activityToLink = ref('')

function linkActivity() {
    if (!activityToLink.value) return
    router.post(route('activities.events.attach', [activityToLink.value, props.event.id]), {}, {
        preserveScroll: true,
        onSuccess: () => {
            activityToLink.value = ''
            showActivityModal.value = false
            toast.success('Actividad enlazada al evento.')
        },
    })
}

function unlinkActivity(activityId) {
    router.delete(route('activities.events.detach', [activityId, props.event.id]), {
        preserveScroll: true,
        onSuccess: () => toast.success('Actividad retirada del evento.'),
    })
}

function toggleEnrolled(en) {
    en.enrolled = !en.enrolled
    router.patch(route('events.enrollments.update', [props.event.id, en.id]), { enrolled: en.enrolled }, {
        preserveScroll: true,
        preserveState: true,
        onError: () => { en.enrolled = !en.enrolled; toast.error('Error al actualizar inscripción.') },
        onSuccess: () => toast.success('Inscripción actualizada.'),
    })
}
function copyLink(url) { navigator.clipboard?.writeText(url); toast.success('Enlace copiado.') }

function whatsAppAuthorizationUrl(row) {
    const phone = row.family_phone || row.phone
    if (!phone || !row.public_url) return '#'
    const cleanPhone = phone.replace(/\D/g, '')
    const fullPhone = cleanPhone.length === 9 ? `34${cleanPhone}` : cleanPhone
    
    const familyGreeting = row.family_name ? `Familia ${row.family_name}` : 'familia'
    const text = `⚜️ *SCOUTS DE SAN JOSÉ* ⚜️\n📑 *AUTORIZACIÓN DE ACTIVIDAD*\n\nHola ${familyGreeting}!\n\nOs enviamos el enlace para confirmar la asistencia y subir la autorización firmada de *${row.member_name}* para la actividad *${props.event.title}*:\n\n👉 ${row.public_url}\n\nPodéis confirmar y subir el documento directamente desde el móvil. ¡Muchas gracias! ⚜️`

    return `https://wa.me/${fullPhone}?text=${encodeURIComponent(text)}`
}

const newChecklistLabel = ref('')
const newChecklistAssignee = ref('')
const newChecklistDue = ref('')
function addChecklistItem() {
    if (!newChecklistLabel.value.trim()) return
    const payload = {
        label: newChecklistLabel.value,
        assigned_to: newChecklistAssignee.value || null,
        due_at: newChecklistDue.value || null,
    }
    newChecklistLabel.value = ''
    newChecklistAssignee.value = ''
    newChecklistDue.value = ''
    router.post(route('events.checklist.store', props.event.id), payload, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => toast.success('Punto añadido.'),
    })
}
// Reparto de tareas del kraal: asignar responsable / fecha límite a un punto.
function patchAssignment(item) {
    router.patch(route('events.checklist.update', [props.event.id, item.id]), {
        assigned_to: item.assigned_to || null,
        due_at: item.due_at || null,
    }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => toast.success('Tarea actualizada.'),
    })
}
function toggleChecklist(item) {
    item.done = !item.done
    router.patch(route('events.checklist.update', [props.event.id, item.id]), { done: item.done }, {
        preserveScroll: true,
        preserveState: true,
        onError: () => { item.done = !item.done },
    })
}
function removeChecklistItem(item) {
    router.delete(route('events.checklist.destroy', [props.event.id, item.id]), {
        preserveScroll: true,
        preserveState: true,
    })
}

const showChargeModal = ref(false)
const defaultChargeType = props.event.type === 'campamento' ? 'campamento' : (props.event.type === 'salida' || props.event.type === 'acampada' ? 'salida' : 'otro')
const chargeForm = useForm({ title: props.event.title, description: '', amount: '', due_date: '', type: defaultChargeType })
function submitCharge() {
    chargeForm.post(route('events.charges.store', props.event.id), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => { showChargeModal.value = false; toast.success('Cobro creado y repartido entre los inscritos.') },
    })
}

function createBudget() {
    router.post(route('budgets.store'), { event_id: props.event.id }, {
        preserveScroll: true,
    })
}
</script>

<template>
    <Head :title="event.title" />
    <AppLayout>
        <PageHeader :title="event.title" :subtitle="event.type_label" icon="calendar">
            <template #actions>
                <Link :href="route('events.index')" class="btn-ghost btn-sm group"><ArrowLeft class="mr-1.5 h-4 w-4 text-slate-500 group-hover:text-slate-700 transition" /> Calendario</Link>
                <Link v-if="event.budget_id" :href="route('budgets.show', event.budget_id)" class="btn-secondary btn-sm group"><Coins class="mr-1.5 h-4 w-4 text-amber-500 group-hover:text-amber-600 transition" /> Presupuesto</Link>
                <button v-else-if="can.update" @click="createBudget" class="btn-secondary btn-sm group"><Coins class="mr-1.5 h-4 w-4 text-amber-500 group-hover:text-amber-600 transition" /> Crear Presupuesto</button>
                <Link v-if="charges.length > 0" :href="route('charges.show', charges[0].id)" class="btn-secondary btn-sm group"><Euro class="mr-1.5 h-4 w-4 text-emerald-500 group-hover:text-emerald-600 transition" /> Ver Cobro</Link>
                <Link v-if="can.update" :href="route('events.edit', event.id)" class="btn-secondary btn-sm group"><Edit class="mr-1.5 h-4 w-4 text-slate-500 group-hover:text-slate-700 transition" /> Editar</Link>
                <ConfirmButton v-if="can.delete" title="Eliminar evento"
                    message="¿Seguro que quieres eliminar este evento? Esta acción no se puede deshacer." confirm-label="Eliminar"
                    @confirm="router.delete(route('events.destroy', event.id))">
                    <span class="inline-flex items-center gap-1.5 rounded-xl border border-red-200 px-3 py-1.5 text-sm font-semibold text-red-600 hover:bg-red-50 transition"><Trash2 class="h-4 w-4" /> Eliminar</span>
                </ConfirmButton>
            </template>
        </PageHeader>

        <!-- Preparar salida: estado de cada paso con la acción para completarlo -->
        <section v-if="preparation" class="mb-6 overflow-hidden rounded-xl border border-ink-200 bg-white shadow-card">
            <button type="button" @click="isPreparationOpen = !isPreparationOpen" class="w-full flex flex-col gap-2 border-b border-ink-100 bg-slate-50/70 p-4 hover:bg-slate-100/70 transition text-left sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <ChevronDown class="h-5 w-5 shrink-0 text-ink-600 transition-transform" :style="{ transform: isPreparationOpen ? 'rotate(0deg)' : 'rotate(-90deg)' }" />
                    <div>
                        <h2 class="text-base font-bold text-ink-900">Preparar salida</h2>
                        <p class="text-xs text-ink-500">Todo lo que hace falta para dejar la actividad lista, en un vistazo.</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <div class="h-2 w-28 overflow-hidden rounded-full bg-ink-100">
                        <div class="h-full rounded-full bg-emerald-500 transition-all"
                            :style="{ width: `${Math.round((preparation.ready / preparation.total) * 100)}%` }" />
                    </div>
                    <span class="shrink-0 text-sm font-semibold text-ink-700">{{ preparation.ready }}/{{ preparation.total }} listo</span>
                </div>
            </button>
            <transition name="collapse" @enter="(el) => { el.style.overflow = 'hidden'; el.style.maxHeight = '0px'; el.offsetHeight; el.style.maxHeight = el.scrollHeight + 'px' }" @leave="(el) => { el.style.overflow = 'hidden'; el.style.maxHeight = el.scrollHeight + 'px'; el.offsetHeight; el.style.maxHeight = '0px' }">
                <ul v-show="isPreparationOpen" class="divide-y divide-ink-100">
                    <li v-for="step in preparation.steps" :key="step.key" class="flex items-center gap-3 p-3 sm:px-4">
                        <component :is="(prepStyle[step.status] || prepStyle.todo).icon" class="h-5 w-5 shrink-0" :class="(prepStyle[step.status] || prepStyle.todo).dot" />
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold" :class="(prepStyle[step.status] || prepStyle.todo).label">{{ step.label }}</p>
                            <p class="truncate text-xs text-ink-500">{{ step.detail }}</p>
                        </div>
                        <a v-if="step.href" :href="step.href" :target="step.href.includes('/pdf/') ? '_blank' : undefined"
                            class="inline-flex shrink-0 items-center gap-0.5 text-xs font-semibold text-brand-600 hover:text-brand-700 hover:underline">
                            {{ step.action_label }} <ChevronRight class="h-3.5 w-3.5" />
                        </a>
                        <button v-else-if="step.anchor" type="button" @click="scrollToAnchor(step.anchor)"
                            class="inline-flex shrink-0 items-center gap-0.5 text-xs font-semibold text-brand-600 hover:text-brand-700 hover:underline">
                            {{ step.action_label }} <ChevronRight class="h-3.5 w-3.5" />
                        </button>
                        <span v-else class="shrink-0 text-xs text-ink-300">{{ step.action_label }}</span>
                    </li>
                </ul>
            </transition>
        </section>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <!-- Detalles -->
                <section class="card-pad">
                    <dl class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                        <div><dt class="section-title">Inicio</dt><dd class="mt-1 text-sm font-medium text-ink-800">{{ formatDate(event.start_at) }}</dd></div>
                        <div><dt class="section-title">Fin</dt><dd class="mt-1 text-sm font-medium text-ink-800">{{ formatDate(event.end_at) ?? '—' }}</dd></div>
                        <div class="col-span-2 sm:col-span-4 border-b border-ink-100 pb-2 mb-2"></div>
                        <div><dt class="section-title">Lugar / Instalación</dt><dd class="mt-1 text-sm font-medium text-ink-800">{{ event.location ?? '—' }}</dd></div>
                        <div><dt class="section-title">Pueblo/Ciudad</dt><dd class="mt-1 text-sm font-medium text-ink-800">{{ event.city ?? '—' }}</dd></div>
                        <div><dt class="section-title">Ambientación</dt><dd class="mt-1 text-sm font-medium text-ink-800">{{ event.theme ?? '—' }}</dd></div>
                        <div><dt class="section-title">Coordinador/a</dt><dd class="mt-1 text-sm font-medium text-ink-800">{{ event.coordinator_name ?? '—' }}</dd></div>
                        <div class="col-span-2 sm:col-span-4 flex items-center gap-6 mt-2">
                            <span v-if="event.eucharist" class="inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-semibold text-brand-700 border border-brand-200">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 7H7"/></svg>
                                Eucaristía
                            </span>
                            <span v-if="event.hike" class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 border border-emerald-200">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 10V3L4 14h7v8l9-11h-7z"/></svg>
                                Marcha / Ruta
                            </span>
                        </div>
                        <div class="col-span-2 sm:col-span-4 mt-2">
                            <dt class="section-title">Ramas</dt>
                            <dd class="mt-1 flex flex-wrap gap-1">
                                <BadgeRama v-for="b in event.branches" :key="b" :rama="b" size="sm" />
                                <span v-if="!event.branches?.length" class="text-sm text-ink-400">Grupo</span>
                            </dd>
                        </div>
                    </dl>
                    <p v-if="event.description" class="mt-4 whitespace-pre-line border-t border-ink-100 pt-4 text-sm text-ink-600">{{ event.description }}</p>
                </section>

                <!-- Actividades del evento: lo que alimenta la ficha de salida MSC -->
                <section id="actividades" class="card-pad">
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                        <h2 class="section-title">Actividades y objetivos del plan</h2>
                        <div class="flex flex-wrap items-center gap-3">
                            <a :href="route('events.pdf.msc-outing', event.id)" target="_blank"
                                class="inline-flex items-center gap-1 text-xs font-semibold text-brand-600 hover:text-brand-700 hover:underline">
                                <FileText class="h-3.5 w-3.5" /> Ficha de salida MSC (PDF)
                            </a>
                            <button v-if="can.update" class="btn-secondary text-xs" @click="showActivityModal = true">
                                + Enlazar actividad
                            </button>
                        </div>
                    </div>

                    <!-- Qué le falta a la ficha para salir completa -->
                    <div v-if="mscReadiness" class="mb-4 grid grid-cols-2 gap-2 sm:grid-cols-4">
                        <div class="rounded-lg border border-ink-100 bg-slate-50 p-2 text-center">
                            <p class="text-lg font-bold text-ink-800">{{ mscReadiness.activities }}</p>
                            <p class="text-[11px] text-ink-500">Actividades</p>
                        </div>
                        <div class="rounded-lg border p-2 text-center"
                            :class="mscReadiness.activities > 0 &amp;&amp; mscReadiness.with_objective === mscReadiness.activities ? 'border-emerald-200 bg-emerald-50' : 'border-amber-200 bg-amber-50'">
                            <p class="text-lg font-bold text-ink-800">{{ mscReadiness.with_objective }}</p>
                            <p class="text-[11px] text-ink-500">Con objetivo</p>
                        </div>
                        <div class="rounded-lg border p-2 text-center"
                            :class="mscReadiness.without_slot === 0 ? 'border-emerald-200 bg-emerald-50' : 'border-amber-200 bg-amber-50'">
                            <p class="text-lg font-bold text-ink-800">{{ mscReadiness.without_slot }}</p>
                            <p class="text-[11px] text-ink-500">Sin franja</p>
                        </div>
                        <div class="rounded-lg border border-ink-100 bg-slate-50 p-2 text-center">
                            <p class="text-lg font-bold text-ink-800">{{ mscReadiness.scopes_covered }}/3</p>
                            <p class="text-[11px] text-ink-500">Ámbitos cubiertos</p>
                        </div>
                    </div>

                    <p v-if="!activities.length" class="rounded-lg border border-dashed border-ink-200 p-4 text-center text-sm text-ink-500">
                        Este evento todavía no tiene actividades. Enlaza las de la biblioteca para que la ficha de salida
                        salga rellena con sus objetivos, su número y su hueco en la rejilla.
                    </p>

                    <div v-else class="space-y-6 border-l-2 border-slate-100 pl-4">
                        <template v-for="(dayActivities, day) in activitiesByDay" :key="day">
                            <div class="relative">
                                <div class="absolute -left-[23px] top-1 h-3 w-3 rounded-full bg-slate-300 border-[3px] border-white"></div>
                                <h3 class="mb-3 font-bold text-slate-800 text-sm uppercase tracking-wider">{{ day || 'Día no especificado' }}</h3>

                                <div class="space-y-4">
                                    <template v-for="(slotActivities, slot) in groupBySlot(dayActivities)" :key="slot">
                                        <div class="pl-4">
                                            <h4 class="mb-2 text-xs font-semibold text-slate-500">
                                                {{ slotActivities[0]?.time_slot_label || 'Sin franja horaria' }}
                                            </h4>
                                            <div class="grid gap-2 lg:grid-cols-2">
                                                <div
                                                    v-for="act in slotActivities"
                                                    :key="act.id"
                                                    class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm"
                                                >
                                                    <div class="flex items-start justify-between gap-2">
                                                        <span class="text-xs font-bold text-slate-400">
                                                            Actividad {{ act.activity_number || '-' }}
                                                        </span>
                                                        <div class="flex items-center gap-2">
                                                            <span v-if="act.type_label" class="inline-flex rounded-md bg-brand-50 px-1.5 py-0.5 text-[10px] font-semibold text-brand-700">
                                                                {{ act.type_label }}
                                                            </span>
                                                            <span v-if="act.duration_minutes" class="inline-flex rounded-md bg-slate-50 px-1.5 py-0.5 text-[10px] font-semibold text-slate-500">
                                                                {{ act.duration_minutes }} min
                                                            </span>
                                                        </div>
                                                    </div>

                                                    <Link :href="route('activities.show', act.id)" class="mt-1 block text-sm font-semibold text-slate-700 hover:text-brand-700 hover:underline">
                                                        {{ act.title }}
                                                    </Link>
                                                    <p v-if="act.owner" class="text-[11px] text-slate-400">Encargado: {{ act.owner }}</p>

                                                    <!-- El objetivo del plan de rama es lo que llena las tablas de ámbito del impreso -->
                                                    <ul v-if="act.objectives.length" class="mt-2 space-y-1 border-t border-slate-100 pt-2">
                                                        <li v-for="o in act.objectives" :key="o.id" class="text-[11px] leading-snug">
                                                            <span v-if="o.scope_label" :class="o.scope_classes" class="mr-1 inline-flex rounded-full border px-1.5 py-0.5 text-[9px] font-bold uppercase">
                                                                {{ o.scope_label }}
                                                            </span>
                                                            <span class="text-slate-600">{{ o.goal }}</span>
                                                            <span v-if="o.content" class="block text-slate-400">{{ o.content }}</span>
                                                        </li>
                                                    </ul>

                                                    <p v-if="act.missing.length" class="mt-2 rounded-md bg-amber-50 px-2 py-1 text-[11px] text-amber-800">
                                                        Le falta: {{ act.missing.join(', ') }}.
                                                    </p>

                                                    <button
                                                        v-if="can.update"
                                                        class="mt-2 text-[11px] font-semibold text-red-600 hover:underline"
                                                        @click="unlinkActivity(act.id)"
                                                    >
                                                        Quitar del evento
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </section>

                <!-- Validador legal (semáforo) -->
                <section v-if="campRatio" id="semaforo" class="overflow-hidden rounded-xl border shadow-card" :class="semaphore.ring">
                    <div class="flex items-center gap-3 p-4" :class="semaphore.bg">
                        <DashIcon :name="statusIcon[campRatio.status]" class="h-8 w-8" />
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
                <section v-if="event.requires_enrollment" id="inscripciones" class="card-pad">
                    <div class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-100 pb-3">
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Inscripciones y Responsables</h2>
                            <p class="text-xs text-slate-500">{{ enrollStats.enrolled }} inscritos · {{ enrollStats.withAuth }} con autorización</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <a
                                v-if="event.requires_enrollment"
                                :href="route('events.pdf.zip', event.id)"
                                target="_blank"
                                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 hover:border-slate-300 transition shadow-sm group"
                                title="Descargar paquete ZIP con todas las autorizaciones"
                            >
                                <FolderArchive class="h-4 w-4 text-slate-500 group-hover:text-slate-700 transition" /> Descargar ZIP
                            </a>
                            <button v-if="can.update" type="button" class="inline-flex items-center gap-1.5 rounded-xl border border-emerald-300 bg-emerald-50 px-3.5 py-2 text-xs font-bold text-emerald-900 hover:bg-emerald-100 transition shadow-sm group" @click="showExceptionalModal = true">
                                <UserPlus class="h-4 w-4 text-emerald-600 group-hover:text-emerald-700 transition" /> Añadir Asistente
                            </button>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-ink-200 text-sm">
                            <thead>
                                <tr class="text-left text-xs uppercase text-ink-400">
                                    <th class="py-2.5 pr-3">Nombre</th>
                                    <th class="py-2.5 pr-3">Rama</th>
                                    <th class="py-2.5 pr-3">Inscrito</th>
                                    <th class="py-2.5 pr-3">Estado</th>
                                    <th class="py-2.5 pr-3 text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-ink-100">
                                <tr v-for="en in enrollments" :key="en.id" class="hover:bg-slate-50/60 transition">
                                    <td class="py-2.5 pr-3 font-medium text-ink-800 max-w-[140px] truncate sm:max-w-[200px]" :title="en.member_name">{{ en.member_name }}</td>
                                    <td class="py-2.5 pr-3 text-ink-600">{{ en.role_label }}</td>
                                    <td class="py-2.5 pr-3">
                                        <input type="checkbox" :checked="en.enrolled" @change="toggleEnrolled(en)" class="rounded border-ink-300 text-brand-600 focus:ring-brand-500" />
                                    </td>
                                    <td class="py-2.5 pr-3">
                                        <BadgeEstado v-if="en.has_authorization || en.confirmed_at" label="Autorización ✓" color="green" />
                                        <BadgeEstado v-else-if="en.notes && en.notes.startsWith('No asiste')" label="No asistirá" color="red" :title="en.notes" />
                                        <BadgeEstado v-else label="Pendiente" color="gray" />
                                    </td>
                                    <td class="py-2.5 pr-3 text-right">
                                        <div class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200/80 bg-slate-50/80 p-1 shadow-sm">
                                            <!-- 💬 WhatsApp -->
                                            <a
                                                v-if="en.public_url && (en.family_phone || en.phone)"
                                                :href="whatsAppAuthorizationUrl(en)"
                                                target="_blank"
                                                class="group/tt relative inline-flex h-8 w-8 items-center justify-center rounded-lg bg-white text-emerald-600 shadow-sm transition hover:bg-emerald-600 hover:text-white"
                                                title="Enviar WhatsApp a la familia"
                                            >
                                                <MessageCircle class="h-4 w-4" />
                                                <span class="absolute bottom-full mb-1.5 hidden group-hover/tt:block whitespace-nowrap rounded-lg bg-slate-900 px-2 py-0.5 text-[10px] font-medium text-white shadow-md z-30">
                                                    Enviar WhatsApp
                                                </span>
                                            </a>

                                            <!-- 📄 PDF Oficial -->
                                            <a
                                                :href="route('events.pdf.authorization', [event.id, en.id])"
                                                target="_blank"
                                                class="group/tt relative inline-flex h-8 w-8 items-center justify-center rounded-lg bg-white text-slate-700 shadow-sm transition hover:bg-slate-800 hover:text-white"
                                                title="Ver / Descargar PDF Oficial"
                                            >
                                                <FileDown class="h-4 w-4" />
                                                <span class="absolute bottom-full mb-1.5 hidden group-hover/tt:block whitespace-nowrap rounded-lg bg-slate-900 px-2 py-0.5 text-[10px] font-medium text-white shadow-md z-30">
                                                    Ver PDF Oficial
                                                </span>
                                            </a>

                                            <!-- 📋 Copiar Enlace -->
                                            <button
                                                v-if="en.public_url"
                                                type="button"
                                                class="group/tt relative inline-flex h-8 w-8 items-center justify-center rounded-lg bg-white text-slate-700 shadow-sm transition hover:bg-slate-800 hover:text-white"
                                                @click="copyLink(en.public_url)"
                                                title="Copiar enlace público"
                                            >
                                                <Copy class="h-4 w-4" />
                                                <span class="absolute bottom-full mb-1.5 hidden group-hover/tt:block whitespace-nowrap rounded-lg bg-slate-900 px-2 py-0.5 text-[10px] font-medium text-white shadow-md z-30">
                                                    Copiar enlace
                                                </span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="enrollments.length === 0">
                                    <td colspan="5" class="py-6 text-center text-ink-400">No hay miembros inscribibles para las ramas de este evento.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- Checklist + reparto de tareas del kraal -->
                <section v-if="event.requires_enrollment" id="checklist" class="card-pad">
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="text-base font-bold text-ink-800">Checklist y tareas del kraal</h2>
                        <span class="text-xs font-medium text-ink-500">{{ checklistDone }}/{{ checklistItems.length }}</span>
                    </div>
                    <ul class="space-y-3">
                        <li v-for="item in checklistItems" :key="item.id" class="rounded-lg border border-ink-100 p-2.5">
                            <div class="flex items-center justify-between gap-2">
                                <label class="flex items-center gap-2 text-sm text-ink-700">
                                    <input type="checkbox" :checked="item.done" @change="toggleChecklist(item)" class="rounded border-ink-300 text-brand-600 focus:ring-brand-500" />
                                    <span :class="item.done ? 'text-ink-400 line-through' : ''">{{ item.label }}</span>
                                </label>
                                <button type="button" class="text-ink-400 hover:text-brand-600 transition" @click="removeChecklistItem(item)"><X class="h-4 w-4" /></button>
                            </div>
                            <div v-if="can.update" class="mt-2 flex flex-wrap items-center gap-2 pl-6">
                                <select v-model="item.assigned_to" class="input min-w-0 max-w-full py-1 text-xs" @change="patchAssignment(item)">
                                    <option :value="null">Sin asignar</option>
                                    <option v-for="u in staffUsers" :key="u.id" :value="u.id">{{ u.name }}</option>
                                </select>
                                <input v-model="item.due_at" type="date" class="input min-w-0 py-1 text-xs" title="Fecha límite" @change="patchAssignment(item)" />
                            </div>
                            <p v-else-if="item.assignee_name || item.due_at" class="mt-1 pl-6 text-xs text-ink-500">
                                {{ item.assignee_name || 'Sin asignar' }}<span v-if="item.due_at"> · vence {{ item.due_at }}</span>
                            </p>
                        </li>
                    </ul>
                    <div v-if="can.update" class="mt-3 space-y-2 border-t border-ink-100 pt-3">
                        <input v-model="newChecklistLabel" type="text" placeholder="Nueva tarea o punto de documentación…" class="input w-full" @keyup.enter="addChecklistItem" />
                        <div class="flex flex-wrap gap-2">
                            <select v-model="newChecklistAssignee" class="input min-w-0 flex-1 py-1.5 text-sm">
                                <option value="">Sin asignar</option>
                                <option v-for="u in staffUsers" :key="u.id" :value="u.id">{{ u.name }}</option>
                            </select>
                            <input v-model="newChecklistDue" type="date" class="input min-w-0 py-1.5 text-sm" title="Fecha límite" />
                            <button type="button" class="btn-secondary btn-sm" @click="addChecklistItem">Añadir</button>
                        </div>
                    </div>
                </section>
            </div>

            <div class="space-y-6">
                <section v-if="event.requires_enrollment && can.update" class="card-pad">
                    <h2 class="section-title mb-3">Documentos</h2>
                    <div class="flex flex-col gap-2">
                        <a :href="route('events.pdf.msc-outing', event.id)" target="_blank" class="btn-secondary justify-start group"><Tent class="mr-2 h-4 w-4 text-slate-500 group-hover:text-slate-700 transition" /> Ficha de salida MSC (PDF)</a>
                        <a :href="route('events.pdf.dossier', event.id)" target="_blank" class="btn-secondary justify-start group"><Tent class="mr-2 h-4 w-4 text-slate-500 group-hover:text-slate-700 transition" /> Dossier de Programación (PDF)</a>
                        <a :href="route('events.pdf.materials', event.id)" target="_blank" class="btn-secondary justify-start group"><Box class="mr-2 h-4 w-4 text-slate-500 group-hover:text-slate-700 transition" /> Lista de material (PDF)</a>
                        <a :href="route('events.pdf.attendees', event.id)" target="_blank" class="btn-secondary justify-start group"><Users class="mr-2 h-4 w-4 text-slate-500 group-hover:text-slate-700 transition" /> Listado de asistentes (PDF)</a>
                        <a :href="route('events.pdf.circular', event.id)" target="_blank" class="btn-secondary justify-start group"><FileText class="mr-2 h-4 w-4 text-slate-500 group-hover:text-slate-700 transition" /> Circular (PDF)</a>
                    </div>
                </section>

                <section v-if="event.requires_enrollment" id="cobros" class="card-pad">
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

        <!-- Modal Añadir Asistente Excepcional (Cualquier Rama o Responsable) -->
        <Modal :show="showActivityModal" title="Enlazar una actividad al evento" @close="showActivityModal = false">
        <p class="mb-3 text-sm text-ink-500">
            Elige una actividad de la biblioteca. Su objetivo del plan de rama y su franja horaria son los
            que rellenan la ficha de salida MSC.
        </p>
        <FormField
            v-model="activityToLink"
            type="select"
            label="Actividad"
            :options="[{ value: '', label: 'Elige una actividad...' }, ...availableActivities.map((a) => ({ value: a.id, label: `${a.title} (${a.branch_label})` }))]"
        />
        <p v-if="!availableActivities.length" class="mt-2 text-xs text-ink-400">
            No quedan actividades sin enlazar para las ramas de este evento.
            <Link :href="route('activities.create')" class="font-semibold text-brand-700 hover:underline">Crear una actividad</Link>.
        </p>
        <template #footer>
            <button class="rounded-md px-4 py-2 text-sm font-medium text-ink-600 hover:bg-ink-100" @click="showActivityModal = false">
                Cancelar
            </button>
            <button
                class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700 disabled:opacity-50"
                :disabled="!activityToLink"
                @click="linkActivity"
            >
                Enlazar
            </button>
        </template>
    </Modal>

    <Modal :show="showExceptionalModal" max-width="md" @close="showExceptionalModal = false">
            <div class="p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-base font-bold text-slate-900">Añadir Asistente / Responsable Excepcional</h3>
                    <button class="text-slate-400 hover:text-slate-600" @click="showExceptionalModal = false">✕</button>
                </div>

                <p class="text-xs text-slate-600 leading-relaxed">
                    Añade a cualquier responsable o educando de otra rama que vaya a asistir de manera excepcional a esta actividad o acampada. Serán incluidos en el cálculo legal de ratios automáticamente.
                </p>

                <div class="space-y-2">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500">Seleccionar Miembro del Censo</label>
                    <select v-model="selectedMemberId" class="w-full rounded-xl border-slate-200 text-xs font-semibold py-2.5">
                        <option value="">-- Seleccionar de todo el grupo --</option>
                        <option v-for="m in availableExceptionalMembers" :key="m.id" :value="m.id">
                            {{ m.full_name }} ({{ m.role_label }})
                        </option>
                    </select>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50 transition" @click="showExceptionalModal = false">
                        Cancelar
                    </button>
                    <button
                        type="button"
                        class="rounded-xl bg-emerald-600 px-5 py-2 text-xs font-bold text-white hover:bg-emerald-700 transition shadow-xs"
                        :disabled="!selectedMemberId"
                        @click="addExceptionalMember"
                    >
                        ➕ Añadir a la Actividad
                    </button>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>

<style scoped>
.collapse-enter-active,
.collapse-leave-active {
    transition: max-height 0.3s ease-in-out;
}

.collapse-enter-from,
.collapse-leave-to {
    max-height: 0;
    overflow: hidden;
}
</style>
