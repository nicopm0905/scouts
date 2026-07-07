<script setup>
import { computed, ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import FormField from '@/Components/Shared/FormField.vue'
import BadgeEstado from '@/Components/Shared/BadgeEstado.vue'
import ConfirmButton from '@/Components/Shared/ConfirmButton.vue'
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
        forceFormData: true, onSuccess: () => toast.success('Ficha sanitaria guardada.'),
    })
}

const consentForm = useForm({ consents: props.consents.map((c) => ({ type: c.type, granted: c.granted, signed_at: c.signed_at })) })
function saveConsents() {
    consentForm.put(route('members.consents.update', props.member.id), { onSuccess: () => toast.success('Consentimientos guardados.') })
}

const leaderForm = useForm({
    qualification: props.leaderProfile?.qualification ?? 'none',
    sexual_offenses_certificate_date: props.leaderProfile?.sexual_offenses_certificate_date ?? '',
    sexual_offenses_certificate_expires_at: props.leaderProfile?.sexual_offenses_certificate_expires_at ?? '',
})
function saveLeaderProfile() {
    leaderForm.put(route('members.leader-profile.update', props.member.id), { onSuccess: () => toast.success('Perfil de responsable guardado.') })
}

const trainingForm = useForm({ name: '', obtained_at: '', expires_at: '', attachment: null })
function addTraining() {
    trainingForm.post(route('members.leader-trainings.store', props.member.id), {
        forceFormData: true, onSuccess: () => { trainingForm.reset(); toast.success('Formación añadida.') },
    })
}
function deleteTraining(t) {
    trainingForm.delete(route('leader-trainings.destroy', t.id), { onSuccess: () => toast.success('Formación eliminada.') })
}

const familyForm = useForm({ family_id: '', relationship: props.familyRelationships[0]?.value ?? '' })
const showFamilyLink = ref(false)
function linkFamily() {
    familyForm.post(route('members.families.attach', props.member.id), {
        onSuccess: () => { showFamilyLink.value = false; toast.success('Familiar vinculado.') },
    })
}
function unlinkFamily(f) {
    familyForm.delete(route('members.families.detach', [props.member.id, f.id]), { onSuccess: () => toast.success('Vínculo eliminado.') })
}

const certificateBadge = computed(() => {
    if (!props.leaderProfile) return { label: 'Sin datos', color: 'gray' }
    return props.leaderProfile.certificate_valid
        ? { label: 'Certificado vigente', color: 'green' }
        : { label: 'Certificado caducado/ausente', color: 'red' }
})
</script>

