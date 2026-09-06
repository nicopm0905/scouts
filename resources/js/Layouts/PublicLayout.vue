<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'

const props = defineProps({
    // Cuando la página empieza con un hero a sangre, la barra arranca transparente.
    transparentOnTop: { type: Boolean, default: false },
})

const page = usePage()
const group = computed(() => page.props.group ?? {})
const groupName = computed(() => group.value.name ?? 'Grupo Scout')
const contact = computed(() => group.value.contact ?? {})
const social = computed(() => group.value.social ?? {})
const year = new Date().getFullYear()

const links = [
    { label: 'Quiénes somos', href: '/#quienes-somos', section: 'quienes-somos' },
    { label: 'Secciones', href: '/#secciones', section: 'secciones' },
    { label: 'Qué hacemos', href: '/#que-hacemos', section: 'que-hacemos' },
    { label: 'Galería', href: '/galeria' },
    { label: 'Historia', href: '/historia' },
    { label: 'Contacto', href: '/#contacto', section: 'contacto' },
]

/*
| Estados de la cabecera:
| - reposo (arriba del todo): ancha y transparente sobre el hero
| - compacta: al bajar se contrae en una "píldora" flotante con desenfoque
| - oculta: al seguir bajando se retira; vuelve en cuanto subes
*/
const compacta = ref(false)
const oculta = ref(false)
const menuOpen = ref(false)
const seccionActiva = ref(null)
const arriba = ref(true)

let ultimoScroll = 0

const onScroll = () => {
    const y = window.scrollY
    arriba.value = y < 8
    compacta.value = y > 24
    // Solo se esconde bien pasado el hero, y nunca con el menú abierto.
    oculta.value = !menuOpen.value && y > 320 && y > ultimoScroll + 4
    ultimoScroll = y
}

let observadorSecciones = null

// Resalta en el menú la sección que ocupa el centro de la pantalla.
const observarSecciones = () => {
    // Se observa también 'unete', que no está en el menú: así ningún enlace
    // queda marcado cuando el lector está en esa sección.
    const ids = [...links.filter((l) => l.section).map((l) => l.section), 'unete']
    const nodos = ids.map((id) => document.getElementById(id)).filter(Boolean)

    if (!nodos.length || !('IntersectionObserver' in window)) return

    observadorSecciones = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) seccionActiva.value = entry.target.id
            })
        },
        { rootMargin: '-45% 0px -45% 0px', threshold: 0 },
    )

    nodos.forEach((n) => observadorSecciones.observe(n))
}

onMounted(() => {
    onScroll()
    window.addEventListener('scroll', onScroll, { passive: true })
    observarSecciones()
})

onBeforeUnmount(() => {
    window.removeEventListener('scroll', onScroll)
    observadorSecciones?.disconnect()
    document.body.style.overflow = ''
})

// Bloquea el scroll de fondo mientras el menú móvil está abierto.
watch(menuOpen, (open) => {
    document.body.style.overflow = open ? 'hidden' : ''
    if (open) oculta.value = false
})

// Sobre el hero los textos van en blanco; en cuanto se compacta, en tinta.
const sobreHero = computed(() => props.transparentOnTop && !compacta.value && !menuOpen.value)

const esActivo = (link) => Boolean(link.section) && seccionActiva.value === link.section

const whatsappUrl = computed(() =>
    contact.value.whatsapp ? `https://wa.me/${contact.value.whatsapp}` : null,
)

const cityLine = computed(() => {
    const c = contact.value
    const ciudad = [c.postal_code, c.locality].filter(Boolean).join(' ')
    return c.province ? `${ciudad} (${c.province})` : ciudad
})

const subirArriba = () => {
    const sinMovimiento = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches
    window.scrollTo({ top: 0, behavior: sinMovimiento ? 'auto' : 'smooth' })
}
</script>

