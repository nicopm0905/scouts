<script setup>
import { Head } from '@inertiajs/vue3'

defineProps({
    albums: { type: Array, default: () => [] },
})
</script>

<template>
    <Head title="Galería del grupo" />

    <div class="min-h-screen bg-slate-100">
        <header class="border-b border-slate-200 bg-white px-4 py-5 sm:px-8">
            <h1 class="text-xl font-bold text-brand-800 sm:text-2xl">⚜️ Galería del grupo</h1>
            <p class="mt-1 text-sm text-slate-500">Fotos con consentimiento de imagen para difusión pública.</p>
        </header>

        <main class="mx-auto max-w-5xl space-y-8 p-4 sm:p-8">
            <p v-if="!albums.length" class="rounded-lg border border-dashed border-slate-300 p-8 text-center text-slate-400">
                Todavía no hay álbumes publicados.
            </p>

            <section v-for="album in albums" :key="album.id" class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-800">{{ album.title }}</h2>
                <p v-if="album.description" class="mt-1 text-sm text-slate-500">{{ album.description }}</p>

                <div v-if="album.photos.length" class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                    <div v-for="photo in album.photos" :key="photo.id" class="overflow-hidden rounded-lg border border-slate-200">
                        <img :src="photo.thumbnail_url" :alt="photo.caption ?? album.title" class="h-36 w-full object-cover" loading="lazy" />
                    </div>
                </div>
                <p v-else class="mt-3 text-sm text-slate-400">Este álbum todavía no tiene fotos.</p>
            </section>
        </main>
    </div>
</template>
