<script setup>
import { ref, onMounted } from 'vue'
import { Head, useForm, router } from '@inertiajs/vue3'

const props = defineProps({
    token: { type: String, required: true },
    event: { type: Object, required: true },
    member_name: { type: String, required: true },
    enrolled: { type: Boolean, required: true },
    confirmed_at: { type: String, default: null },
    has_authorization: { type: Boolean, default: false },
    signature_data: { type: String, default: null },
    medical_consent: { type: Boolean, default: true },
    image_consent: { type: Boolean, default: true },
    family_name: { type: String, default: '' },
    family_dni: { type: String, default: '' },
    address: { type: String, default: '' },
    contact_phone: { type: String, default: '' },
    health_summary: { type: String, default: '' },
    declined: { type: Boolean, default: false },
})

const confirming = ref(false)
const submittedSuccess = ref(false)
const declinedSuccess = ref(props.declined)
const showSuccessModal = ref(false)

const medicalConsent = ref(props.medical_consent)
const imageConsent = ref(props.image_consent)
const familyName = ref(props.family_name)
const familyDni = ref(props.family_dni)
const address = ref(props.address)
const contactPhone = ref(props.contact_phone)
const healthSummary = ref(props.health_summary)

const canvasRef = ref(null)
const isDrawing = ref(false)
const hasSigned = ref(!!props.signature_data)
let ctx = null

onMounted(() => {
    if (canvasRef.value) {
        ctx = canvasRef.value.getContext('2d')
        ctx.lineWidth = 2.5
        ctx.lineCap = 'round'
        ctx.lineJoin = 'round'
        ctx.strokeStyle = '#0f172a'
    }
})

function getPos(e) {
    const rect = canvasRef.value.getBoundingClientRect()
    const clientX = e.touches ? e.touches[0].clientX : e.clientX
    const clientY = e.touches ? e.touches[0].clientY : e.clientY
    return {
        x: clientX - rect.left,
        y: clientY - rect.top,
    }
}

function startDrawing(e) {
    isDrawing.value = true
    const { x, y } = getPos(e)
    ctx.beginPath()
    ctx.moveTo(x, y)
}

function draw(e) {
    if (!isDrawing.value) return
    e.preventDefault()
    const { x, y } = getPos(e)
    ctx.lineTo(x, y)
    ctx.stroke()
    hasSigned.value = true
}

function stopDrawing() {
    isDrawing.value = false
}

function clearSignature() {
    if (canvasRef.value && ctx) {
        ctx.clearRect(0, 0, canvasRef.value.width, canvasRef.value.height)
    }
    hasSigned.value = false
}

function confirmEnrollment() {
    confirming.value = true
    let sigData = props.signature_data
    if (canvasRef.value && hasSigned.value) {
        sigData = canvasRef.value.toDataURL('image/png')
    }

    router.post(route('public.enrollment.confirm', props.token), {
        signature_data: sigData,
        medical_consent: medicalConsent.value,
        image_consent: imageConsent.value,
        family_name: familyName.value,
        family_dni: familyDni.value,
        address: address.value,
        contact_phone: contactPhone.value,
        health_summary: healthSummary.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            submittedSuccess.value = true
            showSuccessModal.value = true
        },
        onFinish: () => { confirming.value = false },
    })
}

const declining = ref(false)
const declineReason = ref('')
function declineEnrollment() {
    if (!confirm('¿Seguro que quieres indicar que no asistirá a la actividad?')) return
    declining.value = true
    router.post(route('public.enrollment.decline', props.token), {
        reason: declineReason.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            declinedSuccess.value = true
        },
        onFinish: () => { declining.value = false },
    })
}

const uploadForm = useForm({ file: null })

function submitUpload() {
    uploadForm.post(route('public.enrollment.upload', props.token), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            submittedSuccess.value = true
            showSuccessModal.value = true
        },
    })
}

