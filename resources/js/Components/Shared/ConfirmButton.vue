<script setup>
import { ref } from 'vue'
import Modal from '@/Components/Shared/Modal.vue'

const props = defineProps({
    title: { type: String, default: '¿Confirmar acción?' },
    message: { type: String, default: 'Esta acción no se puede deshacer.' },
    confirmLabel: { type: String, default: 'Confirmar' },
    variant: { type: String, default: 'danger' }, // danger | primary
})

const emit = defineEmits(['confirm'])
const open = ref(false)

const confirm = () => {
    emit('confirm')
    open.value = false
}

const btn =
    'inline-flex items-center rounded-md px-4 py-2 text-sm font-semibold text-white'
</script>

<template>
    <span>
        <button type="button" @click="open = true">
            <slot>
                <span :class="[btn, variant === 'danger' ? 'bg-brand-600 hover:bg-brand-700' : 'bg-brand-600 hover:bg-brand-700']">
                    {{ confirmLabel }}
                </span>
            </slot>
        </button>

        <Modal :show="open" :title="title" max-width="sm" @close="open = false">
            <p class="text-sm text-slate-600">{{ message }}</p>
            <template #footer>
                <button
                    type="button"
                    class="rounded-md px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100"
                    @click="open = false"
                >
                    Cancelar
                </button>
                <button
                    type="button"
                    :class="[btn, variant === 'danger' ? 'bg-brand-600 hover:bg-brand-700' : 'bg-brand-600 hover:bg-brand-700']"
                    @click="confirm"
                >
                    {{ confirmLabel }}
                </button>
            </template>
        </Modal>
    </span>
</template>
