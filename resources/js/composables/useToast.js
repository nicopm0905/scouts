import { reactive } from 'vue'

// Estado global de toasts (singleton entre componentes).
const state = reactive({
    toasts: [],
})

let counter = 0

function push(message, type = 'success', options = {}) {
    const id = ++counter
    state.toasts.push({
        id,
        message,
        type, // success | error | info
        undo: options.undo ?? null, // callback opcional "deshacer"
    })

    const duration = options.duration ?? (options.undo ? 6000 : 3500)
    if (duration > 0) {
        setTimeout(() => remove(id), duration)
    }

    return id
}

function remove(id) {
    const i = state.toasts.findIndex((t) => t.id === id)
    if (i !== -1) state.toasts.splice(i, 1)
}

/**
 * Composable de notificaciones. Uso:
 *   const toast = useToast()
 *   toast.success('Guardado')
 *   toast.error('Algo falló')
 *   toast.success('Miembro eliminado', { undo: () => router.post(...) })
 */
export function useToast() {
    return {
        toasts: state.toasts,
        success: (msg, opts) => push(msg, 'success', opts),
        error: (msg, opts) => push(msg, 'error', opts),
        info: (msg, opts) => push(msg, 'info', opts),
        remove,
    }
}
