<script setup>
import { computed, ref } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import AppButton from '@/Components/Shared/AppButton.vue'
import FormField from '@/Components/Shared/FormField.vue'
import BadgeEstado from '@/Components/Shared/BadgeEstado.vue'
import BadgeRama from '@/Components/Shared/BadgeRama.vue'
import ConfirmButton from '@/Components/Shared/ConfirmButton.vue'
import WhatsAppCircularModal from '@/Components/Shared/WhatsAppCircularModal.vue'
import { useToast } from '@/composables/useToast'

const props = defineProps({
    member: { type: Object, required: true },
    canManage: { type: Boolean, default: false },
    canSeeSensitive: { type: Boolean, default: false },
    families: { type: Array, default: () => [] },
    allFamilies: { type: Array, default: () => [] },
    familyRelationships: { type: Array, default: () => [] },
    healthRecord: { type: Object, default: null },
    consents: { type: Array, default: () => [] },
    leaderProfile: { type: Object, default: null },
    qualifications: { type: Array, default: () => [] },
})

const toast = useToast()
const activeTab = ref('personal') // 'personal' | 'families' | 'health' | 'consents' | 'leader'
const showWhatsAppModal = ref(false)

const initials = computed(() => (props.member.full_name ?? '?').split(' ').map((p) => p[0]).slice(0, 2).join('').toUpperCase())

const healthForm = useForm({
    allergies: props.healthRecord?.allergies ?? '',
    intolerances: props.healthRecord?.intolerances ?? '',
    medication: props.healthRecord?.medication ?? '',
    observations: props.healthRecord?.observations ?? '',
    health_card_number: props.healthRecord?.health_card_number ?? '',
    attachment: null,
})
function saveHealth() {
    healthForm.transform((d) => ({ ...d, _method: 'put' })).post(route('members.health-record.update', props.member.id), {
        forceFormData: true,
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => toast.success('Ficha sanitaria guardada.'),
    })
}

const consentForm = useForm({ consents: props.consents.map((c) => ({ type: c.type, granted: c.granted, signed_at: c.signed_at })) })
function saveConsents() {
    consentForm.put(route('members.consents.update', props.member.id), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => toast.success('Consentimientos guardados.'),
    })
}

const sendingSignature = ref(null)
function sendSignature(type) {
    sendingSignature.value = type
    router.post(route('signatures.consent.send', [props.member.id, type]), {}, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => toast.success('Solicitud de firma enviada.'),
        onFinish: () => { sendingSignature.value = null },
    })
}

const leaderForm = useForm({
    qualification: props.leaderProfile?.qualification ?? 'none',
    sexual_offenses_certificate_date: props.leaderProfile?.sexual_offenses_certificate_date ?? '',
    sexual_offenses_certificate_expires_at: props.leaderProfile?.sexual_offenses_certificate_expires_at ?? '',
    branches: props.leaderProfile?.branches ?? [],
})
function saveLeaderProfile() {
    leaderForm.put(route('members.leader-profile.update', props.member.id), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => toast.success('Perfil de responsable guardado.'),
    })
}

const trainingForm = useForm({ name: '', obtained_at: '', expires_at: '', attachment: null })
function addTraining() {
    trainingForm.post(route('members.leader-trainings.store', props.member.id), {
        forceFormData: true,
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => { trainingForm.reset(); toast.success('Formación añadida.') },
    })
}
function deleteTraining(t) {
    trainingForm.delete(route('leader-trainings.destroy', t.id), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => toast.success('Formación eliminada.'),
    })
}

const familyForm = useForm({ family_id: '', relationship: props.familyRelationships[0]?.value ?? '' })
const showFamilyLink = ref(false)
function linkFamily() {
    familyForm.post(route('members.families.attach', props.member.id), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => { showFamilyLink.value = false; toast.success('Familiar vinculado.') },
    })
}
function unlinkFamily(f) {
    familyForm.delete(route('members.families.detach', [props.member.id, f.id]), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => toast.success('Vínculo eliminado.'),
    })
}

