<script setup>
import { ref } from 'vue'
import { Link, useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import ConfirmButton from '@/Components/Shared/ConfirmButton.vue'
import { useToast } from '@/composables/useToast'

defineOptions({ layout: AppLayout })

const props = defineProps({
    activity: { type: Object, required: true },
    canManage: { type: Boolean, default: false },
    availableEvents: { type: Array, default: () => [] },
    availableObjectives: { type: Array, default: () => [] },
})

const toast = useToast()
const selectedEvent = ref('')
const selectedObjective = ref('')

function duplicate() {
    router.post(route('activities.duplicate', props.activity.id), {}, {
        onSuccess: () => toast.success('Actividad duplicada correctamente.'),
    })
}

function destroyActivity() {
    router.delete(route('activities.destroy', props.activity.id), {
        onSuccess: () => toast.success('Actividad eliminada.'),
    })
}

function linkEvent() {
    if (!selectedEvent.value) return
    router.post(route('activities.events.attach', [props.activity.id, selectedEvent.value]), {}, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            selectedEvent.value = ''
            toast.success('Actividad programada en el evento.')
        },
    })
}

function unlinkEvent(eventId) {
    router.delete(route('activities.events.detach', [props.activity.id, eventId]), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => toast.success('Actividad retirada del evento.'),
    })
}

function linkObjective() {
    if (!selectedObjective.value) return
    router.post(route('activities.objectives.attach', [props.activity.id, selectedObjective.value]), {}, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            selectedObjective.value = ''
            toast.success('Actividad vinculada al objetivo.')
        },
    })
}

function unlinkObjective(objectiveId) {
    router.delete(route('activities.objectives.detach', [props.activity.id, objectiveId]), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => toast.success('Vínculo eliminado.'),
    })
}
</script>

