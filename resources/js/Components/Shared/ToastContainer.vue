<script setup>
import { watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useToast } from '@/composables/useToast'

const toast = useToast()
const page = usePage()

// Convierte los mensajes flash del backend en toasts.
watch(
    () => page.props.flash,
    (flash) => {
        if (!flash) return
        if (flash.success) toast.success(flash.success)
        if (flash.error) toast.error(flash.error)
        if (flash.info) toast.info(flash.info)
    },
    { immediate: true, deep: true }
)

const styles = {
    success: 'bg-emerald-600',
    error: 'bg-brand-600',
    info: 'bg-ink-700',
}
</script>

<template>
    <div class="pointer-events-none fixed inset-x-0 bottom-0 z-50 flex flex-col items-center gap-2 p-4 sm:items-end">
        <transition-group name="toast">
            <div
                v-for="t in toast.toasts"
                :key="t.id"
                class="pointer-events-auto flex w-full max-w-sm items-center justify-between gap-3 rounded-lg px-4 py-3 text-sm text-white shadow-lg"
                :class="styles[t.type]"
                role="status"
            >
                <span>{{ t.message }}</span>
                <button
                    v-if="t.undo"
                    class="shrink-0 rounded bg-white/20 px-2 py-1 text-xs font-semibold uppercase tracking-wide hover:bg-white/30"
                    @click="t.undo(); toast.remove(t.id)"
                >
                    Deshacer
                </button>
                <button
                    v-else
                    class="shrink-0 text-white/70 hover:text-white"
                    @click="toast.remove(t.id)"
                    aria-label="Cerrar"
                >
                    ✕
                </button>
            </div>
        </transition-group>
    </div>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
    transition: all 0.25s ease;
}
.toast-enter-from,
.toast-leave-to {
    opacity: 0;
    transform: translateY(10px);
}
</style>
