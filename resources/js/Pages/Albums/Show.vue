<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import ConfirmButton from '@/Components/Shared/ConfirmButton.vue'
import { useAuth } from '@/composables/useAuth'
import { useToast } from '@/composables/useToast'

const props = defineProps({
    album: { type: Object, required: true },
    photos: { type: Array, default: () => [] },
})

const { can } = useAuth()
const toast = useToast()

const uploadForm = useForm({
    file: null,
    caption: '',
})

const deleteForm = useForm({})

function submitUpload() {
    uploadForm.post(route('albums.photos.store', props.album.id), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            uploadForm.reset()
            toast.success('Foto subida correctamente.')
        },
    })
}

function destroyPhoto(photo) {
    deleteForm.delete(route('photos.destroy', photo.id), {
        preserveScroll: true,
        onSuccess: () => toast.success('Foto eliminada correctamente.'),
    })
}
</script>

<template>
    <Head :title="album.title" />
    <AppLayout>
        <PageHeader :title="album.title" :subtitle="album.description">
            <template #actions>
                <Link :href="route('albums.index')" class="text-sm text-slate-500 hover:underline">
                    &larr; Volver a álbumes
                </Link>
            </template>
        </PageHeader>

        <form
            v-if="can('photos.manage')"
            class="mb-6 flex flex-col gap-3 rounded-lg border border-slate-200 bg-white p-4 sm:flex-row sm:items-end"
            @submit.prevent="submitUpload"
        >
            <div class="flex-1">
                <label class="block text-sm font-medium text-slate-700">Foto</label>
                <input
                    type="file"
                    accept="image/*"
                    class="mt-1 block w-full text-sm"
                    @change="uploadForm.file = $event.target.files[0]"
                />
                <p v-if="uploadForm.errors.file" class="mt-1 text-xs text-red-600">{{ uploadForm.errors.file }}</p>
            </div>
            <div class="flex-1">
                <label class="block text-sm font-medium text-slate-700">Pie de foto (opcional)</label>
                <input
                    v-model="uploadForm.caption"
                    type="text"
                    class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                />
            </div>
            <button
                type="submit"
                class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700"
                :disabled="uploadForm.processing || !uploadForm.file"
            >
                Subir foto
            </button>
        </form>

        <div v-if="photos.length" class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
            <div v-for="photo in photos" :key="photo.id" class="group relative overflow-hidden rounded-lg border border-slate-200 bg-white">
                <img :src="photo.thumbnail_url" :alt="photo.caption ?? album.title" class="h-40 w-full object-cover" loading="lazy" />
                <p v-if="photo.caption" class="p-2 text-xs text-slate-500">{{ photo.caption }}</p>
                <ConfirmButton
                    v-if="can('photos.manage')"
                    message="¿Eliminar esta foto?"
                    confirm-label="Eliminar"
                    class="absolute right-1 top-1"
                    @confirm="destroyPhoto(photo)"
                >
                    <span class="rounded bg-slate-900/70 px-2 py-1 text-xs text-white opacity-0 group-hover:opacity-100">✕</span>
                </ConfirmButton>
            </div>
        </div>
        <p v-else class="rounded-lg border border-dashed border-slate-300 p-8 text-center text-slate-400">
            Todavía no hay fotos en este álbum.
        </p>
    </AppLayout>
</template>
