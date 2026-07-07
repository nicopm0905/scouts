<script setup>
import { ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import Modal from '@/Components/Shared/Modal.vue'
import FormField from '@/Components/Shared/FormField.vue'
import ConfirmButton from '@/Components/Shared/ConfirmButton.vue'
import { useAuth } from '@/composables/useAuth'
import { useToast } from '@/composables/useToast'

const props = defineProps({
    albums: { type: Array, default: () => [] },
    events: { type: Array, default: () => [] },
})

const { can } = useAuth()
const toast = useToast()

const showCreate = ref(false)

const form = useForm({
    title: '',
    description: '',
    event_id: '',
    visibility: 'internal',
})

const eventOptions = () => [
    { value: '', label: 'Álbum libre (sin evento)' },
    ...props.events.map((e) => ({ value: e.id, label: e.title })),
]

const visibilityOptions = [
    { value: 'internal', label: 'Solo interno' },
    { value: 'publishable', label: 'Publicable (con consentimiento de imagen)' },
]

function submit() {
    form.post(route('albums.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showCreate.value = false
            form.reset()
            toast.success('Álbum creado correctamente.')
        },
    })
}

function destroyAlbum(album) {
    form.delete(route('albums.destroy', album.id), {
        preserveScroll: true,
        onSuccess: () => toast.success('Álbum eliminado correctamente.'),
    })
}
</script>

<template>
    <Head title="Álbumes de fotos" />
    <AppLayout>
        <PageHeader title="Álbumes de fotos" subtitle="Fotos de eventos y salidas alojadas en Google Drive.">
            <template #actions>
                <Link :href="route('albums.public')" class="btn-secondary btn-sm">Galería pública</Link>
                <button v-if="can('photos.manage')" type="button" class="btn-primary btn-sm" @click="showCreate = true">+ Nuevo álbum</button>
            </template>
        </PageHeader>

        <div v-if="albums.length" class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
            <div v-for="album in albums" :key="album.id" class="card overflow-hidden transition hover:shadow-card-hover">
                <!-- Portada -->
                <Link :href="route('albums.show', album.id)" class="relative flex h-32 items-center justify-center overflow-hidden bg-gradient-to-br from-ink-700 to-ink-900">
                    <img v-if="album.cover_url" :src="album.cover_url" class="h-full w-full object-cover" alt="" />
                    <span v-else class="text-4xl opacity-80">📷</span>
                    <span class="absolute right-2 top-2 rounded-full px-2 py-0.5 text-[11px] font-medium"
                        :class="album.visibility === 'publishable' ? 'bg-brand-600 text-white' : 'bg-white/90 text-ink-700'">
                        {{ album.visibility === 'publishable' ? 'Publicable' : 'Interno' }}
                    </span>
                </Link>
                <div class="p-4">
                    <Link :href="route('albums.show', album.id)" class="font-semibold text-ink-800 hover:text-brand-700">{{ album.title }}</Link>
                    <p v-if="album.event" class="text-xs text-ink-500">{{ album.event.title }}</p>
                    <p v-if="album.description" class="mt-1 line-clamp-2 text-sm text-ink-500">{{ album.description }}</p>
                    <div class="mt-3 flex items-center justify-between text-sm text-ink-500">
                        <span>{{ album.photos_count }} foto(s)</span>
                        <ConfirmButton v-if="can('photos.manage')" message="¿Eliminar este álbum y todas sus fotos?" confirm-label="Eliminar" @confirm="destroyAlbum(album)">
                            <span class="text-xs font-medium text-brand-600 hover:underline">Eliminar</span>
                        </ConfirmButton>
                    </div>
                </div>
            </div>
        </div>
        <p v-else class="rounded-lg border border-dashed border-ink-300 p-8 text-center text-ink-400">
            Todavía no hay álbumes.
        </p>

        <Modal :show="showCreate" title="Nuevo álbum" @close="showCreate = false">
            <form class="space-y-4" @submit.prevent="submit">
                <FormField v-model="form.title" label="Título" required :error="form.errors.title" />
                <FormField v-model="form.description" type="textarea" label="Descripción" :error="form.errors.description" />
                <FormField
                    v-model="form.event_id"
                    type="select"
                    label="Evento (opcional)"
                    :options="eventOptions()"
                    :error="form.errors.event_id"
                />
                <FormField
                    v-model="form.visibility"
                    type="select"
                    label="Visibilidad"
                    :options="visibilityOptions"
                    :error="form.errors.visibility"
                />
            </form>
            <template #footer>
                <button type="button" class="btn-ghost" @click="showCreate = false">Cancelar</button>
                <button type="button" class="btn-primary" :disabled="form.processing" @click="submit">Crear álbum</button>
            </template>
        </Modal>
    </AppLayout>
</template>
