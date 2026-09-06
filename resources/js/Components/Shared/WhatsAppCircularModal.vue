<script setup>
import { ref, computed } from 'vue'
import Modal from '@/Components/Shared/Modal.vue'

const props = defineProps({
    show: { type: Boolean, default: false },
    defaultBranch: { type: String, default: 'castor' },
    members: { type: Array, default: () => [] }, // Miembros con teléfonos de tutores si se pasan
})

const emit = defineEmits(['close'])

const selectedTemplate = ref('campamento')
const targetBranch = ref(props.defaultBranch || 'castor')
const eventTitle = ref('Campamento de Primavera')
const location = ref('Cabañas del Valle')
const dateRange = ref('Del 14 al 16 de Marzo')
const meetingPoint = ref('Local Scout a las 09:00h')
const amount = ref('35 €')
const customNotes = ref('')

const templates = [
    { id: 'campamento', label: '🏕️ Aviso de Campamento / Salida', icon: '🏕️' },
    { id: 'cuota', label: '💶 Recordatorio de Pago / Cuota', icon: '💶' },
    { id: 'autorizacion', label: '📑 Autorizaciones y Ficha Médica', icon: '📑' },
    { id: 'personalizado', label: '✏️ Mensaje Personalizado', icon: '✏️' },
]

const branchNames = {
    castor: 'Castores 🦫',
    lobato: 'Lobatos 🐺',
    ranger: 'Ranger ⚜️',
    pionero: 'Pioneros 🔥',
    ruta: 'Rutas 🧭',
    responsable: 'Responsables 🩵',
}

const generatedMessage = computed(() => {
    const branchLabel = branchNames[targetBranch.value] || 'Grupo Scout'
    
    if (selectedTemplate.value === 'campamento') {
        return `⚜️ *SCOUTS DE SAN JOSÉ* ⚜️\n📢 *INFORMACIÓN DE CAMPAMENTO - ${branchLabel.toUpperCase()}*\n\nHola familias! Os compartimos la información para la próxima actividad:\n\n📌 *Actividad:* ${eventTitle.value}\n📍 *Lugar:* ${location.value}\n📅 *Fechas:* ${dateRange.value}\n⏰ *Salida y Encuentro:* ${meetingPoint.value}\n\n🎒 *¿QUÉ LLEVAR EN LA MOCHILA?*\n- Pañoleta y uniforme de grupo\n- Cantimplora llena y gorra\n- Mochila de día con comida y merienda para el sábado\n- Saco de dormir, esterilla y linterna\n- Impermeable / ropa de abrigo\n- Neceser y plato/cubiertos de campamento\n\n${customNotes.value ? `💡 *Notas adicionales:* ${customNotes.value}\n\n` : ''}Cualquier duda, podéis consultarnos por el grupo. Buena Caza! ⚜️`
    }

    if (selectedTemplate.value === 'cuota') {
        return `⚜️ *SCOUTS DE SAN JOSÉ* ⚜️\n💶 *RECORDATORIO DE TESORERÍA - ${branchLabel.toUpperCase()}*\n\nHola familias! Recordamos que está pendiente el pago correspondiente a:\n\n📌 *Concepto:* ${eventTitle.value}\n💰 *Importe:* ${amount.value}\n📅 *Fecha Límite:* ${dateRange.value}\n\n${customNotes.value ? `💡 *Indicaciones:* ${customNotes.value}\n\n` : ''}Por favor, confirmadnos cuando hayáis realizado el pago. Muchas gracias por la colaboración! ⚜️`
    }

    if (selectedTemplate.value === 'autorizacion') {
        return `⚜️ *SCOUTS DE SAN JOSÉ* ⚜️\n📑 *RECORDATORIO DE DOCUMENTACIÓN*\n\nEstimadas familias de ${branchLabel}:\n\nRecordamos la necesidad de entregar firmada la autorización y ficha de salud para la actividad:\n📌 *Actividad:* ${eventTitle.value}\n⏰ *Fecha límite de entrega:* ${dateRange.value}\n\nPodéis descargar la autorización desde la web del grupo o solicitarla a los responsables.\n\nBuena Caza! ⚜️`
    }

    return `⚜️ *SCOUTS DE SAN JOSÉ* ⚜️\n📢 *AVISO DE RAMA (${branchLabel.toUpperCase()})*\n\n${customNotes.value || 'Mensaje para las familias...'}\n\nBuena Caza! ⚜️`
})

const copied = ref(false)
function copyToClipboard() {
    navigator.clipboard?.writeText(generatedMessage.value)
    copied.value = true
    setTimeout(() => { copied.value = false }, 2500)
}

function openWhatsAppWeb() {
    const encoded = encodeURIComponent(generatedMessage.value)
    window.open(`https://web.whatsapp.com/send?text=${encoded}`, '_blank')
}

function openWhatsAppUrl(phone) {
    if (!phone) return '#'
    const cleanPhone = phone.replace(/\D/g, '')
    const fullPhone = cleanPhone.length === 9 ? `34${cleanPhone}` : cleanPhone
    const encoded = encodeURIComponent(generatedMessage.value)
    return `https://wa.me/${fullPhone}?text=${encoded}`
}
</script>

