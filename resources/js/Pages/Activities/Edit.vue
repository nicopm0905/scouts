<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import FormField from '@/Components/Shared/FormField.vue'
import { useToast } from '@/composables/useToast'

defineOptions({ layout: AppLayout })

const props = defineProps({
    activity: { type: Object, required: true },
    branches: { type: Array, default: () => [] },
    inventoryItems: { type: Array, default: () => [] },
})

const toast = useToast()
const removedAttachmentIds = ref([])

const form = useForm({
    title: props.activity.title,
    branch: props.activity.branch ?? '',
    duration_minutes: props.activity.duration_minutes ?? '',
    objectives_text: props.activity.objectives_text ?? '',
    development: props.activity.development ?? '',
    attachments: [],
    materials: props.activity.materials.length
        ? props.activity.materials.map((m) => ({ name: m.name, quantity: m.quantity, inventory_item_id: m.inventory_item_id ?? '' }))
        : [{ name: '', quantity: 1, inventory_item_id: '' }],
})

function addMaterial() {
    form.materials.push({ name: '', quantity: 1, inventory_item_id: '' })
}

function removeMaterial(i) {
    form.materials.splice(i, 1)
}

function onFiles(e) {
    form.attachments = Array.from(e.target.files)
}

function removeAttachment(id) {
    removedAttachmentIds.value.push(id)
}

function submit() {
    form.transform((data) => ({
        ...data,
        materials: data.materials.filter((m) => m.name?.trim()),
        removed_attachment_ids: removedAttachmentIds.value,
    })).put(route('activities.update', props.activity.id), {
        forceFormData: true,
        onSuccess: () => toast.success('Actividad actualizada correctamente.'),
    })
}
</script>

<template>
    <Head title="Editar actividad" />

    <PageHeader title="Editar actividad" :subtitle="activity.title" />

    <form class="max-w-2xl space-y-4 rounded-lg border border-slate-200 bg-white p-4 sm:p-6" @submit.prevent="submit">
        <FormField v-model="form.title" label="Título" :error="form.errors.title" required />
        <FormField
            v-model="form.branch"
            type="select"
            label="Rama"
            :options="[{ value: '', label: 'Todas las ramas' }, ...branches]"
            :error="form.errors.branch"
        />
        <FormField v-model="form.duration_minutes" type="number" label="Duración (min)" :error="form.errors.duration_minutes" />
        <FormField v-model="form.objectives_text" type="textarea" label="Objetivos educativos" :error="form.errors.objectives_text" />
        <FormField v-model="form.development" type="textarea" label="Desarrollo (markdown)" :error="form.errors.development" />

        <div v-if="activity.attachments.length">
            <label class="block text-sm font-medium text-slate-700">Adjuntos actuales</label>
            <ul class="mt-1 space-y-1 text-sm">
                <li
                    v-for="a in activity.attachments.filter((x) => !removedAttachmentIds.includes(x.id))"
                    :key="a.id"
                    class="flex items-center justify-between"
                >
                    <a :href="a.web_view_link" target="_blank" class="text-brand-700 hover:underline">{{ a.id }}</a>
                    <button type="button" class="text-xs text-red-600 hover:underline" @click="removeAttachment(a.id)">Quitar</button>
                </li>
            </ul>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700">Añadir adjuntos</label>
            <input type="file" multiple class="mt-1 block w-full text-sm" @change="onFiles" />
        </div>

        <div>
            <div class="mb-2 flex items-center justify-between">
                <label class="block text-sm font-medium text-slate-700">Materiales</label>
                <button type="button" class="text-xs font-medium text-brand-700 hover:underline" @click="addMaterial">
                    + Añadir material
                </button>
            </div>
            <div v-for="(m, i) in form.materials" :key="i" class="mb-2 flex flex-wrap items-center gap-2">
                <input v-model="m.name" type="text" placeholder="Nombre" class="flex-1 rounded-md border-slate-300 text-sm" />
                <input v-model.number="m.quantity" type="number" min="1" placeholder="Cant." class="w-20 rounded-md border-slate-300 text-sm" />
                <select v-model="m.inventory_item_id" class="w-40 rounded-md border-slate-300 text-sm">
                    <option value="">Sin vincular</option>
                    <option v-for="opt in inventoryItems" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                </select>
                <button type="button" class="text-xs text-red-600 hover:underline" @click="removeMaterial(i)">Quitar</button>
            </div>
        </div>

        <div class="flex justify-end gap-2 pt-2">
            <button type="submit" class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700" :disabled="form.processing">
                Guardar cambios
            </button>
        </div>
    </form>
</template>
