<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import Modal from '@/Components/Shared/Modal.vue'
import FormField from '@/Components/Shared/FormField.vue'
import BadgeEstado from '@/Components/Shared/BadgeEstado.vue'
import ConfirmButton from '@/Components/Shared/ConfirmButton.vue'
import { useToast } from '@/composables/useToast'

const props = defineProps({
    categories: { type: Array, default: () => [] },
    expiring: { type: Array, default: () => [] },
    can: { type: Object, default: () => ({}) },
})

const toast = useToast()

const openCategory = ref(props.categories.find((c) => c.documents.length > 0)?.value ?? props.categories[0]?.value ?? null)

function toggleCategory(value) {
    openCategory.value = openCategory.value === value ? null : value
}

const showModal = ref(false)
const editing = ref(null)

const form = useForm({
    title: '',
    category: props.categories[0]?.value ?? '',
    file: null,
    external_url: '',
    expires_at: '',
    notes: '',
})

function openCreate(categoryValue) {
    editing.value = null
    form.reset()
    form.category = categoryValue ?? props.categories[0]?.value ?? ''
    showModal.value = true
}

function openEdit(document) {
    editing.value = document
    form.title = document.title
    form.category = document.category
    form.file = null
    form.external_url = document.external_url ?? ''
    form.expires_at = document.expires_at ?? ''
    form.notes = document.notes ?? ''
    showModal.value = true
}

function onFileChange(e) {
    form.file = e.target.files[0] ?? null
}

function submit() {
    const options = {
        forceFormData: true,
        onSuccess: () => {
            showModal.value = false
            toast.success(editing.value ? 'Documento actualizado' : 'Documento subido')
            form.reset()
        },
        onError: () => toast.error('Revisa los campos del formulario'),
    }

    if (editing.value) {
        form.transform((data) => ({ ...data, _method: 'put' })).post(route('documents.update', editing.value.id), options)
    } else {
        form.post(route('documents.store'), options)
    }
}

function remove(document) {
    form.delete(route('documents.destroy', document.id), {
        onSuccess: () => toast.success('Documento eliminado'),
    })
}
</script>

<template>
    <AppLayout>
        <PageHeader title="Documentos" subtitle="Archivo de documentos del grupo organizado por categoría">
            <template #actions>
                <button
                    v-if="can.manage"
                    type="button"
                    class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700"
                    @click="openCreate(openCategory)"
                >
                    + Subir documento
                </button>
            </template>
        </PageHeader>

        <div v-if="expiring.length" class="mb-6 rounded-lg border border-amber-300 bg-amber-50 p-4">
            <p class="mb-2 text-sm font-semibold text-amber-800">
                ⚠️ Documentos que caducan en los próximos 30 días
            </p>
            <ul class="space-y-1 text-sm text-amber-800">
                <li v-for="doc in expiring" :key="'exp-'+doc.id">
                    {{ doc.title }} — caduca el {{ doc.expires_at }}
                </li>
            </ul>
        </div>

        <div class="space-y-3">
            <div v-for="cat in categories" :key="cat.value" class="rounded-lg border border-slate-200 bg-white">
                <button
                    type="button"
                    class="flex w-full items-center justify-between px-4 py-3 text-left"
                    @click="toggleCategory(cat.value)"
                >
                    <span class="font-semibold text-slate-800">
                        📁 {{ cat.label }}
                        <span class="ml-2 text-xs font-normal text-slate-400">({{ cat.documents.length }})</span>
                    </span>
                    <span class="text-slate-400">{{ openCategory === cat.value ? '▲' : '▼' }}</span>
                </button>

                <div v-if="openCategory === cat.value" class="border-t border-slate-100 px-4 py-3">
                    <div v-if="cat.documents.length === 0" class="py-4 text-center text-sm text-slate-400">
                        No hay documentos en esta categoría.
                    </div>
                    <ul v-else class="divide-y divide-slate-100">
                        <li v-for="doc in cat.documents" :key="doc.id" class="flex flex-col gap-2 py-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="font-medium text-slate-700">{{ doc.title }}</p>
                                <p class="text-xs text-slate-400">
                                    Subido por {{ doc.creator ?? '—' }} el {{ doc.created_at }}
                                    <span v-if="doc.expires_at"> · Caduca {{ doc.expires_at }}</span>
                                </p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <BadgeEstado v-if="doc.is_expired" label="Caducado" color="red" />
                                <a
                                    v-if="doc.web_view_link"
                                    :href="doc.web_view_link"
                                    target="_blank"
                                    class="rounded-md border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50"
                                >Ver</a>
                                <a
                                    v-else-if="doc.external_url"
                                    :href="doc.external_url"
                                    target="_blank"
                                    class="rounded-md border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50"
                                >Ver</a>
                                <template v-if="can.manage">
                                    <button
                                        type="button"
                                        class="rounded-md border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50"
                                        @click="openEdit(doc)"
                                    >Editar</button>
                                    <ConfirmButton
                                        message="¿Seguro que quieres eliminar este documento?"
                                        confirm-label="Eliminar"
                                        @confirm="remove(doc)"
                                    >
                                        <span class="rounded-md border border-red-200 px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50">
                                            Eliminar
                                        </span>
                                    </ConfirmButton>
                                </template>
                            </div>
                        </li>
                    </ul>
                    <button
                        v-if="can.manage"
                        type="button"
                        class="mt-3 text-xs font-semibold text-emerald-700 hover:underline"
                        @click="openCreate(cat.value)"
                    >
                        + Subir documento en {{ cat.label }}
                    </button>
                </div>
            </div>
        </div>

        <Modal :show="showModal" :title="editing ? 'Editar documento' : 'Subir documento'" @close="showModal = false">
            <form class="space-y-4" @submit.prevent="submit">
                <FormField v-model="form.title" label="Título" required :error="form.errors.title" />

                <FormField
                    v-model="form.category"
                    label="Categoría"
                    type="select"
                    :options="categories.map((c) => ({ value: c.value, label: c.label }))"
                    required
                    :error="form.errors.category"
                />

                <div>
                    <label class="block text-sm font-medium text-slate-700">Fichero</label>
                    <input type="file" class="mt-1 block w-full text-sm" @change="onFileChange" />
                    <p v-if="form.errors.file" class="mt-1 text-xs text-red-600">{{ form.errors.file }}</p>
                </div>

                <FormField v-model="form.external_url" label="URL externa (alternativa al fichero)" placeholder="https://…" :error="form.errors.external_url" />

                <FormField v-model="form.expires_at" label="Fecha de caducidad (opcional)" type="date" :error="form.errors.expires_at" />

                <FormField v-model="form.notes" label="Notas" type="textarea" :error="form.errors.notes" />
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
                    Guardar
                </button>
            </template>
        </Modal>
    </AppLayout>
</template>
