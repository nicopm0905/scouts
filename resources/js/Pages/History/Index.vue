<script setup>
import { ref } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import Modal from '@/Components/Shared/Modal.vue'
import FormField from '@/Components/Shared/FormField.vue'
import ConfirmButton from '@/Components/Shared/ConfirmButton.vue'
import { useAuth } from '@/composables/useAuth'
import { useToast } from '@/composables/useToast'

defineProps({
    entries: { type: Array, default: () => [] },
})

const { can } = useAuth()
const toast = useToast()

const showForm = ref(false)
const editing = ref(null)

const form = useForm({
    year: new Date().getFullYear(),
    title: '',
    body: '',
    position: 0,
    published: true,
    photo: null,
})

function openCreate() {
    editing.value = null
    form.reset()
    form.published = true
    showForm.value = true
}

function openEdit(entry) {
    editing.value = entry
    form.year = entry.year
    form.title = entry.title
    form.body = entry.body
    form.position = entry.position
    form.published = entry.published
    form.photo = null
    showForm.value = true
}

function submit() {
    const options = {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            showForm.value = false
            toast.success(editing.value ? 'Entrada actualizada correctamente.' : 'Entrada creada correctamente.')
        },
    }

    if (editing.value) {
        form.transform((data) => ({ ...data, _method: 'put' })).post(route('history.update', editing.value.id), options)
    } else {
        form.post(route('history.store'), options)
    }
}

const destroyForm = useForm({})

function destroyEntry(entry) {
    destroyForm.delete(route('history.destroy', entry.id), {
        preserveScroll: true,
        onSuccess: () => toast.success('Entrada eliminada correctamente.'),
    })
}
</script>

<template>
    <Head title="Historia del grupo" />
    <AppLayout>
        <PageHeader title="Historia del grupo" subtitle="Línea de tiempo editable para la página pública." icon="clock">
            <template #actions>
                <button
                    v-if="can('history.manage')"
                    type="button"
                    class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700"
                    @click="openCreate"
                >
                    Nueva entrada
                </button>
            </template>
        </PageHeader>

        <div v-if="entries.length" class="space-y-3">
            <div
                v-for="entry in entries"
                :key="entry.id"
                class="flex flex-col gap-3 rounded-lg border border-ink-200 bg-white p-4 sm:flex-row sm:items-center"
            >
                <img
                    v-if="entry.photo_thumbnail_url"
                    :src="entry.photo_thumbnail_url"
                    :alt="entry.title"
                    class="h-16 w-16 rounded object-cover"
                />
                <div class="flex-1">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-bold text-brand-700">{{ entry.year }}</span>
                        <span class="font-semibold text-ink-800">{{ entry.title }}</span>
                        <span v-if="!entry.published" class="rounded-full bg-ink-100 px-2 py-0.5 text-xs text-ink-500">
                            Borrador
                        </span>
                    </div>
                    <p v-if="entry.body" class="mt-1 text-sm text-ink-500">{{ entry.body }}</p>
                </div>
                <div v-if="can('history.manage')" class="flex items-center gap-3">
                    <button type="button" class="text-xs font-medium text-brand-700 hover:underline" @click="openEdit(entry)">
                        Editar
                    </button>
                    <ConfirmButton
                        message="¿Eliminar esta entrada de la historia?"
                        confirm-label="Eliminar"
                        @confirm="destroyEntry(entry)"
                    >
                        <span class="text-xs font-medium text-red-600 hover:underline">Eliminar</span>
                    </ConfirmButton>
                </div>
            </div>
        </div>
        <p v-else class="rounded-lg border border-dashed border-ink-300 p-8 text-center text-ink-400">
            Todavía no hay entradas de historia.
        </p>

        <Modal :show="showForm" :title="editing ? 'Editar entrada' : 'Nueva entrada'" @close="showForm = false">
            <form class="space-y-4" @submit.prevent="submit">
                <div class="grid grid-cols-2 gap-3">
                    <FormField v-model="form.year" type="number" label="Año" required :error="form.errors.year" />
                    <FormField v-model="form.position" type="number" label="Orden" :error="form.errors.position" />
                </div>
                <FormField v-model="form.title" label="Título" required :error="form.errors.title" />
                <FormField v-model="form.body" type="textarea" label="Texto" :error="form.errors.body" />
                <div>
                    <label class="block text-sm font-medium text-ink-700">Foto (opcional)</label>
                    <input type="file" accept="image/*" class="mt-1 block w-full text-sm" @change="form.photo = $event.target.files[0]" />
                    <p v-if="form.errors.photo" class="mt-1 text-xs text-red-600">{{ form.errors.photo }}</p>
                </div>
                <FormField v-model="form.published" type="checkbox" placeholder="Publicada en la página pública" />
            </form>
            <template #footer>
                <button type="button" class="rounded-md px-4 py-2 text-sm font-medium text-ink-600 hover:bg-ink-100" @click="showForm = false">
                    Cancelar
                </button>
                <button
                    type="button"
                    class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700"
                    :disabled="form.processing"
                    @click="submit"
                >
                    Guardar
                </button>
            </template>
        </Modal>
    </AppLayout>
</template>
