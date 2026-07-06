<script setup>
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import FormField from '@/Components/Shared/FormField.vue'
import { useToast } from '@/composables/useToast'

defineOptions({ layout: AppLayout })

const props = defineProps({
    branches: { type: Array, default: () => [] },
    inventoryItems: { type: Array, default: () => [] },
})

const toast = useToast()

const form = useForm({
    title: '',
    branch: '',
    duration_minutes: '',
    objectives_text: '',
    development: '',
    attachments: [],
    materials: [{ name: '', quantity: 1, inventory_item_id: '' }],
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

function submit() {
    form.transform((data) => ({
        ...data,
        materials: data.materials.filter((m) => m.name?.trim()),
    })).post(route('activities.store'), {
        forceFormData: true,
        onSuccess: () => toast.success('Actividad creada correctamente.'),
    })
}
</script>

<template>
    <Head title="Nueva actividad" />

    <PageHeader title="Nueva actividad" subtitle="Añade una actividad reutilizable a la biblioteca." />

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

        <div>
            <label class="block text-sm font-medium text-slate-700">Adjuntos</label>
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
                Guardar actividad
            </button>
        </div>
    </form>
</template>
