<script setup>
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import PublicLayout from '@/Layouts/PublicLayout.vue'
import ScoutPattern from '@/Components/Public/ScoutPattern.vue'
import PillarIcon from '@/Components/Public/PillarIcon.vue'
import CountUp from '@/Components/Public/CountUp.vue'
import FaqItem from '@/Components/Public/FaqItem.vue'
import reveal from '@/Directives/reveal'

const props = defineProps({
    group: { type: Object, required: true },
    gallery: { type: Array, default: () => [] },
    milestones: { type: Array, default: () => [] },
})

const vReveal = reveal

const contact = computed(() => props.group.contact ?? {})
const photos = computed(() => props.group.photos ?? {})
const sections = computed(() => props.group.sections ?? [])

const whatsappUrl = computed(() =>
    contact.value.whatsapp ? `https://wa.me/${contact.value.whatsapp}` : null,
)

// Segunda línea de la dirección: "11403 Jerez de la Frontera (Cádiz)".
const cityLine = computed(() => {
    const c = contact.value
    const ciudad = [c.postal_code, c.locality].filter(Boolean).join(' ')
    return c.province ? `${ciudad} (${c.province})` : ciudad
})

const mailtoUrl = computed(() => {
    if (!contact.value.email) return null
    const subject = encodeURIComponent('Información para apuntarse al grupo')
    return `mailto:${contact.value.email}?subject=${subject}`
})

const metaDescription = computed(
    () => `${props.group.name} — ${props.group.tagline}`,
)
</script>

