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
        onSuccess: () => {
            selectedEvent.value = ''
            toast.success('Actividad programada en el evento.')
        },
    })
}

function unlinkEvent(eventId) {
    router.delete(route('activities.events.detach', [props.activity.id, eventId]), {
        onSuccess: () => toast.success('Actividad retirada del evento.'),
    })
}

function linkObjective() {
    if (!selectedObjective.value) return
    router.post(route('activities.objectives.attach', [props.activity.id, selectedObjective.value]), {}, {
        onSuccess: () => {
            selectedObjective.value = ''
            toast.success('Actividad vinculada al objetivo.')
        },
    })
}

function unlinkObjective(objectiveId) {
    router.delete(route('activities.objectives.detach', [props.activity.id, objectiveId]), {
        onSuccess: () => toast.success('Vínculo eliminado.'),
    })
}
</script>

<template>
    <Head :title="activity.title" />

    <PageHeader :title="activity.title" :subtitle="`${activity.branch_label} · ${activity.duration_minutes ?? '—'} min`">
        <template #actions>
            <button v-if="canManage" class="rounded-md bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-200" @click="duplicate">
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

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <div class="rounded-lg border border-slate-200 bg-white p-4">
                <h3 class="mb-2 font-semibold text-slate-700">Objetivos educativos</h3>
                <p class="whitespace-pre-line text-sm text-slate-600">{{ activity.objectives_text || 'Sin especificar.' }}</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white p-4">
                <h3 class="mb-2 font-semibold text-slate-700">Desarrollo</h3>
                <p class="whitespace-pre-line text-sm text-slate-600">{{ activity.development || 'Sin especificar.' }}</p>
            </div>
            <div v-if="activity.attachments.length" class="rounded-lg border border-slate-200 bg-white p-4">
                <h3 class="mb-2 font-semibold text-slate-700">Adjuntos</h3>
                <ul class="space-y-1 text-sm">
                    <li v-for="a in activity.attachments" :key="a.id">
                        <a :href="a.web_view_link" target="_blank" class="text-brand-700 hover:underline">{{ a.id }}</a>
                    </li>
                </ul>
            </div>
        </div>

        <div class="space-y-4">
            <div class="rounded-lg border border-slate-200 bg-white p-4">
                <h3 class="mb-2 font-semibold text-slate-700">Materiales</h3>
                <ul class="space-y-1 text-sm text-slate-600">
                    <li v-for="m in activity.materials" :key="m.id">
                        {{ m.name }} × {{ m.quantity }}
                        <span v-if="m.inventory_item_name" class="text-xs text-slate-400">({{ m.inventory_item_name }})</span>
                    </li>
                    <li v-if="activity.materials.length === 0" class="text-xs text-slate-400">Sin materiales.</li>
                </ul>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-4">
                <h3 class="mb-2 font-semibold text-slate-700">Eventos programados</h3>
                <ul class="mb-3 space-y-1 text-sm text-slate-600">
                    <li v-for="e in activity.events" :key="e.id" class="flex items-center justify-between">
                        <span>{{ e.title }}</span>
                        <button v-if="canManage" class="text-xs text-red-600 hover:underline" @click="unlinkEvent(e.id)">Quitar</button>
                    </li>
                    <li v-if="activity.events.length === 0" class="text-xs text-slate-400">Sin eventos programados.</li>
                </ul>
                <div v-if="canManage" class="flex gap-2">
                    <select v-model="selectedEvent" class="flex-1 rounded-md border-slate-300 text-sm">
                        <option value="">Elegir evento…</option>
                        <option v-for="e in availableEvents" :key="e.id" :value="e.id">{{ e.title }}</option>
                    </select>
                    <button class="rounded-md bg-brand-600 px-3 py-1 text-sm text-white hover:bg-brand-700" @click="linkEvent">
                        Programar
                    </button>
                </div>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-4">
                <h3 class="mb-2 font-semibold text-slate-700">Objetivos del plan vinculados</h3>
                <ul class="mb-3 space-y-1 text-sm text-slate-600">
                    <li v-for="o in activity.objectives" :key="o.id" class="flex items-center justify-between">
                        <span>{{ o.plan_label }}: {{ o.description }}</span>
                        <button v-if="canManage" class="text-xs text-red-600 hover:underline" @click="unlinkObjective(o.id)">Quitar</button>
                    </li>
                    <li v-if="activity.objectives.length === 0" class="text-xs text-slate-400">Sin vincular.</li>
                </ul>
                <div v-if="canManage" class="flex gap-2">
                    <select v-model="selectedObjective" class="flex-1 rounded-md border-slate-300 text-sm">
                        <option value="">Elegir objetivo…</option>
                        <option v-for="o in availableObjectives" :key="o.id" :value="o.id">{{ o.label }}</option>
                    </select>
                    <button class="rounded-md bg-brand-600 px-3 py-1 text-sm text-white hover:bg-brand-700" @click="linkObjective">
                        Vincular
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
