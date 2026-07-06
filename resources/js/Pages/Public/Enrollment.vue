<script setup>
import { ref } from 'vue'
import { Head, useForm, router } from '@inertiajs/vue3'

const props = defineProps({
    token: { type: String, required: true },
    event: { type: Object, required: true },
    member_name: { type: String, required: true },
    enrolled: { type: Boolean, required: true },
    confirmed_at: { type: String, default: null },
    has_authorization: { type: Boolean, default: false },
})

const confirming = ref(false)

function confirmEnrollment() {
    confirming.value = true
    router.post(route('public.enrollment.confirm', props.token), {}, {
        preserveScroll: true,
        onFinish: () => { confirming.value = false },
    })
}

const uploadForm = useForm({ file: null })

function submitUpload() {
    uploadForm.post(route('public.enrollment.upload', props.token), {
        preserveScroll: true,
        forceFormData: true,
    })
}

function formatDate(iso) {
    if (!iso) return null
    return new Date(iso).toLocaleString('es-ES', { day: '2-digit', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}
</script>

<template>
    <Head title="Confirmación de inscripción" />

    <div class="flex min-h-screen items-center justify-center bg-slate-100 px-4 py-10">
        <div class="w-full max-w-lg rounded-lg bg-white p-6 shadow-md sm:p-8">
            <div class="mb-4 text-center">
                <span class="text-3xl">⚜️</span>
                <h1 class="mt-2 text-lg font-bold text-slate-800">MSC Andalucía</h1>
                <p class="text-sm text-slate-500">Confirmación de inscripción</p>
            </div>

            <div class="rounded-md border border-slate-200 bg-slate-50 p-4">
                <h2 class="font-semibold text-slate-800">{{ event.title }}</h2>
                <p class="text-sm text-slate-500">{{ event.type_label }}</p>
                <p class="mt-2 text-sm text-slate-600">
                    <strong>Fechas:</strong> {{ formatDate(event.start_at) }}
                    <span v-if="event.end_at"> — {{ formatDate(event.end_at) }}</span>
                </p>
                <p v-if="event.location" class="text-sm text-slate-600"><strong>Lugar:</strong> {{ event.location }}</p>
                <p v-if="event.description" class="mt-2 whitespace-pre-line text-sm text-slate-600">{{ event.description }}</p>
            </div>

            <p class="mt-4 text-sm text-slate-700">
                Participante: <strong>{{ member_name }}</strong>
            </p>

            <div class="mt-4">
                <div v-if="enrolled" class="rounded-md bg-emerald-50 p-3 text-sm text-emerald-800">
                    ✅ Inscripción confirmada{{ confirmed_at ? ' el ' + formatDate(confirmed_at) : '' }}.
                </div>
                <button
                    v-else
                    type="button"
                    class="w-full rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-50"
                    :disabled="confirming"
                    @click="confirmEnrollment"
                >
                    Confirmar inscripción
                </button>
            </div>

            <div class="mt-6 border-t border-slate-100 pt-4">
                <h3 class="text-sm font-semibold text-slate-700">Autorización firmada</h3>
                <p v-if="has_authorization" class="mt-1 text-sm text-emerald-700">
                    ✅ Ya hemos recibido la autorización firmada. Gracias.
                </p>
                <form v-else @submit.prevent="submitUpload" class="mt-2 space-y-2">
                    <input
                        type="file"
                        accept=".pdf,.jpg,.jpeg,.png"
                        class="block w-full text-sm text-slate-600"
                        @change="uploadForm.file = $event.target.files[0]"
                    />
                    <p v-if="uploadForm.errors.file" class="text-xs text-red-600">{{ uploadForm.errors.file }}</p>
                    <button
                        type="submit"
                        class="w-full rounded-md border border-emerald-600 px-4 py-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-50 disabled:opacity-50"
                        :disabled="uploadForm.processing || !uploadForm.file"
                    >
                        Subir autorización (PDF o foto)
                    </button>
                </form>
            </div>
        </div>
    </div>
</template>
