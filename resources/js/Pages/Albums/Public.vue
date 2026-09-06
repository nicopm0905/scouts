<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { Head } from '@inertiajs/vue3'
import PublicLayout from '@/Layouts/PublicLayout.vue'
import reveal from '@/Directives/reveal'

const props = defineProps({
    albums: { type: Array, default: () => [] },
})

const vReveal = reveal

const totalPhotos = computed(() =>
    props.albums.reduce((total, album) => total + (album.photos?.length ?? 0), 0),
)

// Visor a pantalla completa (lightbox) con navegación por teclado.
const lightbox = ref(null)

const openPhoto = (album, index) => {
    lightbox.value = { album, index }
}

const closePhoto = () => {
    lightbox.value = null
}

const movePhoto = (delta) => {
    if (!lightbox.value) return
    const photos = lightbox.value.album.photos
    const next = (lightbox.value.index + delta + photos.length) % photos.length
    lightbox.value = { ...lightbox.value, index: next }
}

const currentPhoto = computed(() =>
    lightbox.value ? lightbox.value.album.photos[lightbox.value.index] : null,
)

// Teclado global mientras el visor está abierto (Esc y flechas).
const onKeydown = (event) => {
    if (event.key === 'Escape') closePhoto()
    if (event.key === 'ArrowLeft') movePhoto(-1)
    if (event.key === 'ArrowRight') movePhoto(1)
}

watch(lightbox, (open) => {
    document.body.style.overflow = open ? 'hidden' : ''
    if (open) {
        window.addEventListener('keydown', onKeydown)
    } else {
        window.removeEventListener('keydown', onKeydown)
    }
})

onBeforeUnmount(() => {
    window.removeEventListener('keydown', onKeydown)
    document.body.style.overflow = ''
})
</script>

<template>
    <Head title="Galería del grupo" />

    <PublicLayout>
        <section class="bg-sand-50 py-14 sm:py-20">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <p class="font-display text-sm font-bold uppercase tracking-[0.18em] text-brand-700">Galería</p>
                <h1 class="mt-4 font-display text-3xl font-extrabold leading-tight tracking-tight text-ink-900 sm:text-5xl">
                    Así vivimos el escultismo
                </h1>
                <p class="mt-5 max-w-2xl text-base leading-relaxed text-ink-600 sm:text-lg">
                    Momentos de reuniones, salidas y campamentos. Solo publicamos fotos de quienes nos han dado su
                    consentimiento de imagen.
                </p>
                <p v-if="totalPhotos" class="mt-4 text-sm font-medium text-ink-400">
                    {{ albums.length }} {{ albums.length === 1 ? 'álbum' : 'álbumes' }} · {{ totalPhotos }} fotos
                </p>
            </div>
        </section>

        <section class="bg-sand-50 pb-20 sm:pb-28">
            <div class="mx-auto max-w-6xl space-y-14 px-4 sm:px-6 lg:px-8">
                <p
                    v-if="!albums.length"
                    class="rounded-2xl border border-dashed border-sand-400 bg-white p-10 text-center text-ink-500"
                >
                    Todavía no hay álbumes publicados. ¡Vuelve pronto!
                </p>

                <article v-for="(album, index) in albums" :key="album.id" v-reveal="index * 80">
                    <header class="flex flex-col gap-1 border-b border-sand-300 pb-4">
                        <h2 class="font-display text-2xl font-bold text-ink-900">{{ album.title }}</h2>
                        <p v-if="album.description" class="text-sm text-ink-500">{{ album.description }}</p>
                    </header>

                    <ul v-if="album.photos.length" class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                        <li v-for="(photo, index) in album.photos" :key="photo.id" v-reveal:scale="index * 45">
                            <button
                                type="button"
                                class="group relative block aspect-square w-full overflow-hidden rounded-xl bg-sand-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600"
                                @click="openPhoto(album, index)"
                            >
                                <img
                                    :src="photo.thumbnail_url"
                                    :alt="photo.caption ?? album.title"
                                    class="h-full w-full object-cover object-top transition-transform duration-700 ease-suave group-hover:scale-110 motion-reduce:transform-none"
                                    loading="lazy"
                                />
                                <span class="absolute inset-0 bg-ink-950/0 transition group-hover:bg-ink-950/20"></span>
                            </button>
                        </li>
                    </ul>
                    <p v-else class="mt-4 text-sm text-ink-400">Este álbum todavía no tiene fotos.</p>
                </article>
            </div>
        </section>

        <!-- Visor de foto -->
        <Teleport to="body">
            <div
                v-if="currentPhoto"
                class="fixed inset-0 z-[70] flex items-center justify-center bg-ink-950/95 p-4"
                role="dialog"
                aria-modal="true"
                aria-label="Visor de fotografía"
                tabindex="-1"
                @click.self="closePhoto"
                @keydown.esc="closePhoto"
                @keydown.left="movePhoto(-1)"
                @keydown.right="movePhoto(1)"
            >
                <button
                    type="button"
                    class="absolute right-4 top-4 inline-flex h-11 w-11 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20"
                    aria-label="Cerrar visor"
                    autofocus
                    @click="closePhoto"
                >
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M6 6l12 12M18 6L6 18" stroke-linecap="round" />
                    </svg>
                </button>

                <button
                    v-if="lightbox.album.photos.length > 1"
                    type="button"
                    class="absolute left-2 inline-flex h-12 w-12 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20 sm:left-6"
                    aria-label="Foto anterior"
                    @click.stop="movePhoto(-1)"
                >
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M15 6l-6 6 6 6" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </button>

                <figure class="max-h-full max-w-4xl">
                    <img
                        :src="currentPhoto.thumbnail_url"
                        :alt="currentPhoto.caption ?? lightbox.album.title"
                        class="max-h-[78svh] w-auto rounded-xl object-contain"
                    />
                    <figcaption class="mt-3 text-center text-sm text-white/70">
                        {{ currentPhoto.caption || lightbox.album.title }}
                    </figcaption>
                </figure>

                <button
                    v-if="lightbox.album.photos.length > 1"
                    type="button"
                    class="absolute right-2 inline-flex h-12 w-12 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20 sm:right-6"
                    aria-label="Foto siguiente"
                    @click.stop="movePhoto(1)"
                >
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M9 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </button>
            </div>
        </Teleport>
    </PublicLayout>
</template>