<template>
    <Head>
        <!-- app.js añade « - {nombre del grupo}» al título, no lo repitas aquí. -->
        <title>{{ group.claim }}</title>
        <meta name="description" :content="metaDescription" />
        <meta property="og:title" :content="`${group.name} | ${group.claim}`" />
        <meta property="og:description" :content="metaDescription" />
        <meta property="og:type" content="website" />
    </Head>

    <PublicLayout transparent-on-top>
        <!-- ═══ Hero ═══ -->
        <section class="relative isolate flex min-h-[88svh] items-end overflow-hidden bg-ink-900 sm:min-h-[92svh]">
            <img
                v-if="photos.hero"
                :src="photos.hero"
                :alt="`Chavales del ${group.name} en una actividad`"
                class="absolute inset-0 -z-10 h-full w-full origin-center object-cover motion-safe:animate-acercar"
                fetchpriority="high"
            />
            <div v-else class="absolute inset-0 -z-10 bg-gradient-to-br from-forest-900 via-ink-900 to-brand-950">
                <ScoutPattern class="absolute inset-0 h-full w-full text-white/[0.07]" />
            </div>

            <!-- Velo para asegurar contraste del texto sobre cualquier foto -->
            <div class="absolute inset-0 -z-10 bg-gradient-to-t from-ink-950/90 via-ink-950/60 to-ink-950/35"></div>
            <!-- Refuerzo superior: la barra de navegación va en blanco y la foto puede tener cielo claro -->
            <div class="absolute inset-x-0 top-0 -z-10 h-32 bg-gradient-to-b from-ink-950/55 to-transparent"></div>

            <div class="mx-auto w-full max-w-6xl px-4 pb-16 pt-28 sm:px-6 sm:pb-24 sm:pt-32 lg:px-8 lg:pb-28">
                <p
                    class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs font-semibold uppercase tracking-[0.18em] text-white/70 animate-hero sm:text-sm"
                    style="animation-delay: 80ms"
                >
                    <span class="inline-block h-px w-8 origin-left bg-white/50" aria-hidden="true"></span>
                    {{ group.federation }}
                </p>

                <h1
                    class="mt-5 max-w-4xl font-display text-4xl font-extrabold leading-[1.05] tracking-tight text-white animate-hero sm:text-6xl lg:text-7xl"
                    style="animation-delay: 180ms"
                >
                    {{ group.claim }}
                </h1>

                <p
                    class="mt-5 max-w-xl text-base leading-relaxed text-white/85 animate-hero sm:mt-6 sm:text-lg"
                    style="animation-delay: 320ms"
                >
                    {{ group.tagline }}
                </p>

                <div class="mt-8 flex flex-col gap-3 animate-hero sm:mt-10 sm:flex-row sm:items-center" style="animation-delay: 450ms">
                    <a
                        href="#unete"
                        class="group inline-flex items-center justify-center gap-2 rounded-full bg-brand-600 px-7 py-3.5 text-base font-semibold text-white shadow-lg shadow-brand-950/30 transition-all duration-300 ease-suave hover:-translate-y-0.5 hover:bg-brand-700 hover:shadow-xl hover:shadow-brand-950/40 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white active:translate-y-0 active:scale-[0.98] motion-reduce:transform-none"
                    >
                        Quiero apuntar a mi hijo/a
                        <svg class="h-4 w-4 transition-transform duration-300 group-hover:translate-x-1 motion-reduce:transform-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                            <path d="M5 12h14M13 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </a>
                    <a
                        href="#quienes-somos"
                        class="inline-flex items-center justify-center rounded-full border border-white/40 bg-white/5 px-7 py-3.5 text-base font-semibold text-white backdrop-blur-sm transition-all duration-300 ease-suave hover:-translate-y-0.5 hover:border-white/70 hover:bg-white/15 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white active:translate-y-0 active:scale-[0.98] motion-reduce:transform-none"
                    >
                        Conoce el grupo
                    </a>
                </div>

                <!-- Pista de que hay más contenido abajo -->
                <a
                    href="#quienes-somos"
                    class="mt-12 hidden items-center gap-2 text-xs font-semibold uppercase tracking-[0.18em] text-white/60 transition-colors duration-300 hover:text-white animate-hero sm:inline-flex"
                    style="animation-delay: 700ms"
                >
                </a>
            </div>
        </section>

        <!-- ═══ Cifras ═══ -->
        <section class="bg-sand-50" aria-label="El grupo en cifras">
            <div class="relative z-10 mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <dl
                    v-reveal:scale
                    class="-mt-10 grid grid-cols-2 gap-px overflow-hidden rounded-2xl border border-sand-300 bg-sand-300 shadow-lg shadow-ink-900/5 sm:-mt-14 lg:grid-cols-4"
                >
                    <div v-for="stat in group.stats ?? []" :key="stat.label" class="bg-white px-4 py-6 text-center sm:px-6 sm:py-8">
                        <dt class="order-2 mt-1.5 text-xs font-medium uppercase tracking-wide text-ink-500 sm:text-sm">
                            {{ stat.label }}
                        </dt>
                        <dd class="font-display text-3xl font-extrabold text-brand-700 sm:text-4xl">
                            <CountUp :value="stat.value" />
                        </dd>
                    </div>
                </dl>
            </div>
        </section>

        <!-- ═══ Quiénes somos ═══ -->
        <section id="quienes-somos" class="scroll-mt-20 bg-sand-50 py-20 sm:py-28">
            <div class="mx-auto grid max-w-6xl items-center gap-10 px-4 sm:px-6 lg:grid-cols-2 lg:gap-16 lg:px-8">
                <div v-reveal class="order-2 lg:order-1">
                    <p class="font-display text-sm font-bold uppercase tracking-[0.18em] text-brand-700">Quiénes somos</p>
                    <h2 class="mt-4 font-display text-3xl font-extrabold leading-tight tracking-tight text-ink-900 sm:text-4xl lg:text-5xl">
                        Un grupo scout con raíces en el barrio
                    </h2>
                    <div class="mt-6 space-y-4 text-base leading-relaxed text-ink-600 sm:text-lg">
                        <p>
                            Somos el {{ group.name }}, parte del {{ group.federation }}. Desde
                            {{ group.founded_year }} acompañamos a chavales de {{ contact.locality }} en su crecimiento,
                            con un método educativo probado en todo el mundo.
                        </p>
                        <p>
                            Cada sábado, un equipo de responsables voluntarios prepara actividades pensadas para cada edad.
                            No damos clase: proponemos retos, juegos y proyectos donde aprenden decidiendo, equivocándose
                            y volviéndolo a intentar.
                        </p>
                    </div>

                    <ul class="mt-8 grid gap-3 sm:grid-cols-2">
                        <li
                            v-for="(item, index) in [
                                'Responsables titulados y voluntarios',
                                'Ratios legales siempre respetadas',
                                'Actividades cada sábado del curso',
                                'Campamento de verano y salidas',
                            ]"
                            :key="item"
                            v-reveal="200 + index * 90"
                            class="flex items-start gap-2.5 text-sm font-medium text-ink-700"
                        >
                            <svg class="mt-0.5 h-5 w-5 shrink-0 text-forest-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                                <path d="M20 6 9 17l-5-5" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <span>{{ item }}</span>
                        </li>
                    </ul>
                </div>

                <div v-reveal:scale="120" class="order-1 lg:order-2">
                    <div class="relative aspect-[4/3] overflow-hidden rounded-3xl bg-forest-800 shadow-xl shadow-ink-900/10">
                        <img
                            v-if="photos.about"
                            :src="photos.about"
                            :alt="`Responsables y chavales del ${group.name}`"
                            class="h-full w-full object-cover"
                            loading="lazy"
                        />
                        <div v-else class="absolute inset-0 bg-gradient-to-br from-forest-700 to-forest-900">
                            <ScoutPattern class="absolute inset-0 h-full w-full text-white/10" />
                            <div class="absolute inset-0 flex flex-col items-center justify-center gap-3 p-8 text-center">
                                <img src="/images/logosj.png" alt="" class="h-14 w-auto brightness-0 invert opacity-90" aria-hidden="true" />
                                <p class="text-sm text-white/60">Desde {{ group.founded_year }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══ Secciones ═══ -->
        <section id="secciones" class="scroll-mt-20 border-y border-sand-200 bg-white py-20 sm:py-28">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <div v-reveal class="max-w-2xl">
                    <p class="font-display text-sm font-bold uppercase tracking-[0.18em] text-brand-700">Secciones</p>
                    <h2 class="mt-4 font-display text-3xl font-extrabold leading-tight tracking-tight text-ink-900 sm:text-4xl lg:text-5xl">
                        Cada edad, su aventura
                    </h2>
                    <p class="mt-5 text-base leading-relaxed text-ink-600 sm:text-lg">
                        El grupo se divide en cinco secciones. Cada una tiene su propio estilo, su lema y actividades
                        adaptadas al momento vital de los chavales.
                    </p>
                </div>

                <ul class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    <li
                        v-for="(section, index) in sections"
                        :key="section.key"
                        v-reveal="index * 90"
                        class="group relative flex flex-col overflow-hidden rounded-2xl border border-sand-300 bg-sand-50 transition-all duration-500 ease-suave hover:-translate-y-1.5 hover:border-sand-400 hover:shadow-2xl hover:shadow-ink-900/10 motion-reduce:transform-none"
                    >
                        <div class="relative h-48 overflow-hidden sm:h-52">
                            <img
                                v-if="section.photo"
                                :src="section.photo"
                                :alt="`Sección de ${section.name}`"
                                class="h-full w-full object-cover object-top transition-transform duration-700 ease-suave group-hover:scale-[1.08] motion-reduce:transform-none"
                                loading="lazy"
                            />
                            <div
                                v-else
                                class="h-full w-full"
                                :style="{ backgroundColor: section.color }"
                            >
                                <ScoutPattern class="h-full w-full text-white/25" />
                            </div>
                            <span
                                class="absolute left-4 top-4 inline-flex items-center rounded-full bg-white/95 px-3 py-1 text-xs font-bold uppercase tracking-wide shadow-sm transition-transform duration-500 ease-suave group-hover:-translate-y-0.5 motion-reduce:transform-none"
                                :style="{ color: section.color }"
                            >
                                {{ section.ages }}
                            </span>
                        </div>

                        <div class="flex flex-1 flex-col p-5 sm:p-6">
                            <h3 class="font-display text-xl font-bold text-ink-900">{{ section.name }}</h3>
                            <p class="mt-0.5 text-sm font-medium text-ink-400">
                                {{ section.unit }} · «{{ section.motto }}»
                            </p>
                            <p class="mt-3 flex-1 text-sm leading-relaxed text-ink-600">
                                {{ section.description }}
                            </p>
                            <!-- La barra de color crece al pasar por encima -->
                            <span
                                class="mt-4 block h-1 w-12 origin-left rounded-full transition-transform duration-500 ease-suave group-hover:scale-x-[2.6] motion-reduce:transform-none"
                                :style="{ backgroundColor: section.color }"
                                aria-hidden="true"
                            ></span>
                        </div>
                    </li>

                    <li
                        v-reveal="sections.length * 90"
                        class="flex flex-col justify-center gap-4 rounded-2xl border border-dashed border-brand-300 bg-brand-50/60 p-6 text-center transition-colors duration-500 hover:border-brand-400 hover:bg-brand-50 sm:p-8"
                    >
                        <h3 class="font-display text-xl font-bold text-ink-900">¿No sabes qué sección le toca?</h3>
                        <p class="text-sm leading-relaxed text-ink-600">
                            Dinos la edad de tu hijo o hija y te decimos dónde encaja y cuándo puede venir a conocernos.
                        </p>
                        <a
                            href="#unete"
                            class="group mx-auto inline-flex items-center gap-2 rounded-full bg-brand-700 px-6 py-3 text-sm font-semibold text-white transition-all duration-300 hover:bg-brand-800 hover:shadow-md active:scale-[0.97]"
                        >
                            Pregúntanos
                            <svg class="h-4 w-4 transition-transform duration-300 group-hover:translate-x-1 motion-reduce:transform-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                                <path d="M5 12h14M13 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </a>
                    </li>
                </ul>
            </div>
        </section>

        <!-- ═══ Qué hacemos ═══ -->
        <section id="que-hacemos" class="scroll-mt-20 bg-sand-50 py-20 sm:py-28">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <div v-reveal class="max-w-2xl">
                    <p class="font-display text-sm font-bold uppercase tracking-[0.18em] text-brand-700">Qué hacemos</p>
                    <h2 class="mt-4 font-display text-3xl font-extrabold leading-tight tracking-tight text-ink-900 sm:text-4xl lg:text-5xl">
                        Aprender jugando, crecer sirviendo
                    </h2>
                </div>

                <ul class="mt-12 grid gap-5 sm:grid-cols-2">
                    <li
                        v-for="(pillar, index) in group.pillars ?? []"
                        :key="pillar.title"
                        v-reveal="index * 90"
                        class="group rounded-2xl border border-sand-300 bg-white p-6 transition-all duration-500 ease-suave hover:-translate-y-1 hover:border-brand-200 hover:shadow-xl hover:shadow-ink-900/5 motion-reduce:transform-none sm:p-8"
                    >
                        <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-brand-50 text-brand-700 transition-all duration-500 ease-suave group-hover:bg-brand-100 group-hover:scale-110 motion-reduce:transform-none">
                            <PillarIcon :name="pillar.icon" class="h-6 w-6" />
                        </span>
                        <h3 class="mt-5 font-display text-xl font-bold text-ink-900">{{ pillar.title }}</h3>
                        <p class="mt-2.5 text-sm leading-relaxed text-ink-600 sm:text-base">{{ pillar.text }}</p>
                    </li>
                </ul>

                <!-- Banda de campamento -->
                <div v-reveal class="relative mt-6 isolate overflow-hidden rounded-3xl bg-forest-900 sm:mt-8">
                    <img
                        v-if="photos.camp"
                        :src="photos.camp"
                        alt="Campamento de verano del grupo"
                        class="absolute inset-0 -z-10 h-full w-full object-cover"
                        loading="lazy"
                    />
                    <ScoutPattern v-else class="absolute inset-0 -z-10 h-full w-full text-white/[0.08]" />
                    <div class="absolute inset-0 -z-10 bg-gradient-to-r from-forest-900/95 via-forest-900/80 to-forest-900/50"></div>

                    <div class="max-w-xl px-6 py-12 sm:px-10 sm:py-16">
                        <h3 class="font-display text-2xl font-extrabold leading-tight text-white sm:text-3xl">
                            Diez días de campamento que recuerdan toda la vida
                        </h3>
                        <p class="mt-4 text-base leading-relaxed text-white/80">
                            Cada verano montamos el campamento en plena naturaleza: construcciones, veladas, rutas y una
                            convivencia que convierte a un grupo de chavales en un equipo.
                        </p>
                        <Link
                            href="/galeria"
                            class="group mt-7 inline-flex items-center gap-2 rounded-full bg-white px-6 py-3 text-sm font-semibold text-forest-900 transition-all duration-300 ease-suave hover:-translate-y-0.5 hover:bg-sand-100 hover:shadow-lg active:translate-y-0 active:scale-[0.98] motion-reduce:transform-none"
                        >
                            Ver fotos de nuestras actividades
                            <svg class="h-4 w-4 transition-transform duration-300 group-hover:translate-x-1 motion-reduce:transform-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                                <path d="M5 12h14M13 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══ Galería ═══ -->
        <section v-if="gallery.length" class="border-y border-sand-200 bg-white py-20 sm:py-28">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <div v-reveal class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div class="max-w-2xl">
                        <p class="font-display text-sm font-bold uppercase tracking-[0.18em] text-brand-700">Galería</p>
                        <h2 class="mt-4 font-display text-3xl font-extrabold leading-tight tracking-tight text-ink-900 sm:text-4xl">
                            Así vivimos el escultismo
                        </h2>
                    </div>
                    <Link
                        href="/galeria"
                        class="inline-flex shrink-0 items-center gap-2 rounded-full border border-ink-300 px-5 py-2.5 text-sm font-semibold text-ink-700 transition hover:border-ink-400 hover:bg-sand-50"
                    >
                        Ver toda la galería
                    </Link>
                </div>

                <ul class="mt-10 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                    <li
                        v-for="(photo, index) in gallery"
                        :key="photo.id"
                        v-reveal:scale="index * 60"
                        class="group relative aspect-square overflow-hidden rounded-xl bg-sand-200"
                    >
                        <img
                            :src="photo.url"
                            :alt="photo.caption || `Foto del álbum ${photo.album}`"
                            class="h-full w-full object-cover transition-transform duration-700 ease-suave group-hover:scale-110 motion-reduce:transform-none"
                            loading="lazy"
                        />
                        <span class="absolute inset-0 bg-ink-950/0 transition-colors duration-500 group-hover:bg-ink-950/15"></span>
                    </li>
                </ul>
            </div>
        </section>

        <!-- ═══ Historia ═══ -->
        <section v-if="milestones.length" class="bg-sand-50 py-20 sm:py-28">
            <div class="mx-auto grid max-w-6xl gap-10 px-4 sm:px-6 lg:grid-cols-[1fr_1.1fr] lg:items-center lg:gap-16 lg:px-8">
                <div v-reveal>
                    <p class="font-display text-sm font-bold uppercase tracking-[0.18em] text-brand-700">Nuestra historia</p>
                    <h2 class="mt-4 font-display text-3xl font-extrabold leading-tight tracking-tight text-ink-900 sm:text-4xl">
                        Muchas generaciones, el mismo pañuelo
                    </h2>
                    <p class="mt-5 text-base leading-relaxed text-ink-600 sm:text-lg">
                        Desde {{ group.founded_year }}, cientos de familias han pasado por el grupo. Estos son algunos de
                        los momentos que nos han traído hasta aquí.
                    </p>
                    <Link
                        href="/historia"
                        class="mt-7 inline-flex items-center gap-2 rounded-full border border-ink-300 px-5 py-2.5 text-sm font-semibold text-ink-700 transition hover:border-ink-400 hover:bg-white"
                    >
                        Ver la línea del tiempo completa
                    </Link>
                </div>

                <ol class="relative space-y-6 border-l-2 border-sand-300 pl-6 sm:pl-8">
                    <li
                        v-for="(milestone, index) in milestones"
                        :key="milestone.id"
                        v-reveal="index * 110"
                        class="relative"
                    >
                        <span class="absolute -left-[1.9rem] top-1.5 h-3 w-3 rounded-full bg-brand-600 ring-4 ring-sand-50 sm:-left-[2.4rem]" aria-hidden="true"></span>
                        <p class="font-display text-sm font-bold text-brand-700">{{ milestone.year }}</p>
                        <p class="mt-1 text-lg font-semibold leading-snug text-ink-800">{{ milestone.title }}</p>
                    </li>
                </ol>
            </div>
        </section>

        <!-- ═══ Únete ═══ -->
        <section id="unete" class="scroll-mt-20 border-y border-sand-200 bg-white py-20 sm:py-28">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <div v-reveal class="max-w-2xl">
                    <p class="font-display text-sm font-bold uppercase tracking-[0.18em] text-brand-700">Únete</p>
                    <h2 class="mt-4 font-display text-3xl font-extrabold leading-tight tracking-tight text-ink-900 sm:text-4xl lg:text-5xl">
                        Apuntarse es más fácil de lo que parece
                    </h2>
                    <p class="mt-5 text-base leading-relaxed text-ink-600 sm:text-lg">
                        Tres pasos y sin compromiso. Podéis venir a conocernos antes de decidir nada.
                    </p>
                </div>

                <ol class="mt-12 grid gap-5 sm:grid-cols-3">
                    <li
                        v-for="(step, index) in group.join_steps ?? []"
                        :key="step.title"
                        v-reveal="index * 110"
                        class="group relative overflow-hidden rounded-2xl border border-sand-300 bg-sand-50 p-6 transition-all duration-500 ease-suave hover:-translate-y-1 hover:border-brand-200 hover:shadow-lg motion-reduce:transform-none sm:p-8"
                    >
                        <span class="font-display text-4xl font-extrabold text-brand-200 transition-colors duration-500 group-hover:text-brand-300">0{{ index + 1 }}</span>
                        <h3 class="mt-2 font-display text-lg font-bold text-ink-900">{{ step.title }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-ink-600">{{ step.text }}</p>
                    </li>
                </ol>

                <div v-reveal class="mt-8 flex flex-col gap-3 rounded-2xl bg-brand-700 p-6 sm:flex-row sm:items-center sm:justify-between sm:p-8">
                    <div>
                        <p class="font-display text-xl font-bold text-white sm:text-2xl">¿Damos el primer paso?</p>
                        <p class="mt-1 text-sm text-white/80 sm:text-base">Te respondemos en cuanto podamos, normalmente en 48 horas.</p>
                    </div>
                    <div class="flex flex-col gap-2.5 sm:flex-row">
                        <a
                            v-if="mailtoUrl"
                            :href="mailtoUrl"
                            class="inline-flex items-center justify-center gap-2 rounded-full bg-white px-6 py-3 text-sm font-semibold text-brand-800 transition-all duration-300 ease-suave hover:-translate-y-0.5 hover:bg-sand-100 hover:shadow-lg active:translate-y-0 active:scale-[0.98] motion-reduce:transform-none"
                        >
                            Escríbenos por email
                        </a>
                        <a
                            v-if="whatsappUrl"
                            :href="whatsappUrl"
                            target="_blank"
                            rel="noopener"
                            class="inline-flex items-center justify-center gap-2 rounded-full border border-white/50 px-6 py-3 text-sm font-semibold text-white transition-all duration-300 ease-suave hover:-translate-y-0.5 hover:border-white hover:bg-white/15 active:translate-y-0 motion-reduce:transform-none"
                        >
                            WhatsApp
                        </a>
                    </div>
                </div>

                <!-- Preguntas frecuentes -->
                <div class="mt-16 grid gap-8 lg:grid-cols-[0.8fr_1.2fr] lg:gap-16">
                    <h3 v-reveal class="font-display text-2xl font-extrabold leading-tight text-ink-900 sm:text-3xl">
                        Preguntas frecuentes
                    </h3>

                    <ul v-reveal class="divide-y divide-sand-200 border-y border-sand-200">
                        <li v-for="item in group.faq ?? []" :key="item.q">
                            <FaqItem :question="item.q" :answer="item.a" />
                        </li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- ═══ Contacto ═══ -->
        <section id="contacto" class="scroll-mt-20 bg-sand-50 py-20 sm:py-28">
            <div class="mx-auto grid max-w-6xl gap-10 px-4 sm:px-6 lg:grid-cols-2 lg:gap-16 lg:px-8">
                <div v-reveal>
                    <p class="font-display text-sm font-bold uppercase tracking-[0.18em] text-brand-700">Contacto</p>
                    <h2 class="mt-4 font-display text-3xl font-extrabold leading-tight tracking-tight text-ink-900 sm:text-4xl">
                        Dónde estamos y cuándo nos vemos
                    </h2>
                    <p class="mt-5 text-base leading-relaxed text-ink-600">
                        Nos reunimos en nuestro local. Si quieres pasarte, avísanos antes y te esperamos con calma para
                        enseñártelo todo.
                    </p>

                    <dl class="mt-8 space-y-5">
                        <div v-if="contact.address" class="flex items-start gap-4">
                            <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white text-brand-700 ring-1 ring-sand-300">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path d="M12 21s7-5.5 7-11a7 7 0 1 0-14 0c0 5.5 7 11 7 11Z" stroke-linejoin="round" />
                                    <circle cx="12" cy="10" r="2.5" />
                                </svg>
                            </span>
                            <div class="min-w-0">
                                <dt class="text-sm font-semibold text-ink-900">Local del grupo</dt>
                                <dd class="mt-0.5 text-sm text-ink-600">{{ contact.address }}</dd>
                                <dd v-if="cityLine" class="text-sm text-ink-600">{{ cityLine }}</dd>
                                <dd v-if="contact.map_url" class="mt-1">
                                    <a :href="contact.map_url" target="_blank" rel="noopener" class="text-sm font-semibold text-brand-700 underline-offset-4 hover:underline">
                                        Ver en el mapa
                                    </a>
                                </dd>
                            </div>
                        </div>

                        <div v-if="contact.schedule" class="flex items-start gap-4">
                            <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white text-brand-700 ring-1 ring-sand-300">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <circle cx="12" cy="12" r="9" />
                                    <path d="M12 7v5l3.5 2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </span>
                            <div class="min-w-0">
                                <dt class="text-sm font-semibold text-ink-900">Reuniones</dt>
                                <dd class="mt-0.5 text-sm text-ink-600">{{ contact.schedule }}</dd>
                            </div>
                        </div>

                        <div v-if="contact.email" class="flex items-start gap-4">
                            <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white text-brand-700 ring-1 ring-sand-300">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <rect x="3" y="5" width="18" height="14" rx="3" />
                                    <path d="m4 7 8 6 8-6" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </span>
                            <div class="min-w-0">
                                <dt class="text-sm font-semibold text-ink-900">Email</dt>
                                <dd class="mt-0.5 break-words text-sm text-ink-600">
                                    <a :href="`mailto:${contact.email}`" class="transition hover:text-brand-700">{{ contact.email }}</a>
                                </dd>
                            </div>
                        </div>

                        <div v-if="contact.phone" class="flex items-start gap-4">
                            <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white text-brand-700 ring-1 ring-sand-300">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path d="M6 3h3l2 5-2.5 1.5a12 12 0 0 0 6 6L16 13l5 2v3a2 2 0 0 1-2.2 2A17 17 0 0 1 4 5.2 2 2 0 0 1 6 3Z" stroke-linejoin="round" />
                                </svg>
                            </span>
                            <div class="min-w-0">
                                <dt class="text-sm font-semibold text-ink-900">Teléfono</dt>
                                <dd class="mt-0.5 text-sm text-ink-600">
                                    <a :href="`tel:${contact.phone.replace(/\s/g, '')}`" class="transition hover:text-brand-700">{{ contact.phone }}</a>
                                </dd>
                            </div>
                        </div>
                    </dl>
                </div>

                <div v-reveal class="relative isolate flex min-h-[18rem] flex-col justify-end overflow-hidden rounded-3xl bg-ink-900 p-6 sm:p-10">
                    <ScoutPattern class="absolute inset-0 -z-10 h-full w-full text-white/[0.08]" />
                    <div class="absolute inset-0 -z-10 bg-gradient-to-br from-brand-900/70 to-ink-950/90"></div>
                    <img src="/images/logosj.png" alt="" class="mb-6 h-12 w-auto brightness-0 invert" aria-hidden="true" />
                    <p class="font-display text-2xl font-extrabold leading-tight text-white sm:text-3xl">
                        «Intenta dejar este mundo un poco mejor de como te lo encontraste.»
                    </p>
                    <p class="mt-3 text-sm font-medium text-white/70">Baden-Powell, fundador del escultismo</p>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
