<script setup>
import { Head } from '@inertiajs/vue3'
import PublicLayout from '@/Layouts/PublicLayout.vue'
import reveal from '@/Directives/reveal'

defineProps({
    entries: { type: Array, default: () => [] },
})

const vReveal = reveal
</script>

<template>
    <Head title="Nuestra historia" />

    <PublicLayout>
        <section class="bg-sand-50 py-14 sm:py-20">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <p class="font-display text-sm font-bold uppercase tracking-[0.18em] text-brand-700">Nuestra historia</p>
                <h1 class="mt-4 font-display text-3xl font-extrabold leading-tight tracking-tight text-ink-900 sm:text-5xl">
                    Muchas generaciones, el mismo pañuelo
                </h1>
                <p class="mt-5 max-w-2xl text-base leading-relaxed text-ink-600 sm:text-lg">
                    Un recorrido por los momentos que han marcado la vida del grupo, contados por quienes los vivieron.
                </p>
            </div>
        </section>

        <section class="bg-sand-50 pb-20 sm:pb-28">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <p
                    v-if="!entries.length"
                    class="rounded-2xl border border-dashed border-sand-400 bg-white p-10 text-center text-ink-500"
                >
                    Estamos recopilando la historia del grupo. Vuelve pronto.
                </p>

                <ol v-else class="relative space-y-10 border-l-2 border-sand-300 pl-6 sm:pl-10">
                    <li v-for="(entry, index) in entries" :key="entry.id" v-reveal="index * 90" class="relative">
                        <span
                            class="absolute -left-[1.9rem] top-2 h-3.5 w-3.5 rounded-full bg-brand-600 ring-4 ring-sand-50 sm:-left-[2.9rem]"
                            aria-hidden="true"
                        ></span>
                        <p class="font-display text-sm font-bold uppercase tracking-wider text-brand-700">{{ entry.year }}</p>
                        <h2 class="mt-1 font-display text-xl font-bold text-ink-900 sm:text-2xl">{{ entry.title }}</h2>
                        <img
                            v-if="entry.photo_url"
                            :src="entry.photo_url"
                            :alt="entry.title"
                            class="mt-4 aspect-[16/10] w-full rounded-2xl object-cover shadow-sm"
                            loading="lazy"
                        />
                        <p v-if="entry.body" class="mt-4 text-base leading-relaxed text-ink-600">{{ entry.body }}</p>
                    </li>
                </ol>
            </div>
        </section>
    </PublicLayout>
</template>
