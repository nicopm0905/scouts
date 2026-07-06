<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import Modal from '@/Components/Shared/Modal.vue'
import FormField from '@/Components/Shared/FormField.vue'
import DataTable from '@/Components/Shared/DataTable.vue'
import ConfirmButton from '@/Components/Shared/ConfirmButton.vue'
import { useToast } from '@/composables/useToast'

const props = defineProps({
    minutes: { type: Array, default: () => [] },
    attendeeOptions: { type: Array, default: () => [] },
    types: { type: Array, default: () => [] },
    can: { type: Object, default: () => ({}) },
})

const toast = useToast()

const columns = [
    { key: 'held_on', label: 'Fecha', sortable: true },
    { key: 'title', label: 'Título', sortable: true },
    { key: 'type', label: 'Tipo' },
    { key: 'location', label: 'Lugar' },
]

const showModal = ref(false)
const editing = ref(null)

function blankItem() {
    return { topic: '', discussion: '', agreement: '' }
}

const form = useForm({
    title: '',
    type: props.types[0]?.value ?? 'actas_consejo',
    held_on: '',
    location: '',
    attendee_ids: [],
    items: [blankItem()],
})

function typeLabel(value) {
    return props.types.find((t) => t.value === value)?.label ?? value
}

function openCreate() {
    editing.value = null
    form.reset()
    form.type = props.types[0]?.value ?? 'actas_consejo'
    form.items = [blankItem()]
    showModal.value = true
}

function openEdit(minute) {
    editing.value = minute
    form.title = minute.title
    form.type = minute.type
    form.held_on = minute.held_on
    form.location = minute.location ?? ''
    form.attendee_ids = [...minute.attendee_ids]
    form.items = minute.items.length ? minute.items.map((i) => ({ ...i })) : [blankItem()]
    showModal.value = false
    showModal.value = true
}

function addItem() {
    form.items.push(blankItem())
}

function removeItem(index) {
    form.items.splice(index, 1)
    if (form.items.length === 0) form.items.push(blankItem())
}

function toggleAttendee(id) {
    const i = form.attendee_ids.indexOf(id)
    if (i === -1) form.attendee_ids.push(id)
    else form.attendee_ids.splice(i, 1)
}

function submit() {
    const options = {
        onSuccess: () => {
            showModal.value = false
            toast.success(editing.value ? 'Acta actualizada y PDF regenerado' : 'Acta creada y PDF generado')
            form.reset()
        },
        onError: () => toast.error('Revisa los campos del formulario'),
    }

    if (editing.value) {
        form.put(route('minutes.update', editing.value.id), options)
    } else {
        form.post(route('minutes.store'), options)
    }
}

function remove(minute) {
    form.delete(route('minutes.destroy', minute.id), {
        onSuccess: () => toast.success('Acta eliminada'),
    })
}
</script>

<template>
    <AppLayout>
        <PageHeader title="Actas" subtitle="Actas de consejo y asamblea, con generación automática de PDF">
            <template #actions>
                <button
                    v-if="can.manage"
                    type="button"
                    class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700"
                    @click="openCreate"
                >
                    + Nueva acta
                </button>
            </template>
        </PageHeader>

        <DataTable :columns="columns" :rows="minutes" persist-key="minutes" placeholder="Buscar acta…">
            <template #cell-type="{ value }">{{ typeLabel(value) }}</template>
            <template #actions="{ row }">
                <div class="flex justify-end gap-2">
                    <a
                        v-if="row.web_view_link"
                        :href="row.web_view_link"
                        target="_blank"
                        class="rounded-md border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50"
                    >Ver PDF</a>
                    <template v-if="can.manage">
                        <button
                            type="button"
                            class="rounded-md border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50"
                            @click="openEdit(row)"
                        >Editar</button>
                        <ConfirmButton message="¿Seguro que quieres eliminar esta acta?" confirm-label="Eliminar" @confirm="remove(row)">
                            <span class="rounded-md border border-red-200 px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50">
                                Eliminar
                            </span>
                        </ConfirmButton>
                    </template>
                </div>
            </template>
            <template #empty>No hay actas registradas.</template>
        </DataTable>

        <Modal :show="showModal" :title="editing ? 'Editar acta' : 'Nueva acta'" max-width="2xl" @close="showModal = false">
            <form class="space-y-4" @submit.prevent="submit">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <FormField v-model="form.title" label="Título" required :error="form.errors.title" />
                    <FormField
                        v-model="form.type"
                        label="Tipo"
                        type="select"
                        :options="types"
                        required
                        :error="form.errors.type"
                    />
                    <FormField v-model="form.held_on" label="Fecha de la reunión" type="date" required :error="form.errors.held_on" />
                    <FormField v-model="form.location" label="Lugar" :error="form.errors.location" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Asistentes</label>
                    <div class="mt-2 flex max-h-32 flex-wrap gap-2 overflow-y-auto rounded-md border border-slate-200 p-2">
                        <label
                            v-for="opt in attendeeOptions"
                            :key="opt.value"
                            class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs"
                            :class="form.attendee_ids.includes(opt.value) ? 'border-emerald-500 bg-emerald-50 text-emerald-700' : 'border-slate-300 text-slate-600'"
                        >
                            <input type="checkbox" class="sr-only" :checked="form.attendee_ids.includes(opt.value)" @change="toggleAttendee(opt.value)" />
                            {{ opt.label }}
                        </label>
                        <p v-if="attendeeOptions.length === 0" class="text-xs text-slate-400">No hay responsables registrados.</p>
                    </div>
                    <p v-if="form.errors.attendee_ids" class="mt-1 text-xs text-red-600">{{ form.errors.attendee_ids }}</p>
                </div>

                <div>
                    <div class="flex items-center justify-between">
                        <label class="block text-sm font-medium text-slate-700">Orden del día</label>
                        <button type="button" class="text-xs font-semibold text-emerald-700 hover:underline" @click="addItem">
                            + Añadir punto
                        </button>
                    </div>

                    <div class="mt-2 space-y-3">
                        <div v-for="(item, index) in form.items" :key="index" class="rounded-md border border-slate-200 p-3">
                            <div class="mb-2 flex items-center justify-between">
                                <span class="text-xs font-semibold text-slate-500">Punto {{ index + 1 }}</span>
                                <button type="button" class="text-xs text-red-600 hover:underline" @click="removeItem(index)">
                                    Quitar
                                </button>
                            </div>
                            <div class="space-y-2">
                                <FormField v-model="item.topic" label="Tema" placeholder="Tema" required :error="form.errors[`items.${index}.topic`]" />
                                <FormField v-model="item.discussion" label="Desarrollo" type="textarea" placeholder="Desarrollo (opcional)" />
                                <FormField v-model="item.agreement" label="Acuerdo" type="textarea" placeholder="Acuerdo adoptado (opcional)" />
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <template #footer>
                <button type="button" class="rounded-md px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100" @click="showModal = false">
                    Cancelar
                </button>
                <button
                    type="button"
                    class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-50"
                    :disabled="form.processing"
                    @click="submit"
                >
                    Guardar y generar PDF
                </button>
            </template>
        </Modal>
    </AppLayout>
</template>