const certificateBadge = computed(() => {
    if (!props.leaderProfile) return { label: 'Sin datos', color: 'gray' }
    return props.leaderProfile.certificate_valid
        ? { label: 'Certificado vigente', color: 'green' }
        : { label: 'Certificado caducado/ausente', color: 'red' }
})

function whatsAppUrl(phone, text = '') {
    if (!phone) return '#'
    const cleanPhone = phone.replace(/\D/g, '')
    const fullPhone = cleanPhone.length === 9 ? `34${cleanPhone}` : cleanPhone
    const msg = text || `Hola ${props.member.first_name}, te escribimos desde Scouts de San José ⚜️`
    return `https://wa.me/${fullPhone}?text=${encodeURIComponent(msg)}`
}
</script>

<template>
    <Head :title="member.full_name" />
    <AppLayout>
        <div class="space-y-6">
            <!-- Hero Dossier del Miembro -->
            <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-slate-900 text-2xl font-black text-white shadow-sm">
                            {{ initials }}
                        </div>
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h1 class="text-2xl font-black tracking-tight text-slate-900">{{ member.full_name }}</h1>
                                <BadgeRama :rama="member.role" size="lg" />
                                <BadgeEstado :label="member.active ? 'Activo' : 'Baja'" :color="member.active ? 'green' : 'gray'" />
                            </div>
                            
                            <p class="mt-1 text-sm font-medium text-slate-600 flex flex-wrap items-center gap-2">
                                <span v-if="member.age !== null" class="font-bold text-slate-800">{{ member.age }} años</span>
                                <span v-if="member.birth_date">· {{ member.birth_date }} (Edad calculada automáticamente)</span>
                            </p>

                            <div v-if="member.is_leader" class="mt-2">
                                <BadgeEstado :label="certificateBadge.label" :color="certificateBadge.color" />
                            </div>
                        </div>
                    </div>

                    <!-- Acciones rápidas -->
                    <div class="flex flex-wrap items-center gap-2 border-t border-slate-100 pt-4 sm:border-t-0 sm:pt-0">
                        <AppButton
                            v-if="member.phone"
                            :href="whatsAppUrl(member.phone)"
                            external
                            size="sm"
                            icon="phone"
                        >
                            WhatsApp
                        </AppButton>
                        <AppButton size="sm" icon="mail" @click="showWhatsAppModal = true">Generar circular</AppButton>
                        <AppButton v-if="canManage" :href="route('members.edit', member.id)" variant="primary" size="sm" icon="edit">
                            Editar
                        </AppButton>
                        <AppButton :href="route('members.index')" size="sm">Volver</AppButton>
                    </div>
                </div>

                <!-- Pestañas de Dossier -->
                <div class="mt-6 flex border-b border-slate-200 overflow-x-auto gap-1">
                    <button
                        type="button"
                        class="px-4 py-2.5 text-xs font-bold transition border-b-2 whitespace-nowrap"
                        :class="activeTab === 'personal' ? 'border-brand-600 text-brand-800 bg-brand-50/60 rounded-t-xl' : 'border-transparent text-slate-500 hover:text-slate-900'"
                        @click="activeTab = 'personal'"
                    >
                        Datos personales
                    </button>
                    <button
                        type="button"
                        class="px-4 py-2.5 text-xs font-bold transition border-b-2 whitespace-nowrap"
                        :class="activeTab === 'families' ? 'border-brand-600 text-brand-800 bg-brand-50/60 rounded-t-xl' : 'border-transparent text-slate-500 hover:text-slate-900'"
                        @click="activeTab = 'families'"
                    >
                        Familiares y tutores ({{ families.length }})
                    </button>
                    <button
                        v-if="canSeeSensitive"
                        type="button"
                        class="px-4 py-2.5 text-xs font-bold transition border-b-2 whitespace-nowrap"
                        :class="activeTab === 'health' ? 'border-brand-600 text-brand-800 bg-brand-50/60 rounded-t-xl' : 'border-transparent text-slate-500 hover:text-slate-900'"
                        @click="activeTab = 'health'"
                    >
                        Ficha sanitaria
                    </button>
                    <button
                        v-if="canSeeSensitive"
                        type="button"
                        class="px-4 py-2.5 text-xs font-bold transition border-b-2 whitespace-nowrap"
                        :class="activeTab === 'consents' ? 'border-brand-600 text-brand-800 bg-brand-50/60 rounded-t-xl' : 'border-transparent text-slate-500 hover:text-slate-900'"
                        @click="activeTab = 'consents'"
                    >
                        Consentimientos ({{ consents.length }})
                    </button>
                    <button
                        v-if="member.is_leader"
                        type="button"
                        class="px-4 py-2.5 text-xs font-bold transition border-b-2 whitespace-nowrap"
                        :class="activeTab === 'leader' ? 'border-brand-600 text-brand-800 bg-brand-50/60 rounded-t-xl' : 'border-transparent text-slate-500 hover:text-slate-900'"
                        @click="activeTab = 'leader'"
                    >
                        Perfil de responsable
                    </button>
                </div>
            </div>

            <!-- Contenido de las Pestañas -->
            <div class="space-y-6">
                <!-- Pestaña 1: Datos Personales -->
                <div v-if="activeTab === 'personal'" class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm">
                    <h2 class="text-base font-bold text-slate-900 mb-4 border-b border-slate-100 pb-2">Información del Miembro</h2>
                    <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 text-sm">
                        <div class="rounded-xl border border-slate-100 bg-slate-50/50 p-3.5">
                            <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Teléfono Directo</dt>
                            <dd class="mt-1 flex items-center justify-between font-bold text-slate-900">
                                <span>{{ member.phone ?? 'Sin teléfono' }}</span>
                                <a v-if="member.phone" :href="whatsAppUrl(member.phone)" target="_blank" class="text-xs font-bold text-emerald-700 hover:underline">💬 WhatsApp</a>
                            </dd>
                        </div>
                        <div class="rounded-xl border border-slate-100 bg-slate-50/50 p-3.5">
                            <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Correo Electrónico</dt>
                            <dd class="mt-1 font-bold text-slate-900 truncate">{{ member.email ?? 'Sin correo' }}</dd>
                        </div>
                        <div class="rounded-xl border border-slate-100 bg-slate-50/50 p-3.5">
                            <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Fecha de Nacimiento (Cumpleaños)</dt>
                            <dd class="mt-1 font-bold text-slate-900">{{ member.birth_date ?? '—' }}</dd>
                        </div>
                        <div class="rounded-xl border border-slate-100 bg-slate-50/50 p-3.5">
                            <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Fecha de Alta en Grupo</dt>
                            <dd class="mt-1 font-bold text-slate-900">{{ member.joined_at ?? '—' }}</dd>
                        </div>
                        <div class="rounded-xl border border-slate-100 bg-slate-50/50 p-3.5">
                            <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Rama Educativa</dt>
                            <dd class="mt-1 font-bold text-slate-900"><BadgeRama :rama="member.role" /></dd>
                        </div>
                    </dl>
                    <div v-if="member.notes" class="mt-4 rounded-xl border border-slate-100 bg-slate-50/50 p-4">
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Notas adicionales</p>
                        <p class="mt-1 text-sm text-slate-800 leading-relaxed">{{ member.notes }}</p>
                    </div>
                </div>

                <!-- Pestaña 2: Familiares -->
                <div v-if="activeTab === 'families'" class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Familiares y Tutores Vinculados</h2>
                            <p class="text-xs text-slate-500">Padres, madres o tutores legales responsables del scout</p>
                        </div>
                        <button v-if="canManage" class="rounded-xl bg-emerald-50 border border-emerald-300 px-3 py-1.5 text-xs font-bold text-emerald-900 hover:bg-emerald-100 transition" @click="showFamilyLink = !showFamilyLink">
                            + Vincular Familiar
                        </button>
                    </div>

                    <div v-if="showFamilyLink" class="space-y-3 rounded-2xl bg-slate-50 p-4 border border-slate-200">
                        <FormField v-model="familyForm.family_id" type="select" label="Seleccionar Unidad Familiar"
                            :options="allFamilies.map((f) => ({ value: f.id, label: f.name }))" :error="familyForm.errors.family_id" />
                        <FormField v-model="familyForm.relationship" type="select" label="Parentesco"
                            :options="familyRelationships" :error="familyForm.errors.relationship" />
                        <button class="rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white hover:bg-emerald-700 transition" @click="linkFamily">Vincular</button>
                    </div>

                    <div v-if="families.length" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div v-for="f in families" :key="f.id" class="rounded-2xl border border-slate-200 bg-slate-50/50 p-4 space-y-2">
                            <div class="flex items-center justify-between">
                                <Link :href="route('families.edit', f.id)" class="font-bold text-slate-900 hover:text-emerald-700 transition text-base">
                                    👨‍👩‍👧 Familia {{ f.name }}
                                </Link>
                                <span class="rounded-lg bg-emerald-100 px-2 py-0.5 text-xs font-bold text-emerald-800">{{ f.relationship_label }}</span>
                            </div>

                            <p v-if="f.contact_phone" class="text-xs font-medium text-slate-600 flex items-center justify-between">
                                <span>Teléfono de contacto: <strong>{{ f.contact_phone }}</strong></span>
                                <a :href="whatsAppUrl(f.contact_phone)" target="_blank" class="rounded-lg bg-emerald-600 px-2.5 py-1 text-xs font-bold text-white hover:bg-emerald-700 transition">
                                    💬 WhatsApp Tutor
                                </a>
                            </p>

                            <div v-if="canManage" class="pt-2 border-t border-slate-200/60 text-right">
                                <button class="text-xs font-bold text-rose-600 hover:underline" @click="unlinkFamily(f)">Desvincular</button>
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-sm font-medium text-slate-400 py-4 text-center">No hay familiares ni tutores vinculados aún.</p>
                </div>

                <!-- Pestaña 3: Ficha Sanitaria (RGPD) -->
                <div v-if="activeTab === 'health' && canSeeSensitive" class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm">
                    <div class="mb-4 border-b border-slate-100 pb-3 flex items-center justify-between">
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Ficha Sanitaria y Salud (RGPD)</h2>
                            <p class="text-xs text-slate-500">Información de salud cifrada para acampadas y salidas</p>
                        </div>
                        <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800">Protegido por Ley RGPD</span>
                    </div>

                    <form class="grid grid-cols-1 gap-4 sm:grid-cols-2" @submit.prevent="saveHealth">
                        <FormField v-model="healthForm.allergies" type="textarea" label="Alergias Conocidas" :error="healthForm.errors.allergies" />
                        <FormField v-model="healthForm.intolerances" type="textarea" label="Intolerancias Alimentarias" :error="healthForm.errors.intolerances" />
                        <FormField v-model="healthForm.medication" type="textarea" label="Medicación habitual" :error="healthForm.errors.medication" />
                        <FormField v-model="healthForm.observations" type="textarea" label="Observaciones Médicas" :error="healthForm.errors.observations" />
                        <FormField v-model="healthForm.health_card_number" label="Nº Tarjeta Sanitaria / SIP" :error="healthForm.errors.health_card_number" />
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Documento Adjunto (Ficha Médica PDF / Foto)</label>
                            <input type="file" class="mt-1 block w-full text-xs text-slate-600" @change="healthForm.attachment = $event.target.files[0]" />
                            <p v-if="healthRecord?.drive_file_id" class="mt-1.5 text-xs font-bold text-emerald-700">✓ Documento almacenado en Google Drive</p>
                        </div>
                        <div class="sm:col-span-2 pt-2">
                            <button type="submit" class="rounded-xl bg-emerald-600 px-6 py-2.5 text-xs font-bold text-white hover:bg-emerald-700 transition shadow-xs" :disabled="healthForm.processing">
                                Guardar Ficha Sanitaria
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Pestaña 4: Consentimientos -->
                <div v-if="activeTab === 'consents' && canSeeSensitive" class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm space-y-4">
                    <div class="border-b border-slate-100 pb-3">
                        <h2 class="text-base font-bold text-slate-900">Consentimientos y Firmas Legales</h2>
                        <p class="text-xs text-slate-500">Autorización de uso de imagen, salidas y firma digital de tutores</p>
                    </div>

                    <form class="space-y-3" @submit.prevent="saveConsents">
                        <div v-for="(consent, i) in consentForm.consents" :key="consent.type"
                            class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-slate-50/50 p-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="space-y-1">
                                <p class="text-sm font-bold text-slate-900">{{ props.consents[i]?.label }}</p>
                                <div class="flex items-center gap-2">
                                    <BadgeEstado :label="consent.granted ? 'Concedido' : 'Pendiente'" :color="consent.granted ? 'green' : 'yellow'" />
                                    <BadgeEstado
                                        v-if="props.consents[i]?.signature_status"
                                        :label="props.consents[i]?.signature_status_label"
                                        :color="props.consents[i]?.signature_status === 'signed' ? 'green' : 'yellow'"
                                    />
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-3">
                                <label class="flex items-center gap-2 text-xs font-bold text-slate-700">
                                    <input v-model="consent.granted" type="checkbox" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" />
                                    Concedido
                                </label>
                                <input v-model="consent.signed_at" type="date" class="rounded-xl border-slate-200 text-xs font-semibold py-1.5" />
                                <button
                                    type="button"
                                    class="rounded-xl bg-slate-200 px-3 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-300 transition"
                                    :disabled="sendingSignature === consent.type"
                                    @click="sendSignature(consent.type)"
                                >
                                    📩 Solicitar Firma Digital
                                </button>
                            </div>
                        </div>

                        <div class="pt-2">
                            <button type="submit" class="rounded-xl bg-emerald-600 px-6 py-2.5 text-xs font-bold text-white hover:bg-emerald-700 transition shadow-xs">
                                Guardar Consentimientos
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Pestaña 5: Perfil Responsable -->
                <div v-if="activeTab === 'leader' && member.is_leader" class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm space-y-4">
                    <div class="border-b border-slate-100 pb-3">
                        <h2 class="text-base font-bold text-slate-900">Perfil de Responsable (Kraal)</h2>
                        <p class="text-xs text-slate-500">Titulaciones oficiales de tiempo libre y Certificado de Delitos Sexuales</p>
                    </div>

                    <form class="grid grid-cols-1 gap-4 sm:grid-cols-2" @submit.prevent="saveLeaderProfile">
                        <FormField v-model="leaderForm.qualification" type="select" label="Titulación de Tiempo Libre" :options="qualifications" :error="leaderForm.errors.qualification" />
                        <div></div>
                        <FormField v-model="leaderForm.sexual_offenses_certificate_date" type="date" label="Fecha Certificado Delitos Sexuales" :error="leaderForm.errors.sexual_offenses_certificate_date" />
                        <FormField v-model="leaderForm.sexual_offenses_certificate_expires_at" type="date" label="Fecha Caducidad Certificado" :error="leaderForm.errors.sexual_offenses_certificate_expires_at" />
                        
                        <div class="sm:col-span-2 space-y-2 rounded-2xl border border-slate-200/80 bg-slate-50/50 p-4">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600">Ramas Educativas Asignadas al Responsable</label>
                            <p class="text-xs text-slate-500 mb-2">Marcar las ramas en las que trabaja este responsable para incluirlo automáticamente en sus eventos y listas de actividades</p>
                            <div class="flex flex-wrap gap-4">
                                <label v-for="b in ['castor', 'lobato', 'ranger', 'pionero', 'ruta']" :key="b" class="flex items-center gap-2 text-xs font-bold text-slate-700 cursor-pointer">
                                    <input type="checkbox" :value="b" v-model="leaderForm.branches" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" />
                                    <BadgeRama :rama="b" />
                                </label>
                            </div>
                        </div>

                        <div class="sm:col-span-2 pt-2">
                            <button type="submit" class="rounded-xl bg-emerald-600 px-6 py-2.5 text-xs font-bold text-white hover:bg-emerald-700 transition shadow-xs">
                                Guardar Perfil de Responsable
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Generador de Circulares WhatsApp -->
        <WhatsAppCircularModal
            :show="showWhatsAppModal"
            :default-branch="member.role"
            :members="[member]"
            @close="showWhatsAppModal = false"
        />
    </AppLayout>
</template>