<template>
    <Head :title="member.full_name" />
    <AppLayout>
        <!-- Hero de identidad -->
        <div class="mb-6 flex flex-col gap-4 rounded-2xl border border-ink-200 bg-white p-5 shadow-card sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-4">
                <span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-brand-600 text-2xl font-bold text-white">
                    {{ initials }}
                </span>
                <div>
                    <h1 class="text-xl font-bold text-ink-900">{{ member.full_name }}</h1>
                    <p class="text-sm text-ink-500">{{ member.role_label }} · {{ member.age ?? '—' }} años</p>
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <BadgeEstado :label="member.active ? 'Activo' : 'Baja'" :color="member.active ? 'green' : 'gray'" />
                        <BadgeEstado v-if="member.is_leader" :label="certificateBadge.label" :color="certificateBadge.color" />
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <Link v-if="canManage" :href="route('members.edit', member.id)" class="btn-secondary btn-sm">Editar</Link>
                <Link :href="route('members.index')" class="btn-ghost btn-sm">← Volver</Link>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Columna izquierda: datos + familiares -->
            <div class="space-y-6 lg:col-span-1">
                <section class="card-pad">
                    <h2 class="section-title mb-4">Datos de contacto</h2>
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between gap-3"><dt class="text-ink-400">Teléfono</dt><dd class="text-right font-medium text-ink-800">{{ member.phone ?? '—' }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-ink-400">Correo</dt><dd class="text-right font-medium text-ink-800">{{ member.email ?? '—' }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-ink-400">Nacimiento</dt><dd class="text-right font-medium text-ink-800">{{ member.birth_date ?? '—' }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-ink-400">Alta</dt><dd class="text-right font-medium text-ink-800">{{ member.joined_at ?? '—' }}</dd></div>
                        <div v-if="member.notes" class="pt-1"><dt class="text-ink-400">Notas</dt><dd class="mt-1 text-ink-700">{{ member.notes }}</dd></div>
                    </dl>
                </section>

                <section class="card-pad">
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="section-title">Familiares</h2>
                        <button v-if="canManage" class="text-sm font-semibold text-brand-700 hover:underline" @click="showFamilyLink = !showFamilyLink">+ Vincular</button>
                    </div>
                    <div v-if="showFamilyLink" class="mb-4 space-y-2 rounded-lg bg-ink-50 p-3">
                        <FormField v-model="familyForm.family_id" type="select" label="Familia"
                            :options="allFamilies.map((f) => ({ value: f.id, label: f.name }))" :error="familyForm.errors.family_id" />
                        <FormField v-model="familyForm.relationship" type="select" label="Parentesco"
                            :options="familyRelationships" :error="familyForm.errors.relationship" />
                        <button class="btn-primary btn-sm" @click="linkFamily">Vincular</button>
                    </div>
                    <ul v-if="families.length" class="space-y-2 text-sm">
                        <li v-for="f in families" :key="f.id" class="flex items-center justify-between">
                            <Link :href="route('families.edit', f.id)" class="text-brand-700 hover:underline">
                                {{ f.name }} <span class="text-ink-400">({{ f.relationship_label }})</span>
                            </Link>
                            <button v-if="canManage" class="text-xs text-brand-600 hover:underline" @click="unlinkFamily(f)">Desvincular</button>
                        </li>
                    </ul>
                    <p v-else class="text-sm text-ink-400">Sin familiares vinculados.</p>
                </section>
            </div>

            <!-- Columna derecha: fichas editables -->
            <div class="space-y-6 lg:col-span-2">
                <!-- Ficha sanitaria -->
                <section v-if="canSeeSensitive" class="card-pad">
                    <div class="mb-4 flex items-center gap-2">
                        <h2 class="text-base font-bold text-ink-800">Ficha sanitaria</h2>
                        <span class="rounded-full bg-brand-50 px-2 py-0.5 text-[11px] font-medium text-brand-700">Datos sensibles</span>
                    </div>
                    <form class="grid grid-cols-1 gap-4 sm:grid-cols-2" @submit.prevent="saveHealth">
                        <FormField v-model="healthForm.allergies" type="textarea" label="Alergias" :error="healthForm.errors.allergies" />
                        <FormField v-model="healthForm.intolerances" type="textarea" label="Intolerancias" :error="healthForm.errors.intolerances" />
                        <FormField v-model="healthForm.medication" type="textarea" label="Medicación" :error="healthForm.errors.medication" />
                        <FormField v-model="healthForm.observations" type="textarea" label="Observaciones" :error="healthForm.errors.observations" />
                        <FormField v-model="healthForm.health_card_number" label="Nº tarjeta sanitaria" :error="healthForm.errors.health_card_number" />
                        <div>
                            <label class="label">Adjuntar documento</label>
                            <input type="file" class="mt-1 block w-full text-sm text-ink-600" @change="healthForm.attachment = $event.target.files[0]" />
                            <p v-if="healthRecord?.drive_file_id" class="mt-1 text-xs text-brand-700">✓ Documento adjunto</p>
                        </div>
                        <div class="sm:col-span-2">
                            <button type="submit" class="btn-primary" :disabled="healthForm.processing">Guardar ficha sanitaria</button>
                        </div>
                    </form>
                </section>

                <!-- Consentimientos -->
                <section v-if="canSeeSensitive" class="card-pad">
                    <h2 class="mb-4 text-base font-bold text-ink-800">Consentimientos</h2>
                    <form class="space-y-2" @submit.prevent="saveConsents">
                        <div v-for="(consent, i) in consentForm.consents" :key="consent.type"
                            class="flex flex-col gap-2 rounded-lg border border-ink-100 p-3 sm:flex-row sm:items-center sm:justify-between">
                            <span class="flex items-center gap-2 text-sm font-medium text-ink-700">
                                <BadgeEstado :label="consent.granted ? 'Concedido' : 'Pendiente'" :color="consent.granted ? 'green' : 'yellow'" />
                                {{ props.consents[i]?.label }}
                            </span>
                            <div class="flex items-center gap-3">
                                <label class="flex items-center gap-1.5 text-sm text-ink-600">
                                    <input v-model="consent.granted" type="checkbox" class="rounded border-ink-300 text-brand-600 focus:ring-brand-500" />
                                    Concedido
                                </label>
                                <input v-model="consent.signed_at" type="date" class="input py-1 text-sm" />
                            </div>
                        </div>
                        <button type="submit" class="btn-primary mt-2" :disabled="consentForm.processing">Guardar consentimientos</button>
                    </form>
                </section>

                <!-- Perfil de responsable -->
                <section v-if="member.is_leader" class="card-pad">
                    <div class="mb-4 flex flex-wrap items-center gap-3">
                        <h2 class="text-base font-bold text-ink-800">Perfil de responsable</h2>
                        <BadgeEstado :label="certificateBadge.label" :color="certificateBadge.color" />
                    </div>
                    <form class="grid grid-cols-1 gap-4 sm:grid-cols-3" @submit.prevent="saveLeaderProfile">
                        <FormField v-model="leaderForm.qualification" type="select" label="Titulación" :options="qualifications" :error="leaderForm.errors.qualification" />
                        <FormField v-model="leaderForm.sexual_offenses_certificate_date" type="date" label="Fecha certificado d. sexuales" :error="leaderForm.errors.sexual_offenses_certificate_date" />
                        <FormField v-model="leaderForm.sexual_offenses_certificate_expires_at" type="date" label="Caducidad certificado" :error="leaderForm.errors.sexual_offenses_certificate_expires_at" />
                        <div class="sm:col-span-3">
                            <button type="submit" class="btn-primary" :disabled="leaderForm.processing">Guardar perfil</button>
                        </div>
                    </form>

                    <h3 class="section-title mb-2 mt-6">Formaciones</h3>
                    <ul class="mb-4 space-y-2 text-sm">
                        <li v-for="t in leaderProfile?.trainings ?? []" :key="t.id"
                            class="flex items-center justify-between rounded-lg border border-ink-100 px-3 py-2">
                            <span class="text-ink-700">
                                {{ t.name }}
                                <span class="text-ink-400">({{ t.obtained_at ?? '—' }}<template v-if="t.expires_at"> · caduca {{ t.expires_at }}</template>)</span>
                                <BadgeEstado v-if="t.expired" label="Caducada" color="red" class="ml-2" />
                            </span>
                            <ConfirmButton title="Eliminar formación" message="¿Eliminar esta formación?" confirm-label="Eliminar" @confirm="deleteTraining(t)">
                                <span class="text-xs text-brand-600 hover:underline">Eliminar</span>
                            </ConfirmButton>
                        </li>
                        <li v-if="!leaderProfile?.trainings?.length" class="text-ink-400">Sin formaciones registradas.</li>
                    </ul>

                    <form class="grid grid-cols-1 gap-3 sm:grid-cols-4" @submit.prevent="addTraining">
                        <FormField v-model="trainingForm.name" label="Formación" :error="trainingForm.errors.name" />
                        <FormField v-model="trainingForm.obtained_at" type="date" label="Obtenida" :error="trainingForm.errors.obtained_at" />
                        <FormField v-model="trainingForm.expires_at" type="date" label="Caduca" :error="trainingForm.errors.expires_at" />
                        <div>
                            <label class="label">Adjunto</label>
                            <input type="file" class="mt-1 block w-full text-sm text-ink-600" @change="trainingForm.attachment = $event.target.files[0]" />
                        </div>
                        <div class="flex items-end sm:col-span-4">
                            <button type="submit" class="btn-secondary">+ Añadir formación</button>
                        </div>
                    </form>
                </section>
            </div>
        </div>
    </AppLayout>
</template>