<template>
    <!-- overflow-x: clip evita barras horizontales durante las animaciones de entrada -->
    <div class="min-h-screen bg-sand-50 font-sans text-ink-800 antialiased [overflow-x:clip]">
        <a
            href="#contenido"
            class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-[60] focus:rounded-full focus:bg-brand-700 focus:px-5 focus:py-2 focus:text-sm focus:font-semibold focus:text-white"
        >
            Saltar al contenido principal
        </a>

        <!-- ═══ Cabecera flotante ═══ -->
        <header
            class="ease-suave fixed inset-x-0 top-0 z-50 transition-transform duration-500 motion-reduce:transition-none"
            :class="oculta ? '-translate-y-[130%]' : 'translate-y-0'"
        >
            <div
                class="ease-suave mx-auto transition-all duration-500 motion-reduce:transition-none"
                :class="compacta
                    ? 'mt-2 max-w-5xl px-3 sm:mt-3 sm:px-4'
                    : 'mt-0 max-w-6xl px-4 sm:px-6 lg:px-8'"
            >
                <nav
                    class="ease-suave flex items-center justify-between gap-3 transition-all duration-500 motion-reduce:transition-none"
                    :class="[
                        compacta
                            ? 'h-14 rounded-full border border-sand-300/80 bg-sand-50/85 px-3 shadow-lg shadow-ink-900/5 backdrop-blur-xl sm:px-4'
                            : 'h-16 rounded-full border border-transparent px-0 sm:h-20',
                        menuOpen && !compacta ? 'bg-sand-50/95 px-3 shadow-lg backdrop-blur-xl' : '',
                    ]"
                    aria-label="Navegación principal"
                >
                    <Link
                        href="/"
                        class="flex min-w-0 items-center rounded-full transition-transform duration-300 hover:scale-[1.03] active:scale-[0.98] motion-reduce:transform-none"
                        :aria-label="`${groupName} — Inicio`"
                        @click="menuOpen = false"
                    >
                        <img
                            src="/images/logosj.png"
                            :alt="`Logotipo de ${groupName}`"
                            class="ease-suave w-auto shrink-0 transition-all duration-500 motion-reduce:transition-none"
                            :class="[compacta ? 'h-7 sm:h-8' : 'h-8 sm:h-10', sobreHero ? 'brightness-0 invert' : '']"
                        />
                    </Link>

                    <!-- Enlaces (escritorio) -->
                    <ul class="hidden items-center gap-0.5 lg:flex">
                        <li v-for="link in links" :key="link.href">
                            <a
                                :href="link.href"
                                class="group relative block rounded-full px-3 py-2 text-sm font-medium transition-colors duration-200"
                                :class="[
                                    sobreHero ? 'text-white/85 hover:text-white' : 'text-ink-600 hover:text-ink-900',
                                    esActivo(link) ? (sobreHero ? 'text-white' : 'text-brand-700') : '',
                                ]"
                            >
                                <span class="whitespace-nowrap">{{ link.label }}</span>
                                <!-- Subrayado que crece al pasar por encima y se queda en la sección activa -->
                                <span
                                    class="absolute inset-x-3 bottom-1 h-0.5 origin-center rounded-full transition-transform duration-300 ease-out group-hover:scale-x-100 motion-reduce:transition-none"
                                    :class="[
                                        sobreHero ? 'bg-white' : 'bg-brand-600',
                                        esActivo(link) ? 'scale-x-100' : 'scale-x-0',
                                    ]"
                                    aria-hidden="true"
                                ></span>
                            </a>
                        </li>
                    </ul>

                    <div class="flex items-center gap-2">
                        <a
                            href="/#unete"
                            class="ease-suave group hidden items-center gap-1.5 whitespace-nowrap rounded-full bg-brand-700 text-sm font-semibold text-white shadow-sm transition-all duration-300 hover:bg-brand-800 hover:shadow-md focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-700 active:scale-[0.97] motion-reduce:transition-none sm:inline-flex"
                            :class="compacta ? 'px-4 py-2' : 'px-5 py-2.5'"
                        >
                            Únete al grupo
                            <svg
                                class="h-3.5 w-3.5 transition-transform duration-300 group-hover:translate-x-0.5 motion-reduce:transform-none"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"
                            >
                                <path d="M5 12h14M13 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </a>
                        <Link
                            href="/login"
                            class="hidden whitespace-nowrap rounded-full border px-4 py-2 text-sm font-medium transition-all duration-300 active:scale-[0.97] lg:inline-flex"
                            :class="sobreHero
                                ? 'border-white/40 text-white hover:bg-white/15'
                                : 'border-ink-300 text-ink-700 hover:border-ink-400 hover:bg-white'"
                        >
                            Acceso responsables
                        </Link>

                        <!-- Botón de menú (móvil/tablet) -->
                        <button
                            type="button"
                            class="inline-flex h-11 w-11 items-center justify-center rounded-full transition-colors duration-200 active:scale-95 lg:hidden"
                            :class="sobreHero ? 'text-white hover:bg-white/15' : 'text-ink-800 hover:bg-sand-200'"
                            :aria-expanded="menuOpen"
                            aria-controls="menu-movil"
                            :aria-label="menuOpen ? 'Cerrar menú' : 'Abrir menú de navegación'"
                            @click="menuOpen = !menuOpen"
                        >
                            <!-- Hamburguesa que se convierte en aspa -->
                            <span class="relative block h-4 w-6" aria-hidden="true">
                                <span
                                    class="absolute left-0 block h-0.5 w-6 rounded-full bg-current transition-all duration-300 ease-out motion-reduce:transition-none"
                                    :class="menuOpen ? 'top-1/2 -translate-y-1/2 rotate-45' : 'top-0'"
                                ></span>
                                <span
                                    class="absolute left-0 top-1/2 block h-0.5 w-6 -translate-y-1/2 rounded-full bg-current transition-all duration-200 motion-reduce:transition-none"
                                    :class="menuOpen ? 'scale-x-0 opacity-0' : 'scale-x-100 opacity-100'"
                                ></span>
                                <span
                                    class="absolute left-0 block h-0.5 w-6 rounded-full bg-current transition-all duration-300 ease-out motion-reduce:transition-none"
                                    :class="menuOpen ? 'bottom-1/2 translate-y-1/2 -rotate-45' : 'bottom-0'"
                                ></span>
                            </span>
                        </button>
                    </div>
                </nav>
            </div>

            <!-- Panel móvil -->
            <Transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="-translate-y-3 opacity-0"
                leave-active-class="transition duration-200 ease-in"
                leave-to-class="-translate-y-3 opacity-0"
            >
                <div
                    v-if="menuOpen"
                    id="menu-movil"
                    class="mx-3 mt-2 max-h-[calc(100dvh-6rem)] overflow-y-auto rounded-3xl border border-sand-300 bg-sand-50 px-3 pb-5 pt-3 shadow-xl shadow-ink-900/10 sm:mx-4 lg:hidden"
                >
                    <ul class="space-y-1">
                        <li
                            v-for="(link, index) in links"
                            :key="link.href"
                            class="animate-entrada motion-reduce:animate-none"
                            :style="{ animationDelay: `${index * 45}ms` }"
                        >
                            <a
                                :href="link.href"
                                class="flex items-center justify-between rounded-2xl px-4 py-3 text-base font-medium text-ink-700 transition-colors duration-200 hover:bg-sand-200 active:bg-sand-300"
                                :class="esActivo(link) ? 'bg-sand-200 text-brand-700' : ''"
                                @click="menuOpen = false"
                            >
                                {{ link.label }}
                                <svg class="h-4 w-4 text-ink-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path d="M9 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </a>
                        </li>
                    </ul>
                    <div class="mt-3 grid gap-2">
                        <a
                            href="/#unete"
                            class="rounded-2xl bg-brand-700 px-4 py-3 text-center text-base font-semibold text-white transition active:scale-[0.98]"
                            @click="menuOpen = false"
                        >
                            Únete al grupo
                        </a>
                        <Link
                            href="/login"
                            class="rounded-2xl border border-ink-300 px-4 py-3 text-center text-base font-medium text-ink-700 transition active:scale-[0.98]"
                            @click="menuOpen = false"
                        >
                            Acceso responsables
                        </Link>
                    </div>
                </div>
            </Transition>
        </header>

        <main id="contenido" :class="transparentOnTop ? '' : 'pt-20 sm:pt-24'">
            <slot />
        </main>

        <!-- ═══ Volver arriba ═══ -->
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="translate-y-3 scale-90 opacity-0"
            leave-active-class="transition duration-200 ease-in"
            leave-to-class="translate-y-3 scale-90 opacity-0"
        >
            <button
                v-if="!arriba && compacta"
                type="button"
                class="fixed bottom-5 right-5 z-40 inline-flex h-11 w-11 items-center justify-center rounded-full border border-sand-300 bg-white/90 text-ink-700 shadow-lg shadow-ink-900/10 backdrop-blur transition-all duration-300 hover:-translate-y-0.5 hover:text-brand-700 hover:shadow-xl active:scale-95 motion-reduce:transition-none sm:bottom-8 sm:right-8"
                aria-label="Volver arriba"
                @click="subirArriba"
            >
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M12 19V5M6 11l6-6 6 6" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </button>
        </Transition>

        <!-- ═══ Pie ═══ -->
        <footer class="border-t border-sand-300 bg-white">
            <div class="mx-auto max-w-6xl px-4 py-12 sm:px-6 sm:py-16 lg:px-8">
                <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="sm:col-span-2 lg:col-span-1">
                        <img src="/images/logosj.png" :alt="`Logotipo de ${groupName}`" class="h-12 w-auto" />
                        <p class="mt-4 max-w-xs text-sm leading-relaxed text-ink-500">
                            {{ group.tagline }}
                        </p>
                    </div>

                    <div>
                        <h2 class="font-display text-sm font-bold uppercase tracking-wider text-ink-900">Navegación</h2>
                        <ul class="mt-4 space-y-2.5">
                            <li v-for="link in links" :key="`f-${link.href}`">
                                <a
                                    :href="link.href"
                                    class="group inline-flex items-center gap-1.5 text-sm text-ink-500 transition-colors duration-200 hover:text-brand-700"
                                >
                                    <span
                                        class="h-px w-0 bg-brand-600 transition-all duration-300 ease-out group-hover:w-3 motion-reduce:transition-none"
                                        aria-hidden="true"
                                    ></span>
                                    {{ link.label }}
                                </a>
                            </li>
                        </ul>
                    </div>

                    <div>
                        <h2 class="font-display text-sm font-bold uppercase tracking-wider text-ink-900">Contacto</h2>
                        <ul class="mt-4 space-y-2.5 text-sm text-ink-500">
                            <li v-if="contact.address">
                                {{ contact.address }}<template v-if="cityLine"><br />{{ cityLine }}</template>
                            </li>
                            <li v-if="contact.email">
                                <a :href="`mailto:${contact.email}`" class="break-words transition-colors duration-200 hover:text-brand-700">{{ contact.email }}</a>
                            </li>
                            <li v-if="contact.phone">
                                <a :href="`tel:${contact.phone.replace(/\s/g, '')}`" class="transition-colors duration-200 hover:text-brand-700">{{ contact.phone }}</a>
                            </li>
                            <li v-if="whatsappUrl">
                                <a :href="whatsappUrl" target="_blank" rel="noopener" class="transition-colors duration-200 hover:text-brand-700">WhatsApp</a>
                            </li>
                        </ul>
                        <ul v-if="social.instagram || social.facebook || social.youtube" class="mt-4 flex gap-2">
                            <li v-if="social.instagram">
                                <a
                                    :href="social.instagram"
                                    target="_blank"
                                    rel="noopener"
                                    :aria-label="`Instagram de ${groupName}`"
                                    class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-sand-300 text-ink-500 transition-all duration-300 hover:-translate-y-0.5 hover:border-brand-300 hover:text-brand-700 motion-reduce:transform-none"
                                >
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                                        <rect x="3" y="3" width="18" height="18" rx="5" />
                                        <circle cx="12" cy="12" r="4" />
                                        <circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none" />
                                    </svg>
                                </a>
                            </li>
                            <li v-if="social.facebook">
                                <a
                                    :href="social.facebook"
                                    target="_blank"
                                    rel="noopener"
                                    :aria-label="`Facebook de ${groupName}`"
                                    class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-sand-300 text-ink-500 transition-all duration-300 hover:-translate-y-0.5 hover:border-brand-300 hover:text-brand-700 motion-reduce:transform-none"
                                >
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                                        <path d="M14 8.5V7a1.5 1.5 0 0 1 1.5-1.5H17V3h-2.5A4 4 0 0 0 10.5 7v1.5H8V11h2.5v10H14V11h2.4l.6-2.5H14Z" stroke-linejoin="round" />
                                    </svg>
                                </a>
                            </li>
                            <li v-if="social.youtube">
                                <a
                                    :href="social.youtube"
                                    target="_blank"
                                    rel="noopener"
                                    :aria-label="`YouTube de ${groupName}`"
                                    class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-sand-300 text-ink-500 transition-all duration-300 hover:-translate-y-0.5 hover:border-brand-300 hover:text-brand-700 motion-reduce:transform-none"
                                >
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                                        <rect x="2.5" y="5.5" width="19" height="13" rx="4" />
                                        <path d="M10.5 9.5v5l4.5-2.5-4.5-2.5Z" stroke-linejoin="round" />
                                    </svg>
                                </a>
                            </li>
                        </ul>
                    </div>

                    <div>
                        <h2 class="font-display text-sm font-bold uppercase tracking-wider text-ink-900">Pertenecemos a</h2>
                        <div class="mt-4 flex flex-wrap items-center gap-4">
                            <img src="/images/logo-msc.png" alt="Movimiento Scout Católico" class="h-10 w-auto object-contain opacity-80 transition-opacity duration-300 hover:opacity-100" />
                            <img src="/images/logo-andalucia.png" alt="Scouts de Andalucía" class="h-10 w-auto object-contain opacity-80 transition-opacity duration-300 hover:opacity-100" />
                            <img src="/images/logo-asidonia-jerez.png" alt="Diócesis de Asidonia-Jerez" class="h-10 w-auto object-contain opacity-80 transition-opacity duration-300 hover:opacity-100" />
                        </div>
                    </div>
                </div>

                <div class="mt-12 flex flex-col gap-3 border-t border-sand-200 pt-6 text-xs text-ink-400 sm:flex-row sm:items-center sm:justify-between">
                    <p>© {{ year }} {{ groupName }}. Todos los derechos reservados.</p>
                    <p>Hecho con ilusión por el equipo de responsables.</p>
                </div>
            </div>
        </footer>
    </div>
</template>
