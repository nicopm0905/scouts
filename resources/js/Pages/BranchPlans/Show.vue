<script setup>
import { computed, ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import BadgeEstado from '@/Components/Shared/BadgeEstado.vue'
import Modal from '@/Components/Shared/Modal.vue'
import FormField from '@/Components/Shared/FormField.vue'
import ConfirmButton from '@/Components/Shared/ConfirmButton.vue'
import { useToast } from '@/composables/useToast'

defineOptions({ layout: AppLayout })

const props = defineProps({
    plan: { type: Object, required: true },
    canManage: { type: Boolean, default: false },
})

const toast = useToast()
const showObjectiveModal = ref(false)
const editingObjective = ref(null)

const terms = [1, 2, 3]

const objectivesByTerm = computed(() => {
    const map = { 1: [], 2: [], 3: [], sin_trimestre: [] }
    for (const o of props.plan.objectives) {
        const key = o.term ?? 'sin_trimestre'
        map[key] = map[key] ?? []
        map[key].push(o)
    }
    return map
})

const statusOptions = [
    { value: 'pendiente', label: 'Pendiente' },
    { value: 'en_curso', label: 'En curso' },
    { value: 'logrado', label: 'Logrado' },
]

const form = useForm({
    description: '',
    term: '',
    status: 'pendiente',
})

function openCreate() {
    editingObjective.value = null
    form.reset()
    form.status = 'pendiente'
    showObjectiveModal.value = true
}

function openEdit(objective) {
    editingObjective.value = objective
    form.description = objective.description
    form.term = objective.term ?? ''
    form.status = objective.status
    showObjectiveModal.value = true
}

function submit() {
    const onSuccess = () => {
        showObjectiveModal.value = false
        toast.success('Guardado correctamente.')
    }

    if (editingObjective.value) {
        form.put(route('branch-plans.objectives.update', editingObjective.value.id), { onSuccess })
    } else {
        form.post(route('branch-plans.objectives.store', props.plan.id), { onSuccess })
    }
}

function destroyObjective(objective) {
    form.delete(route('branch-plans.objectives.destroy', objective.id), {
        onSuccess: () => toast.success('Objetivo eliminado.'),
    })
}

function badgeColor(status) {
    return { pendiente: 'gray', en_curso: 'blue', logrado: 'green' }[status] ?? 'gray'
}

function termStats(term) {
    const list = objectivesByTerm.value[term] ?? []
    const done = list.filter((o) => o.status === 'logrado').length
    return { done, total: list.length, pct: list.length ? Math.round((done / list.length) * 100) : 0 }
}
</script>

<template>
    <Head :title="`Plan ${plan.branch_label} ${plan.school_year}`" />

    <PageHeader :title="`Plan de ${plan.branch_label} — ${plan.school_year}`" :subtitle="plan.description">
        <template #actions>
            <button
                v-if="canManage"
                class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700"
                @click="openCreate"
            >
                + Añadir objetivo
            </button>
        </template>
    </PageHeader>

    <div class="mb-6 card-pad">
        <p class="section-title mb-2">Progreso general · base de la memoria anual</p>
        <div class="flex items-center gap-3">
            <div class="h-3 w-full max-w-md overflow-hidden rounded-full bg-ink-100">
                <div class="h-full rounded-full bg-emerald-500 transition-all" :style="{ width: plan.completion_percentage + '%' }" />
            </div>
            <span class="text-lg font-bold text-emerald-600">{{ plan.completion_percentage }}%</span>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div v-for="term in [...terms, 'sin_trimestre']" :key="term" class="card p-4">
            <div class="mb-3">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-ink-800">{{ term === 'sin_trimestre' ? 'Sin trimestre' : `Trimestre ${term}` }}</h3>
                    <span class="text-xs text-ink-400">{{ termStats(term).done }}/{{ termStats(term).total }}</span>
                </div>
                <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-ink-100">
                    <div class="h-full rounded-full bg-emerald-500" :style="{ width: termStats(term).pct + '%' }" />
                </div>
            </div>
            <ul class="space-y-2">
                <li
                    v-for="o in objectivesByTerm[term]"
                    :key="o.id"
                    class="rounded-md border border-ink-100 p-2 text-sm"
                >
                    <div class="flex items-start justify-between gap-2">
                        <span class="text-ink-700">{{ o.description }}</span>
                    </div>
                    <div class="mt-2 flex items-center justify-between">
                        <BadgeEstado :label="o.status_label" :color="badgeColor(o.status)" />
                        <div v-if="canManage" class="flex gap-2">
                            <button class="text-xs text-brand-700 hover:underline" @click="openEdit(o)">Editar</button>
                            <ConfirmButton
                                message="¿Eliminar este objetivo?"
                                confirm-label="Eliminar"
                                @confirm="destroyObjective(o)"
                            >
                                <span class="text-xs text-red-600 hover:underline">Eliminar</span>
                            </ConfirmButton>
                        </div>
                    </div>
                </li>
                <li v-if="objectivesByTerm[term].length === 0" class="text-xs text-ink-400">Sin objetivos.</li>
            </ul>
        </div>
    </div>

    <Modal :show="showObjectiveModal" :title="editingObjective ? 'Editar objetivo' : 'Nuevo objetivo'" @close="showObjectiveModal = false">
        <form class="space-y-4" @submit.prevent="submit">
            <FormField v-model="form.description" type="textarea" label="Descripción" :error="form.errors.description" required />
            <FormField v-model="form.term" type="select" label="Trimestre" :options="[{ value: '', label: 'Sin trimestre' }, { value: 1, label: '1' }, { value: 2, label: '2' }, { value: 3, label: '3' }]" :error="form.errors.term" />
            <FormField v-model="form.status" type="select" label="Estado" :options="statusOptions" :error="form.errors.status" />
        </form>
        <template #footer>
            <button class="rounded-md px-4 py-2 text-sm font-medium text-ink-600 hover:bg-ink-100" @click="showObjectiveModal = false">
                Cancelar
            </button>
            <button class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700" @click="submit">
                Guardar
            </button>
        </template>
    </Modal>
</template>
