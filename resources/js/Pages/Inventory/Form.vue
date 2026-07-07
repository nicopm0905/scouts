<script setup>
import { computed } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import FormField from '@/Components/Shared/FormField.vue'
import { useToast } from '@/composables/useToast'

const props = defineProps({
    item: { type: Object, default: null },
    categories: { type: Array, default: () => [] },
    conditions: { type: Array, default: () => [] },
})

const toast = useToast()
const isEdit = computed(() => !!props.item)

const form = useForm({
    name: props.item?.name ?? '',
    category: props.item?.category ?? '',
    quantity: props.item?.quantity ?? 1,
    condition: props.item?.condition ?? 'bueno',
    location: props.item?.location ?? '',
    next_review_at: props.item?.next_review_at ?? '',
    notes: props.item?.notes ?? '',
    photo: null,
    remove_photo: false,
    _method: isEdit.value ? 'put' : 'post',
})

function submit() {
    const url = isEdit.value ? route('inventory.update', props.item.id) : route('inventory.store')

    form.post(url, {
        forceFormData: true,
        onSuccess: () => toast.success(isEdit.value ? 'Ítem actualizado.' : 'Ítem creado.'),
    })
}

function onPhotoChange(e) {
    form.photo = e.target.files[0] ?? null
}
</script>

<template>
    <Head :title="isEdit ? 'Editar ítem' : 'Nuevo ítem'" />
    <AppLayout>
        <PageHeader :title="isEdit ? 'Editar ítem' : 'Nuevo ítem'" subtitle="Datos del material de inventario." />

        <form class="max-w-2xl space-y-4 card-pad" @submit.prevent="submit">
            <FormField label="Nombre" v-model="form.name" required :error="form.errors.name" />

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <FormField
                    label="Categoría"
                    type="select"
                    v-model="form.category"
                    :options="categories"
                    required
                    :error="form.errors.category"
                />
                <FormField
                    label="Estado"
                    type="select"
                    v-model="form.condition"
                    :options="conditions"
                    required
                    :error="form.errors.condition"
                />
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <FormField label="Cantidad total" type="number" v-model.number="form.quantity" required :error="form.errors.quantity" />
                <FormField label="Ubicación" v-model="form.location" :error="form.errors.location" />
            </div>

            <FormField
                label="Próxima revisión"
                type="date"
                v-model="form.next_review_at"
                hint="Para material con caducidad o revisión periódica (botiquín, cuerdas…)."
                :error="form.errors.next_review_at"
            />

            <FormField label="Notas" type="textarea" v-model="form.notes" :error="form.errors.notes" />

            <div>
                <label class="label">Foto (opcional)</label>
                <img v-if="item?.photo_url" :src="item.photo_url" class="mt-2 h-20 w-20 rounded-lg object-cover" alt="" />
                <input type="file" accept="image/*" class="mt-1 block w-full text-sm text-ink-600" @change="onPhotoChange" />
                <label v-if="item?.photo_url" class="mt-2 inline-flex items-center gap-2 text-sm text-ink-600">
                    <input type="checkbox" v-model="form.remove_photo" class="rounded border-ink-300 text-brand-600 focus:ring-brand-500" /> Quitar foto actual
                </label>
                <p v-if="form.errors.photo" class="mt-1 text-xs text-brand-600">{{ form.errors.photo }}</p>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <a :href="route('inventory.index')" class="btn-ghost">Cancelar</a>
                <button type="submit" class="btn-primary" :disabled="form.processing">Guardar</button>
            </div>
        </form>
    </AppLayout>
</template>