function formatDate(iso) {
    if (!iso) return null
    return new Date(iso).toLocaleString('es-ES', { day: '2-digit', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}
</script>

<template>
    <Head :title="`Autorización - ${event.title}`" />

    <div class="min-h-screen bg-slate-100 flex flex-col justify-center items-center p-4 sm:p-6 font-sans">
        <div class="w-full max-w-lg rounded-3xl border border-slate-200/80 bg-white p-6 sm:p-8 shadow-xl space-y-6">
            <!-- Encabezado con Logo del Grupo -->
            <div class="text-center border-b border-slate-100 pb-5">
                <div class="inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-50 p-2 shadow-xs ring-1 ring-emerald-200">
                    <img src="/images/logosj.png" alt="Scouts de San José" class="h-12 w-12 object-contain bg-white rounded-md p-1" />
                </div>
                <h1 class="mt-3 text-xl font-black text-slate-900 tracking-tight">Scouts de San José</h1>
                <p class="text-xs font-bold uppercase tracking-wider text-emerald-800 mt-0.5">Portal de Familias y Autorizaciones</p>
            </div>

            <!-- Pantalla de Éxito / Confirmación Proceso Completado -->
            <div v-if="(has_authorization || submittedSuccess) && !declinedSuccess" class="rounded-3xl border-2 border-emerald-400 bg-gradient-to-br from-emerald-50 to-teal-50 p-6 text-center space-y-3 shadow-sm">
                <div class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-600 text-white font-black text-2xl shadow-md">
                    ✓
                </div>
                <h3 class="text-lg font-black text-emerald-950">¡Autorización Firmada y Registrada!</h3>
                <p class="text-xs font-semibold text-emerald-800 leading-relaxed">
                    Muchas gracias, familia. La autorización para <strong>{{ member_name }}</strong> ha sido registrada y firmada correctamente para {{ event.title }}.
                </p>

                <div class="pt-2">
                    <a
                        :href="route('public.enrollment.pdf', token)"
                        target="_blank"
                        class="w-full inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-700 py-3 px-4 text-xs font-bold text-white shadow-md hover:bg-emerald-800 transition"
                    >
                        <span>📄 Descargar mi Autorización Firmada (PDF)</span>
                    </a>
                </div>
            </div>

            <!-- Pantalla de Éxito / Decline -->
            <div v-if="declinedSuccess" class="rounded-3xl border-2 border-rose-400 bg-gradient-to-br from-rose-50 to-red-50 p-6 text-center space-y-3 shadow-sm">
                <div class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-rose-600 text-white font-black text-2xl shadow-md">
                    ✓
                </div>
                <h3 class="text-lg font-black text-rose-950">Aviso Recibido</h3>
                <p class="text-xs font-semibold text-rose-800 leading-relaxed">
                    Muchas gracias por avisar de que <strong>{{ member_name }}</strong> no asistirá a la actividad. ¡Lo tendremos en cuenta para organizarnos!
                </p>
            </div>

            <!-- Contenido de la Ficha y Formulario (Solo si no se ha completado/declinado) -->
            <div v-if="!has_authorization && !submittedSuccess && !declinedSuccess" class="space-y-6">
                <!-- Ficha de la Actividad -->
                <div class="rounded-2xl border border-emerald-200/80 bg-gradient-to-br from-emerald-50/60 to-teal-50/30 p-5 space-y-3">
                    <div class="flex items-center justify-between">
                    <span class="rounded-full bg-emerald-700 px-3 py-1 text-xs font-bold text-white shadow-xs">
                        {{ event.type_label || 'Actividad Scout' }}
                    </span>
                    <span class="text-xs font-bold text-slate-500">⚜️ MSC</span>
                </div>

                <h2 class="text-lg font-black text-slate-900 leading-snug">{{ event.title }}</h2>

                <div class="space-y-1.5 text-xs text-slate-700 font-medium">
                    <p class="flex items-center gap-1.5">
                        <span>📅</span>
                        <span><strong>Fechas:</strong> {{ formatDate(event.start_at) }}<span v-if="event.end_at"> — {{ formatDate(event.end_at) }}</span></span>
                    </p>
                    <p v-if="event.location" class="flex items-center gap-1.5">
                        <span>📍</span>
                        <span><strong>Lugar:</strong> {{ event.location }}</span>
                    </p>
                </div>

                <p v-if="event.description" class="whitespace-pre-line text-xs text-slate-600 border-t border-emerald-200/60 pt-2.5 leading-relaxed">
                    {{ event.description }}
                </p>
            </div>

            <!-- Participante y PDF Oficial -->
            <div class="rounded-2xl border border-slate-200/80 bg-slate-50/80 p-4 space-y-3">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400">Educando / Participante</span>
                        <span class="text-base font-black text-slate-900">{{ member_name }}</span>
                    </div>
                    <div class="h-10 w-10 rounded-xl bg-slate-200/80 flex items-center justify-center text-slate-600 font-bold">
                        ⚜️
                    </div>
                </div>
                
                <a
                    :href="route('public.enrollment.pdf', token)"
                    target="_blank"
                    class="w-full flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-100 transition shadow-xs"
                >
                    <span>📄 Previsualizar PDF Oficial de Autorización</span>
                </a>
            </div>

            <!-- Formulario de Datos del Tutor y Salud del Educando -->
            <div class="space-y-3 pt-2">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">Paso 1: Datos del Tutor Legal y Domicilio</h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Padre / Madre / Tutor Legal</label>
                        <input
                            type="text"
                            v-model="familyName"
                            placeholder="Nombre y Apellidos"
                            class="w-full rounded-xl border-slate-300 text-xs text-slate-900 focus:border-emerald-500 focus:ring-emerald-500"
                        />
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">D.N.I / N.I.E del Tutor</label>
                        <input
                            type="text"
                            v-model="familyDni"
                            placeholder="Ej: 12345678Z"
                            class="w-full rounded-xl border-slate-300 text-xs text-slate-900 focus:border-emerald-500 focus:ring-emerald-500"
                        />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Domicilio Habitual / Dirección</label>
                        <input
                            type="text"
                            v-model="address"
                            placeholder="Ej: C/ Porvera 21, Jerez"
                            class="w-full rounded-xl border-slate-300 text-xs text-slate-900 focus:border-emerald-500 focus:ring-emerald-500"
                        />
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Teléfono de Urgencias</label>
                        <input
                            type="text"
                            v-model="contactPhone"
                            placeholder="Ej: 611223344"
                            class="w-full rounded-xl border-slate-300 text-xs text-slate-900 focus:border-emerald-500 focus:ring-emerald-500"
                        />
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Atenciones Especiales / Alergias / Medicación</label>
                    <textarea
                        v-model="healthSummary"
                        rows="2"
                        placeholder="Indicar si padece alergias, intolerancias o precisa toma de medicamentos durante la actividad..."
                        class="w-full rounded-xl border-slate-300 text-xs text-slate-900 focus:border-emerald-500 focus:ring-emerald-500"
                    />
                </div>
            </div>

            <!-- Casillas de Autorización Interactivas -->
            <div class="space-y-3 pt-2">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">Paso 2: Declaración y Consentimientos</h3>

                <label class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50/50 p-4 cursor-pointer hover:bg-slate-100/60 transition">
                    <input
                        type="checkbox"
                        v-model="medicalConsent"
                        class="mt-0.5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 h-5 w-5"
                    />
                    <div class="text-xs leading-relaxed text-slate-700">
                        <strong class="text-slate-900 font-bold block mb-0.5">Autorización Médica de Urgencia</strong>
                        Autorizo a los responsables a tomar las decisiones médicas o quirúrgicas urgentes en caso de accidente o enfermedad.
                    </div>
                </label>

                <label class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50/50 p-4 cursor-pointer hover:bg-slate-100/60 transition">
                    <input
                        type="checkbox"
                        v-model="imageConsent"
                        class="mt-0.5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 h-5 w-5"
                    />
                    <div class="text-xs leading-relaxed text-slate-700">
                        <strong class="text-slate-900 font-bold block mb-0.5">Cesión de Derechos de Imagen</strong>
                        Autorizo al Grupo Scout San José y Federación MSC a la fijación y difusión de fotografías/vídeos de la actividad.
                    </div>
                </label>
            </div>

            <!-- Canvas de Firma Digital Táctil -->
            <div class="space-y-2 pt-2">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">Paso 3: Firma Táctil del Padre / Madre / Tutor</h3>
                    <button
                        type="button"
                        class="text-[11px] font-bold text-slate-500 hover:text-rose-600 underline"
                        @click="clearSignature"
                    >
                        🗑️ Limpiar firma
                    </button>
                </div>

                <div class="rounded-2xl border-2 border-slate-300 bg-white p-2 shadow-inner">
                    <canvas
                        ref="canvasRef"
                        width="440"
                        height="140"
                        class="w-full touch-none bg-slate-50/30 rounded-xl border border-slate-100 cursor-crosshair"
                        @mousedown="startDrawing"
                        @mousemove="draw"
                        @mouseup="stopDrawing"
                        @mouseleave="stopDrawing"
                        @touchstart="startDrawing"
                        @touchmove="draw"
                        @touchend="stopDrawing"
                    />
                </div>
                <p class="text-[11px] text-center text-slate-400 font-medium">Firma directamente dentro del recuadro usando tu dedo en el móvil o el ratón</p>
            </div>

            <!-- Botón de Envío / Confirmación -->
            <div class="pt-2">
                <button
                    type="button"
                    class="w-full flex items-center justify-center gap-2 rounded-2xl bg-emerald-600 py-3.5 text-sm font-bold text-white hover:bg-emerald-700 transition shadow-md disabled:opacity-50"
                    :disabled="confirming"
                    @click="confirmEnrollment"
                >
                    <span>{{ confirming ? 'Guardando firma y datos...' : (enrolled ? '✅ Actualizar Autorización Firmada' : '✒️ Firmar y Confirmar Autorización') }}</span>
                </button>
            </div>

            <!-- Alternativa de Subir PDF o Foto escaneada -->
            <div class="space-y-3 pt-4 border-t border-slate-100">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">Opción Alternativa: Adjuntar Documento Escaneado</h3>

                <div v-if="has_authorization && !signature_data" class="rounded-2xl border border-emerald-300 bg-emerald-50 p-4 text-center space-y-1">
                    <p class="text-sm font-bold text-emerald-900">✅ Autorización Escaneada Almacenada</p>
                </div>

                <form v-else @submit.prevent="submitUpload" class="space-y-3">
                    <div class="rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50/50 p-4 text-center hover:border-emerald-400 transition">
                        <label class="block cursor-pointer">
                            <span class="block text-2xl mb-1">📸 📄</span>
                            <span class="block text-xs font-bold text-slate-700">Seleccionar Foto o PDF de la Autorización en Papel</span>
                            <input
                                type="file"
                                accept=".pdf,.jpg,.jpeg,.png"
                                class="hidden"
                                @change="uploadForm.file = $event.target.files[0]"
                            />
                        </label>
                        <p v-if="uploadForm.file" class="mt-2 text-xs font-bold text-emerald-800">
                            📎 {{ uploadForm.file.name }}
                        </p>
                    </div>

                    <button
                        type="submit"
                        class="w-full flex items-center justify-center gap-2 rounded-2xl border border-slate-300 bg-white py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-50 transition disabled:opacity-50"
                        :disabled="uploadForm.processing || !uploadForm.file"
                    >
                        <span>{{ uploadForm.processing ? 'Subiendo...' : '📤 Subir Documento en Papel' }}</span>
                    </button>
                </form>
            </div>
            <!-- Opción: No Asiste -->
            <div v-if="!has_authorization && !submittedSuccess" class="space-y-3 pt-4 border-t border-slate-100">
                <h3 class="text-xs font-bold uppercase tracking-wider text-rose-500">¿No asiste a la actividad?</h3>
                <div class="rounded-2xl border border-rose-100 bg-rose-50/50 p-4 space-y-3">
                    <p class="text-xs text-rose-800 font-medium leading-relaxed">Si {{ member_name }} no va a poder asistir, por favor avísanos para que podamos organizarnos mejor.</p>
                    <input
                        type="text"
                        v-model="declineReason"
                        placeholder="Motivo (Opcional)"
                        class="w-full rounded-xl border-rose-200 text-xs text-rose-900 focus:border-rose-500 focus:ring-rose-500 bg-white"
                    />
                    <button
                        type="button"
                        class="w-full flex items-center justify-center gap-2 rounded-xl border border-rose-300 bg-white py-2.5 text-xs font-bold text-rose-700 hover:bg-rose-100 transition shadow-sm disabled:opacity-50"
                        :disabled="declining"
                        @click="declineEnrollment"
                    >
                        <span>{{ declining ? 'Enviando aviso...' : '❌ Avisar de que NO asistirá' }}</span>
                    </button>
                </div>
            </div>
            
            </div> <!-- Cierre del envoltorio del formulario -->

        </div>

        <!-- Modal Elegante y Minimalista de Confirmación de Firma -->
        <Teleport to="body">
            <Transition
                enter-active-class="ease-out duration-300"
                enter-from-class="opacity-0 scale-95"
                enter-to-class="opacity-100 scale-100"
                leave-active-class="ease-in duration-200"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-95"
            >
                <div v-if="showSuccessModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
                    <div class="w-full max-w-md transform overflow-hidden rounded-3xl bg-white p-6 sm:p-8 text-center shadow-2xl transition-all border border-emerald-100">
                        <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-3xl bg-emerald-100 text-emerald-600 shadow-inner">
                            <span class="text-4xl animate-bounce">✅</span>
                        </div>

                        <h3 class="mt-5 text-xl font-black text-slate-900 tracking-tight">¡Autorización Firmada!</h3>
                        
                        <p class="mt-2 text-xs text-slate-600 font-medium leading-relaxed">
                            Se ha registrado y firmado correctamente el documento oficial de autorización para <strong class="text-slate-900">{{ member_name }}</strong> en la actividad <strong>{{ event.title }}</strong>.
                        </p>

                        <div class="mt-6 space-y-2.5">
                            <a
                                :href="route('public.enrollment.pdf', token)"
                                target="_blank"
                                class="w-full flex items-center justify-center gap-2 rounded-2xl bg-emerald-600 py-3.5 px-4 text-xs font-bold text-white shadow-md hover:bg-emerald-700 transition"
                            >
                                <span>📄 Ver / Descargar Documento PDF Firmado</span>
                            </a>

                            <button
                                type="button"
                                class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-3 px-4 text-xs font-bold text-slate-700 hover:bg-slate-100 transition"
                                @click="showSuccessModal = false"
                            >
                                Entendido, cerrar
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <p class="mt-6 text-center text-xs font-medium text-slate-500">
            Grupo Scout Scouts de San José · MSC Andalucía
        </p>
    </div>
</template>