<template>
    <Modal :show="show" max-width="3xl" @close="emit('close')">
        <div class="p-6 space-y-6">
            <!-- Encabezado -->
            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-800 text-lg">
                        💬
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">Generador de Mensajes WhatsApp</h3>
                        <p class="text-xs font-medium text-slate-500">Crea avisos formateados para los grupos de WhatsApp del grupo</p>
                    </div>
                </div>
                <button class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600" @click="emit('close')">✕</button>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <!-- Columna Izquierda: Configuración -->
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">Tipo de Mensaje</label>
                        <div class="grid grid-cols-1 gap-1.5">
                            <button
                                v-for="t in templates"
                                :key="t.id"
                                type="button"
                                class="flex items-center gap-2.5 rounded-xl border p-2.5 text-left text-xs font-bold transition"
                                :class="selectedTemplate === t.id ? 'border-emerald-500 bg-emerald-50/80 text-emerald-900 ring-1 ring-emerald-300' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'"
                                @click="selectedTemplate = t.id"
                            >
                                <span>{{ t.icon }}</span>
                                <span>{{ t.label }}</span>
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">Rama Destinataria</label>
                        <select v-model="targetBranch" class="w-full rounded-xl border-slate-200 text-sm font-semibold focus:border-emerald-500 focus:ring-emerald-500">
                            <option value="castor">Castores 🦫</option>
                            <option value="lobato">Lobatos 🐺</option>
                            <option value="ranger">Ranger ⚜️</option>
                            <option value="pionero">Pioneros 🔥</option>
                            <option value="ruta">Rutas 🧭</option>
                            <option value="responsable">Responsables 🩵</option>
                        </select>
                    </div>

                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Título de la Actividad / Concepto</label>
                            <input v-model="eventTitle" type="text" class="w-full rounded-xl border-slate-200 text-xs font-semibold" placeholder="Ej. Acampada de Primavera" />
                        </div>

                        <div v-if="selectedTemplate === 'campamento'">
                            <label class="block text-xs font-bold text-slate-700 mb-1">Lugar</label>
                            <input v-model="location" type="text" class="w-full rounded-xl border-slate-200 text-xs font-semibold" placeholder="Ej. Cabañas del Valle" />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">{{ selectedTemplate === 'cuota' ? 'Fecha Límite' : 'Fechas' }}</label>
                            <input v-model="dateRange" type="text" class="w-full rounded-xl border-slate-200 text-xs font-semibold" placeholder="Ej. Del 14 al 16 de Marzo" />
                        </div>

                        <div v-if="selectedTemplate === 'cuota'">
                            <label class="block text-xs font-bold text-slate-700 mb-1">Importe</label>
                            <input v-model="amount" type="text" class="w-full rounded-xl border-slate-200 text-xs font-semibold" placeholder="Ej. 35 €" />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Notas Adicionales</label>
                            <textarea v-model="customNotes" rows="2" class="w-full rounded-xl border-slate-200 text-xs" placeholder="Cualquier aclaración especial..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- Columna Derecha: Vista Previa y Acciones -->
                <div class="flex flex-col justify-between space-y-4 rounded-2xl border border-slate-200 bg-slate-50/80 p-4">
                    <div>
                        <div class="flex items-center justify-between border-b border-slate-200/80 pb-2 mb-3">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-600">📱 Vista previa WhatsApp</span>
                            <span class="text-[11px] font-semibold text-emerald-700">Formato listo</span>
                        </div>
                        <div class="whitespace-pre-wrap rounded-xl border border-slate-200 bg-white p-3.5 font-sans text-xs text-slate-800 shadow-xs leading-relaxed max-h-72 overflow-y-auto">
                            {{ generatedMessage }}
                        </div>
                    </div>

                    <div class="space-y-2 pt-2 border-t border-slate-200">
                        <button
                            type="button"
                            class="w-full flex items-center justify-center gap-2 rounded-xl bg-emerald-600 py-2.5 text-xs font-bold text-white hover:bg-emerald-700 transition shadow-xs"
                            @click="copyToClipboard"
                        >
                            <span>{{ copied ? '✅ ¡Copiado al Portapapeles!' : '📋 Copiar Mensaje para WhatsApp' }}</span>
                        </button>
                        <button
                            type="button"
                            class="w-full flex items-center justify-center gap-2 rounded-xl border border-emerald-300 bg-emerald-50 py-2.5 text-xs font-bold text-emerald-900 hover:bg-emerald-100 transition"
                            @click="openWhatsAppWeb"
                        >
                            <span>💬 Abrir WhatsApp Web</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Envíos individuales si hay miembros cargados -->
            <div v-if="members.length" class="border-t border-slate-100 pt-4">
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-600 mb-3">Enviar a tutores / padres individualmente</h4>
                <div class="max-h-40 overflow-y-auto divide-y divide-slate-100 rounded-xl border border-slate-200 bg-white p-2">
                    <div v-for="m in members" :key="m.id" class="flex items-center justify-between py-2 px-3 text-xs">
                        <div>
                            <span class="font-bold text-slate-800">{{ m.full_name }}</span>
                            <span v-if="m.phone" class="text-slate-500 ml-2">({{ m.phone }})</span>
                        </div>
                        <a
                            v-if="m.phone"
                            :href="openWhatsAppUrl(m.phone)"
                            target="_blank"
                            class="inline-flex items-center gap-1 rounded-lg bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-800 hover:bg-emerald-200 transition"
                        >
                            <span>💬 WhatsApp</span>
                        </a>
                        <span v-else class="text-slate-400 italic">Sin teléfono</span>
                    </div>
                </div>
            </div>
        </div>
    </Modal>
</template>
