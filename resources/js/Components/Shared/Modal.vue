<script setup>
import { onMounted, onUnmounted, watch } from 'vue'

const props = defineProps({
    show: { type: Boolean, default: false },
    title: { type: String, default: null },
    maxWidth: { type: String, default: 'lg' }, // sm, md, lg, xl, 2xl
})

const emit = defineEmits(['close'])

const widths = {
    sm: 'sm:max-w-sm',
    md: 'sm:max-w-md',
    lg: 'sm:max-w-lg',
    xl: 'sm:max-w-xl',
    '2xl': 'sm:max-w-2xl',
}

const close = () => emit('close')

const onKeydown = (e) => {
    if (e.key === 'Escape' && props.show) close()
}

onMounted(() => document.addEventListener('keydown', onKeydown))
onUnmounted(() => document.removeEventListener('keydown', onKeydown))

watch(
    () => props.show,
    (v) => {
        document.body.style.overflow = v ? 'hidden' : null
    }
)
</script>

<template>
    <teleport to="body">
        <transition leave-active-class="duration-200">
            <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0">
                <transition
                    enter-active-class="ease-out duration-200"
                    enter-from-class="opacity-0"
                    enter-to-class="opacity-100"
                    leave-active-class="ease-in duration-150"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0"
                >
                    <div v-show="show" class="fixed inset-0 bg-slate-900/50" @click="close" />
                </transition>

                <transition
                    enter-active-class="ease-out duration-200"
                    enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                    leave-active-class="ease-in duration-150"
                    leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                    leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                >
                    <div
                        v-show="show"
                        class="relative mx-auto mt-12 w-full overflow-hidden rounded-lg bg-white shadow-xl"
                        :class="widths[maxWidth]"
                    >
                        <div v-if="title" class="border-b border-slate-100 px-6 py-4">
                            <h3 class="text-lg font-semibold text-slate-800">{{ title }}</h3>
                        </div>
                        <div class="px-6 py-4">
                            <slot />
                        </div>
                        <div v-if="$slots.footer" class="flex justify-end gap-2 border-t border-slate-100 bg-slate-50 px-6 py-3">
                            <slot name="footer" />
                        </div>
                    </div>
                </transition>
            </div>
        </transition>
    </teleport>
</template>
