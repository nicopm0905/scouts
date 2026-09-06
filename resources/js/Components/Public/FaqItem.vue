<script setup>
import { ref } from 'vue'

/**
 * Pregunta frecuente con apertura suave (altura animada) y accesible:
 * es un botón real con aria-expanded, y el panel se anuncia con region.
 */
defineProps({
    question: { type: String, required: true },
    answer: { type: String, required: true },
})

const abierto = ref(false)
const panel = ref(null)

// Anima de 0 a la altura real del contenido, y vuelve a 0 al cerrar.
const alEntrar = (el) => {
    el.style.height = '0px'
    el.offsetHeight // fuerza reflow para que la transición arranque
    el.style.height = `${el.scrollHeight}px`
}

const alEntrarFin = (el) => {
    el.style.height = 'auto'
}

const alSalir = (el) => {
    el.style.height = `${el.scrollHeight}px`
    el.offsetHeight
    el.style.height = '0px'
}
</script>

<template>
    <div class="group">
        <h3>
            <button
                type="button"
                class="flex w-full items-start justify-between gap-4 py-5 text-left font-semibold text-ink-900 transition-colors duration-200 hover:text-brand-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-brand-600"
                :aria-expanded="abierto"
                @click="abierto = !abierto"
            >
                <span>{{ question }}</span>
                <span
                    class="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full border border-sand-300 text-brand-700 transition-all duration-300 ease-out"
                    :class="abierto ? 'rotate-45 border-brand-300 bg-brand-50' : 'group-hover:border-brand-200'"
                >
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                        <path d="M12 5v14M5 12h14" stroke-linecap="round" />
                    </svg>
                </span>
            </button>
        </h3>

        <Transition
            enter-active-class="overflow-hidden transition-[height] duration-300 ease-out"
            leave-active-class="overflow-hidden transition-[height] duration-200 ease-in"
            @enter="alEntrar"
            @after-enter="alEntrarFin"
            @leave="alSalir"
        >
            <div v-show="abierto" ref="panel">
                <p class="max-w-2xl pb-5 text-sm leading-relaxed text-ink-600 sm:text-base">{{ answer }}</p>
            </div>
        </Transition>
    </div>
</template>
