<script setup>
import { ref, onMounted } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'

const props = defineProps({
    token: { type: String, required: true },
    title: { type: String, required: true },
    member_name: { type: String, required: true },
    document_url: { type: String, default: null },
    legal_text: { type: String, default: null },
    status: { type: String, required: true },
    signed_at: { type: String, default: null },
})

const canvas = ref(null)
let ctx = null
let drawing = false
let hasDrawn = false

const form = useForm({
    signer_name: '',
    signature_image: '',
    accepted: false,
})

onMounted(() => {
    if (props.status === 'signed' || props.status === 'expired') return

    ctx = canvas.value.getContext('2d')
    ctx.strokeStyle = '#1e293b'
    ctx.lineWidth = 2
    ctx.lineCap = 'round'
})

function pointerPos(event) {
    const rect = canvas.value.getBoundingClientRect()
    const point = event.touches ? event.touches[0] : event
    return { x: point.clientX - rect.left, y: point.clientY - rect.top }
}

function startDrawing(event) {
    drawing = true
    hasDrawn = true
    const { x, y } = pointerPos(event)
    ctx.beginPath()
    ctx.moveTo(x, y)
}

function draw(event) {
    if (!drawing) return
    event.preventDefault()
    const { x, y } = pointerPos(event)
    ctx.lineTo(x, y)
    ctx.stroke()
}

function stopDrawing() {
    drawing = false
}

function clearSignature() {
    ctx.clearRect(0, 0, canvas.value.width, canvas.value.height)
    hasDrawn = false
}

function submit() {
    if (!hasDrawn) return
    form.signature_image = canvas.value.toDataURL('image/png')
    form.post(route('public.signature.sign', props.token), { preserveScroll: true })
}

function formatDate(iso) {
    if (!iso) return null
    return new Date(iso).toLocaleString('es-ES', { day: '2-digit', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}
</script>

<template>
    <Head title="Firma de documento" />

    <div class="flex min-h-screen items-center justify-center bg-ink-100 px-4 py-10">
        <div class="w-full max-w-lg rounded-lg bg-white p-6 shadow-md sm:p-8">
            <div class="mb-4 text-center">
                <span class="text-3xl">⚜️</span>
                <h1 class="mt-2 text-lg font-bold text-ink-800">MSC Andalucía</h1>
                <p class="text-sm text-ink-500">Firma digital de documento</p>
            </div>

            <div class="rounded-md border border-ink-200 bg-ink-50 p-4">
                <h2 class="font-semibold text-ink-800">{{ title }}</h2>
                <p class="text-sm text-ink-600">Participante: <strong>{{ member_name }}</strong></p>
                <a
                    v-if="document_url"
                    :href="document_url"
                    target="_blank"
                    rel="noopener"
                    class="mt-2 inline-block text-sm text-brand-700 underline"
                >
                    Ver documento
                </a>
                <p v-if="legal_text" class="mt-2 whitespace-pre-line text-sm text-ink-600">{{ legal_text }}</p>
            </div>

            <div v-if="status === 'signed'" class="mt-4 rounded-md bg-brand-50 p-3 text-sm text-brand-800">
                ✅ Ya has firmado este documento{{ signed_at ? ' el ' + formatDate(signed_at) : '' }}. Gracias.
            </div>

            <div v-else-if="status === 'expired'" class="mt-4 rounded-md bg-red-50 p-3 text-sm text-red-700">
                Este enlace de firma ha caducado. Ponte en contacto con la secretaría del grupo para que te
                envíen uno nuevo.
            </div>

            <div v-else-if="form.recentlySuccessful" class="mt-4 rounded-md bg-brand-50 p-3 text-sm text-brand-800">
                ✅ Documento firmado correctamente. Gracias.
            </div>

            <form v-else @submit.prevent="submit" class="mt-4 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-ink-700">Nombre completo del firmante</label>
                    <input
                        v-model="form.signer_name"
                        type="text"
                        required
                        class="mt-1 w-full rounded-md border-ink-300 text-sm"
                        placeholder="Nombre y apellidos"
                    />
                    <p v-if="form.errors.signer_name" class="mt-1 text-xs text-red-600">{{ form.errors.signer_name }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-ink-700">Firma</label>
                    <canvas
                        ref="canvas"
                        width="440"
                        height="160"
                        class="mt-1 w-full touch-none rounded-md border border-ink-300 bg-white"
                        @mousedown="startDrawing"
                        @mousemove="draw"
                        @mouseup="stopDrawing"
                        @mouseleave="stopDrawing"
                        @touchstart="startDrawing"
                        @touchmove="draw"
                        @touchend="stopDrawing"
                    ></canvas>
                    <button type="button" class="mt-1 text-xs text-ink-500 underline" @click="clearSignature">
                        Borrar y volver a firmar
                    </button>
                    <p v-if="form.errors.signature_image" class="mt-1 text-xs text-red-600">{{ form.errors.signature_image }}</p>
                </div>

                <label class="flex items-start gap-2 text-sm text-ink-700">
                    <input v-model="form.accepted" type="checkbox" class="mt-1" required />
                    <span>
                        Declaro ser el padre/madre/tutor legal de {{ member_name }} y presto mi conformidad
                        al contenido de este documento.
                    </span>
                </label>
                <p v-if="form.errors.accepted" class="text-xs text-red-600">{{ form.errors.accepted }}</p>

                <button
                    type="submit"
                    class="w-full rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700 disabled:opacity-50"
                    :disabled="form.processing"
                >
                    Firmar documento
                </button>
            </form>
        </div>
    </div>
</template>