<template>
    <Head :title="activity.title" />

    <PageHeader :title="activity.title" :subtitle="`${activity.branch_label} · ${activity.duration_minutes ?? '—'} min`" icon="sparkles">
        <template #actions>
            <button v-if="canManage" class="rounded-md bg-ink-100 px-4 py-2 text-sm font-medium text-ink-700 hover:bg-ink-200" @click="duplicate">
                Duplicar
            </button>
            <Link v-if="canManage" :href="route('activities.edit', activity.id)" class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">
                Editar
            </Link>
            <ConfirmButton v-if="canManage" message="¿Eliminar esta actividad?" confirm-label="Eliminar" @confirm="destroyActivity">
                <span class="inline-flex items-center rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">
                    Eliminar
                </span>
            </ConfirmButton>
        </template>
    </PageHeader>

    <div class="mb-4 flex flex-wrap gap-4">
        <div v-if="activity.activity_type_label" class="flex items-center gap-2 rounded-full bg-brand-50 px-3 py-1 text-sm font-semibold text-brand-700">
            {{ activity.activity_type_label }}
        </div>
        <div v-if="activity.owner" class="flex items-center gap-2 rounded-full bg-ink-100 px-3 py-1 text-sm font-medium text-ink-700">
            Encargado: {{ activity.owner }}
        </div>
        <div v-if="activity.scheduled_date" class="flex items-center gap-2 rounded-full bg-ink-100 px-3 py-1 text-sm font-medium text-ink-700">
            Fecha: {{ activity.scheduled_date }}
        </div>
        <div v-if="activity.place" class="flex items-center gap-2 rounded-full bg-ink-100 px-3 py-1 text-sm font-medium text-ink-700">
            Sitio: {{ activity.place }}
        </div>
        <div v-if="activity.day_number" class="flex items-center gap-2 rounded-full bg-ink-100 px-3 py-1 text-sm font-medium text-ink-700">
            <svg class="h-4 w-4 text-ink-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5m-9-6h.008v.008H12v-.008zM12 15h.008v.008H12V15zm0 2.25h.008v.008H12v-.008zM9.75 15h.008v.008H9.75V15zm0 2.25h.008v.008H9.75v-.008zM7.5 15h.008v.008H7.5V15zm0 2.25h.008v.008H7.5v-.008zm6.75-4.5h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V15zm0 2.25h.008v.008h-.008v-.008zm2.25-4.5h.008v.008H16.5v-.008zm0 2.25h.008v.008H16.5V15z" /></svg>
            {{ activity.day_number }}
        </div>
        <div v-if="activity.time_slot" class="flex items-center gap-2 rounded-full bg-ink-100 px-3 py-1 text-sm font-medium text-ink-700">
            <svg class="h-4 w-4 text-ink-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            Franja: {{ activity.time_slot }}
        </div>
        <div v-if="activity.activity_number" class="flex items-center gap-2 rounded-full bg-ink-100 px-3 py-1 text-sm font-medium text-ink-700">
            <svg class="h-4 w-4 text-ink-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 8.25h15m-16.5 7.5h15m-1.8-13.5l-3.9 19.5m-5.1-19.5l-3.9 19.5" /></svg>
            Actividad Nº: {{ activity.activity_number }}
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <div class="rounded-lg border border-ink-200 bg-white p-4">
                <h3 class="mb-2 font-semibold text-ink-700">Objetivos educativos</h3>
                <p class="whitespace-pre-line text-sm text-ink-600">{{ activity.objectives_text || 'Sin especificar.' }}</p>
            </div>
            <div class="rounded-lg border border-ink-200 bg-white p-4">
                <h3 class="mb-2 font-semibold text-ink-700">Desarrollo</h3>
                <p class="whitespace-pre-line text-sm text-ink-600">{{ activity.development || 'Sin especificar.' }}</p>
            </div>
            <div v-if="activity.evaluation" class="rounded-lg border border-ink-200 bg-white p-4">
                <h3 class="mb-2 font-semibold text-ink-700">Evaluación — ¿cómo ha salido?</h3>
                <p class="whitespace-pre-line text-sm text-ink-600">{{ activity.evaluation }}</p>
            </div>
            <div v-if="activity.attachments.length" class="rounded-lg border border-ink-200 bg-white p-4">
                <h3 class="mb-2 font-semibold text-ink-700">Adjuntos</h3>
                <ul class="space-y-1 text-sm">
                    <li v-for="a in activity.attachments" :key="a.id">
                        <a :href="a.web_view_link" target="_blank" class="text-brand-700 hover:underline">{{ a.id }}</a>
                    </li>
                </ul>
            </div>
        </div>

        <div class="space-y-4">
            <div class="rounded-lg border border-ink-200 bg-white p-4">
                <h3 class="mb-2 font-semibold text-ink-700">Materiales</h3>
                <p v-if="activity.materials_text" class="whitespace-pre-line text-sm text-ink-600 mb-3">{{ activity.materials_text }}</p>
                <ul class="space-y-1 text-sm text-ink-600 border-t border-ink-100 pt-3">
                    <li v-for="m in activity.materials" :key="m.id">
                        {{ m.name }} × {{ m.quantity }}
                        <span v-if="m.inventory_item_name" class="text-xs text-ink-400">({{ m.inventory_item_name }})</span>
                    </li>
                    <li v-if="activity.materials.length === 0" class="text-xs text-ink-400">Sin materiales vinculados al inventario.</li>
                </ul>
            </div>

            <div class="rounded-lg border border-ink-200 bg-white p-4">
                <h3 class="mb-2 font-semibold text-ink-700">Eventos programados</h3>
                <ul class="mb-3 space-y-1 text-sm text-ink-600">
                    <li v-for="e in activity.events" :key="e.id" class="flex items-center justify-between">
                        <span>{{ e.title }}</span>
                        <button v-if="canManage" class="text-xs text-red-600 hover:underline" @click="unlinkEvent(e.id)">Quitar</button>
                    </li>
                    <li v-if="activity.events.length === 0" class="text-xs text-ink-400">Sin eventos programados.</li>
                </ul>
                <div v-if="canManage" class="flex gap-2">
                    <select v-model="selectedEvent" class="min-w-0 flex-1 rounded-md border-ink-300 text-sm">
                        <option value="">Elegir evento…</option>
                        <option v-for="e in availableEvents" :key="e.id" :value="e.id">{{ e.title }}</option>
                    </select>
                    <button class="shrink-0 rounded-md bg-brand-600 px-3 py-1 text-sm text-white hover:bg-brand-700" @click="linkEvent">
                        Programar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Objetivos del plan: a lo ancho para que quepan bien el texto y el selector -->
    <div class="mt-4 rounded-lg border border-ink-200 bg-white p-4">
        <h3 class="mb-2 font-semibold text-ink-700">Objetivos del plan vinculados</h3>
        <ul class="mb-3 space-y-1 text-sm text-ink-600">
            <li v-for="o in activity.objectives" :key="o.id" class="flex items-start justify-between gap-3">
                <span class="min-w-0"><span class="font-medium text-ink-700">{{ o.plan_label }}:</span> {{ o.description }}</span>
                <button v-if="canManage" class="shrink-0 text-xs text-red-600 hover:underline" @click="unlinkObjective(o.id)">Quitar</button>
            </li>
            <li v-if="activity.objectives.length === 0" class="text-xs text-ink-400">Sin vincular.</li>
        </ul>
        <div v-if="canManage" class="flex gap-2">
            <select v-model="selectedObjective" class="min-w-0 flex-1 rounded-md border-ink-300 text-sm">
                <option value="">Elegir objetivo…</option>
                <option v-for="o in availableObjectives" :key="o.id" :value="o.id">{{ o.label }}</option>
            </select>
            <button class="shrink-0 rounded-md bg-brand-600 px-4 py-1 text-sm text-white hover:bg-brand-700" @click="linkObjective">
                Vincular
            </button>
        </div>
    </div>
</template>
