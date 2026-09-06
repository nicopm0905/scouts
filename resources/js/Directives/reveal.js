/**
 * Directiva v-reveal: revela el elemento cuando entra en el viewport.
 *
 * Uso:
 *   <section v-reveal>                 → fundido + subida (por defecto)
 *   <li v-reveal="index * 70">         → mismo efecto con retardo en ms (cascada)
 *   <div v-reveal:scale>               → fundido + ligera escala
 *   <div v-reveal:left="120">          → entra desde la izquierda, con retardo
 *
 * Respeta `prefers-reduced-motion` y degrada a "visible" si el navegador no
 * soporta IntersectionObserver.
 */

const observers = new WeakMap()

// Curva suave con un punto de llegada firme (sin rebote).
const EASING = 'cubic-bezier(0.22, 1, 0.36, 1)'

const VARIANTS = {
    up: 'translate3d(0, 22px, 0)',
    down: 'translate3d(0, -22px, 0)',
    left: 'translate3d(-20px, 0, 0)',
    right: 'translate3d(20px, 0, 0)',
    scale: 'scale(0.96)',
    fade: 'none',
}

const prefersReducedMotion = () =>
    typeof window !== 'undefined'
    && typeof window.matchMedia === 'function'
    && window.matchMedia('(prefers-reduced-motion: reduce)').matches

const show = (el, delay) => {
    el.style.transitionDelay = `${delay}ms`
    el.style.opacity = '1'
    el.style.transform = 'none'
}

export default {
    mounted(el, binding) {
        if (typeof window === 'undefined' || prefersReducedMotion() || !('IntersectionObserver' in window)) {
            return
        }

        const delay = Number.isFinite(binding.value) ? Math.max(0, binding.value) : 0
        const from = VARIANTS[binding.arg] ?? VARIANTS.up
        const duration = binding.modifiers?.slow ? 900 : 650

        el.style.opacity = '0'
        if (from !== 'none') el.style.transform = from
        el.style.transition = `opacity ${duration}ms ${EASING}, transform ${duration}ms ${EASING}`
        el.style.willChange = 'opacity, transform'

        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (!entry.isIntersecting) return
                    show(el, delay)
                    // Libera la capa de composición cuando termina la animación.
                    window.setTimeout(() => {
                        el.style.willChange = 'auto'
                        el.style.transitionDelay = ''
                    }, delay + duration + 50)
                    observer.unobserve(el)
                })
            },
            { threshold: 0.12, rootMargin: '0px 0px -8% 0px' },
        )

        observer.observe(el)
        observers.set(el, observer)
    },

    unmounted(el) {
        observers.get(el)?.disconnect()
        observers.delete(el)
    },
}
