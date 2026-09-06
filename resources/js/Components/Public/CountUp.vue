<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'

/**
 * Cifra que cuenta hacia arriba la primera vez que entra en pantalla.
 * Acepta valores como "120", "50+" o "1.200": anima solo la parte numérica y
 * conserva el sufijo. Si el sistema pide menos movimiento, muestra el valor final.
 */
const props = defineProps({
    value: { type: String, required: true },
    duration: { type: Number, default: 1400 },
})

const el = ref(null)
const mostrado = ref('')

const partes = computed(() => {
    const match = String(props.value).match(/^(\D*)([\d.,]+)(.*)$/)
    if (!match) return null

    const [, prefijo, numero, sufijo] = match
    const limpio = numero.replace(/[.,]/g, '')

    return { prefijo, sufijo, objetivo: Number(limpio), separadores: numero.length !== limpio.length }
})

const formatear = (n) => {
    const p = partes.value
    if (!p) return props.value
    const numero = p.separadores ? n.toLocaleString('es-ES') : String(n)
    return `${p.prefijo}${numero}${p.sufijo}`
}

let raf = null
let observer = null

const animar = () => {
    const p = partes.value
    if (!p) return

    const inicio = performance.now()
    const paso = (ahora) => {
        const t = Math.min(1, (ahora - inicio) / props.duration)
        // easeOutExpo: arranca rápido y frena con suavidad.
        const eased = t === 1 ? 1 : 1 - Math.pow(2, -10 * t)
        mostrado.value = formatear(Math.round(p.objetivo * eased))
        if (t < 1) raf = requestAnimationFrame(paso)
    }

    raf = requestAnimationFrame(paso)
}

onMounted(() => {
    const sinMovimiento = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches
    const p = partes.value

    if (!p || sinMovimiento || !('IntersectionObserver' in window)) {
        mostrado.value = props.value
        return
    }

    mostrado.value = formatear(0)

    observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return
                animar()
                observer.unobserve(entry.target)
            })
        },
        { threshold: 0.5 },
    )

    if (el.value) observer.observe(el.value)
})

onBeforeUnmount(() => {
    if (raf) cancelAnimationFrame(raf)
    observer?.disconnect()
})
</script>

<template>
    <span ref="el" class="tabular-nums">{{ mostrado || value }}</span>
</template>
