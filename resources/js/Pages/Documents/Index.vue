<script setup>
import { ref } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
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
function toggleCategory(v) { openCategory.value = openCategory.value === v ? null : v }

const showModal = ref(false)
const editing = ref(null)
const form = useForm({ title: '', category: props.categories[0]?.value ?? '', file: null, external_url: '', expires_at: '', notes: '' })

function openCreate(cat) { editing.value = null; form.reset(); form.category = cat ?? props.categories[0]?.value ?? ''; showModal.value = true }
function openEdit(d) {
    editing.value = d
    form.title = d.title; form.category = d.category; form.file = null
    form.external_url = d.external_url ?? ''; form.expires_at = d.expires_at ?? ''; form.notes = d.notes ?? ''
    showModal.value = true
}
function onFileChange(e) { form.file = e.target.files[0] ?? null }
function submit() {
    const opt = {
        forceFormData: true,
        onSuccess: () => { showModal.value = false; toast.success(editing.value ? 'Documento actualizado' : 'Documento subido'); form.reset() },
        onError: () => toast.error('Revisa los campos del formulario'),
    }
    if (editing.value) form.transform((d) => ({ ...d, _method: 'put' })).post(route('documents.update', editing.value.id), opt)
    else form.post(route('documents.store'), opt)
}
function remove(d) { form.delete(route('documents.destroy', d.id), { onSuccess: () => toast.success('Documento eliminado') }) }
</script>

<template>
    <Head title="Documentos" />
    <AppLayout>
        <PageHeader title="Documentos" subtitle="Archivo del grupo organizado por categoría.">
            <template #actions>
                <button v-if="can.manage" type="button" class="btn-primary btn-sm" @click="openCreate(openCategory)">+ Subir documento</button>
            </template>
        </PageHeader>

        <div v-if="expiring.length" class="mb-6 rounded-xl border border-amber-300 bg-amber-50 p-4">
            <p class="mb-2 text-sm font-semibold text-amber-800">⚠️ Documentos que caducan en los próximos 30 días</p>
            <ul class="space-y-1 text-sm text-amber-800">
                <li v-for="doc in expiring" :key="'exp-' + doc.id">{{ doc.title }} — caduca el {{ doc.expires_at }}</li>
            </ul>
        </div>

        <div class="space-y-3">
            <div v-for="cat in categories" :key="cat.value" class="card overflow-hidden">
                <button type="button" class="flex w-full items-center justify-between px-4 py-3 text-left hover:bg-ink-50" @click="toggleCategory(cat.value)">
                    <span class="font-semibold text-ink-800">📁 {{ cat.label }}
                        <span class="ml-2 rounded-full bg-ink-100 px-2 py-0.5 text-xs font-medium text-ink-500">{{ cat.documents.length }}</span>
                    </span>
                    <span class="text-ink-400">{{ openCategory === cat.value ? '▲' : '▼' }}</span>
                </button>

                <div v-if="openCategory === cat.value" class="border-t border-ink-100 px-4 py-3">
                    <div v-if="cat.documents.length === 0" class="py-4 text-center text-sm text-ink-400">No hay documentos en esta categoría.</div>
                    <ul v-else class="divide-y divide-ink-100">
                        <li v-for="doc in cat.documents" :key="doc.id" class="flex flex-col gap-2 py-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="font-medium text-ink-800">{{ doc.title }}</p>
                                <p class="text-xs text-ink-400">
                                    Subido por {{ doc.creator ?? '—' }} el {{ doc.created_at }}
                                    <span v-if="doc.expires_at"> · Caduca {{ doc.expires_at }}</span>
                                </p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <BadgeEstado v-if="doc.is_expired" label="Caducado" color="red" />
                                <a v-if="doc.web_view_link || doc.external_url" :href="doc.web_view_link || doc.external_url" target="_blank" class="btn-secondary btn-sm">Ver</a>
                                <template v-if="can.manage">
                                    <button type="button" class="btn-secondary btn-sm" @click="openEdit(doc)">Editar</button>
                                    <ConfirmButton message="¿Seguro que quieres eliminar este documento?" confirm-label="Eliminar" @confirm="remove(doc)">
                                        <span class="rounded-md border border-brand-200 px-2.5 py-1 text-xs font-medium text-brand-600 hover:bg-brand-50">Eliminar</span>
                                    </ConfirmButton>
                                </template>
                            </div>
                        </li>
                    </ul>
                    <button v-if="can.manage" type="button" class="mt-3 text-xs font-semibold text-brand-700 hover:underline" @click="openCreate(cat.value)">
                        + Subir documento en {{ cat.label }}
                    </button>
                </div>
            </div>
        </div>

        <Modal :show="showModal" :title="editing ? 'Editar documento' : 'Subir documento'" @close="showModal = false">
            <form class="space-y-4" @submit.prevent="submit">
                <FormField v-model="form.title" label="Título" required :error="form.errors.title" />
                <FormField v-model="form.category" label="Categoría" type="select"
                    :options="categories.map((c) => ({ value: c.value, label: c.label }))" required :error="form.errors.category" />
                <div>
                    <label class="label">Fichero</label>
                    <input type="file" class="mt-1 block w-full text-sm text-ink-600" @change="onFileChange" />
                    <p v-if="form.errors.file" class="mt-1 text-xs text-brand-600">{{ form.errors.file }}</p>
                </div>
                <FormField v-model="form.external_url" label="URL externa (alternativa al fichero)" placeholder="https://…" :error="form.errors.external_url" />
                <FormField v-model="form.expires_at" label="Fecha de caducidad (opcional)" type="date" :error="form.errors.expires_at" />
                <FormField v-model="form.notes" label="Notas" type="textarea" :error="form.errors.notes" />
            </form>
            <template #footer>
                <button type="button" class="btn-ghost" @click="showModal = false">Cancelar</button>
                <button type="button" class="btn-primary" :disabled="form.processing" @click="submit">Guardar</button>
            </template>
        </Modal>
    </AppLayout>
</template>
