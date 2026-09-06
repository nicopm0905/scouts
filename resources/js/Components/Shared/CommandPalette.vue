<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import DashIcon from '@/Components/Shared/DashIcon.vue'

/**
 * Paleta de comandos (Ctrl/⌘ + K): buscar y saltar a cualquier pantalla o
 * acción sin usar el ratón. Recibe ya filtrados por permisos los comandos.
 */
const props = defineProps({
    // [{ label, hint, href, icon, group }]
    commands: { type: Array, default: () => [] },
})

const abierta = ref(false)
const consulta = ref('')
const seleccion = ref(0)
const campo = ref(null)

const normaliza = (t) =>
    (t ?? '')
        .toLowerCase()
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '')

const resultados = computed(() => {
    const q = normaliza(consulta.value.trim())
    if (!q) return props.commands.slice(0, 12)

    return props.commands
        .filter((c) => normaliza(`${c.label} ${c.hint ?? ''} ${c.group ?? ''}`).includes(q))
        .slice(0, 12)
})

// Agrupa manteniendo el orden en que llegan los resultados.
const grupos = computed(() => {
    const mapa = new Map()
    resultados.value.forEach((c, indice) => {
        const clave = c.group ?? 'Ir a'
        if (!mapa.has(clave)) mapa.set(clave, [])
        mapa.get(clave).push({ ...c, indice })
    })
    return [...mapa.entries()]
})

watch(resultados, () => {
    seleccion.value = 0
})

const abrir = async () => {
    abierta.value = true
    consulta.value = ''
    seleccion.value = 0
    document.body.style.overflow = 'hidden'
    await nextTick()
    campo.value?.focus()
}

const cerrar = () => {
    abierta.value = false
    document.body.style.overflow = ''
}

const ejecutar = (comando) => {
    if (!comando) return
    cerrar()
    router.visit(comando.href)
}

const alPulsar = (event) => {
    // Ctrl+K / ⌘K abre; Escape cierra.
    if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault()
        abierta.value ? cerrar() : abrir()
        return
    }

    if (!abierta.value) return

    if (event.key === 'Escape') {
        event.preventDefault()
        cerrar()
    } else if (event.key === 'ArrowDown') {
        event.preventDefault()
        seleccion.value = (seleccion.value + 1) % Math.max(1, resultados.value.length)
    } else if (event.key === 'ArrowUp') {
        event.preventDefault()
        seleccion.value = (seleccion.value - 1 + resultados.value.length) % Math.max(1, resultados.value.length)
    } else if (event.key === 'Enter') {
        event.preventDefault()
        ejecutar(resultados.value[seleccion.value])
    }
}

onMounted(() => window.addEventListener('keydown', alPulsar))
onBeforeUnmount(() => {
    window.removeEventListener('keydown', alPulsar)
    document.body.style.overflow = ''
})

defineExpose({ abrir })
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0"
            leave-active-class="transition duration-100 ease-in"
            leave-to-class="opacity-0"
        >
            <div
                v-if="abierta"
                class="fixed inset-0 z-[80] bg-slate-900/40 backdrop-blur-sm"
                @click.self="cerrar"
            >
                <div class="mx-auto mt-[12vh] w-[92vw] max-w-xl px-2">
                    <Transition
                        appear
                        enter-active-class="transition duration-200 ease-out"
                        enter-from-class="-translate-y-2 scale-95 opacity-0"
                    >
                        <div
                            class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl"
                            role="dialog"
                            aria-modal="true"
                            aria-label="Buscar en la plataforma"
                        >
                            <!-- Campo de búsqueda -->
                            <div class="flex items-center gap-3 border-b border-slate-100 px-4">
                                <DashIcon name="search" class="h-5 w-5 shrink-0 text-slate-400" />
                                <input
                                    ref="campo"
                                    v-model="consulta"
                                    type="text"
                                    class="w-full border-0 bg-transparent py-4 text-base text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-0"
                                    placeholder="Buscar pantalla o acción…"
                                    autocomplete="off"
                                    spellcheck="false"
                                />
                                <kbd class="hidden shrink-0 rounded border border-slate-200 bg-slate-50 px-1.5 py-0.5 text-[11px] font-semibold text-slate-500 sm:block">
                                    Esc
                                </kbd>
                            </div>

                            <!-- Resultados -->
                            <div class="max-h-[55vh] overflow-y-auto py-2">
                                <p v-if="!resultados.length" class="px-4 py-8 text-center text-sm text-slate-400">
                                    Nada coincide con «{{ consulta }}».
                                </p>

                                <div v-for="[grupo, items] in grupos" :key="grupo">
                                    <p class="px-4 pb-1 pt-2 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                                        {{ grupo }}
                                    </p>
                                    <button
                                        v-for="item in items"
                                        :key="item.href + item.label"
                                        type="button"
                                        class="flex w-full items-center gap-3 px-4 py-2.5 text-left transition-colors"
                                        :class="item.indice === seleccion ? 'bg-brand-50 text-brand-900' : 'text-slate-700 hover:bg-slate-50'"
                                        @click="ejecutar(item)"
                                        @mousemove="seleccion = item.indice"
                                    >
                                        <span
                                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg"
                                            :class="item.indice === seleccion ? 'bg-brand-100 text-brand-700' : 'bg-slate-100 text-slate-500'"
                                        >
                                            <DashIcon :name="item.icon ?? 'arrow'" class="h-4 w-4" />
                                        </span>
                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate text-sm font-semibold">{{ item.label }}</span>
                                            <span v-if="item.hint" class="block truncate text-xs text-slate-400">{{ item.hint }}</span>
                                        </span>
                                        <DashIcon
                                            v-if="item.indice === seleccion"
                                            name="arrow"
                                            class="h-4 w-4 shrink-0 text-brand-500"
                                        />
                                    </button>
                                </div>
                            </div>

                            <!-- Pie de ayuda -->
                            <div class="flex items-center gap-4 border-t border-slate-100 bg-slate-50 px-4 py-2 text-[11px] font-medium text-slate-400">
                                <span><kbd class="font-sans font-bold">↑ ↓</kbd> moverse</span>
                                <span><kbd class="font-sans font-bold">Enter</kbd> abrir</span>
                                <span class="ml-auto hidden sm:block"><kbd class="font-sans font-bold">Ctrl + K</kbd> en cualquier momento</span>
                            </div>
                        </div>
                    </Transition>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
