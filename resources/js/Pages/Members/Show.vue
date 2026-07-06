<script setup>
import { computed, ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
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

// --- Ficha sanitaria ---
const healthForm = useForm({
    allergies: props.healthRecord?.allergies ?? '',
    intolerances: props.healthRecord?.intolerances ?? '',
    medication: props.healthRecord?.medication ?? '',
    observations: props.healthRecord?.observations ?? '',
    health_card_number: props.healthRecord?.health_card_number ?? '',
    attachment: null,
})

function saveHealth() {
    healthForm.transform((data) => ({ ...data, _method: 'put' })).post(route('members.health-record.update', props.member.id), {
        forceFormData: true,
        onSuccess: () => toast.success('Ficha sanitaria guardada.'),
    })
}

// --- Consentimientos ---
const consentForm = useForm({
    consents: props.consents.map((c) => ({ type: c.type, granted: c.granted, signed_at: c.signed_at })),
})

function saveConsents() {
    consentForm.put(route('members.consents.update', props.member.id), {
        onSuccess: () => toast.success('Consentimientos guardados.'),
    })
}

// --- Perfil de responsable ---
const leaderForm = useForm({
    qualification: props.leaderProfile?.qualification ?? 'none',
    sexual_offenses_certificate_date: props.leaderProfile?.sexual_offenses_certificate_date ?? '',
    sexual_offenses_certificate_expires_at: props.leaderProfile?.sexual_offenses_certificate_expires_at ?? '',
})

function saveLeaderProfile() {
    leaderForm.put(route('members.leader-profile.update', props.member.id), {
        onSuccess: () => toast.success('Perfil de responsable guardado.'),
    })
}

const trainingForm = useForm({ name: '', obtained_at: '', expires_at: '', attachment: null })

function addTraining() {
    trainingForm.post(route('members.leader-trainings.store', props.member.id), {
        forceFormData: true,
        onSuccess: () => {
            trainingForm.reset()
            toast.success('Formación añadida.')
        },
    })
}

function deleteTraining(training) {
    trainingForm.delete(route('leader-trainings.destroy', training.id), {
        onSuccess: () => toast.success('Formación eliminada.'),
    })
}

// --- Familiares ---
const familyForm = useForm({ family_id: '', relationship: props.familyRelationships[0]?.value ?? '' })
const showFamilyLink = ref(false)

function linkFamily() {
    familyForm.post(route('members.families.attach', props.member.id), {
        onSuccess: () => {
            showFamilyLink.value = false
            toast.success('Familiar vinculado.')
        },
    })
}

function unlinkFamily(family) {
    familyForm.delete(route('members.families.detach', [props.member.id, family.id]), {
        onSuccess: () => toast.success('Vínculo eliminado.'),
    })
}

const certificateBadge = computed(() => {
    if (!props.leaderProfile) return { label: 'Sin datos', color: 'gray' }
    return props.leaderProfile.certificate_valid
        ? { label: 'Certificado vigente', color: 'green' }
        : { label: 'Certificado caducado o ausente', color: 'red' }
})
</script>

<template>
    <Head :title="member.full_name" />
    <AppLayout>
        <PageHeader :title="member.full_name" :subtitle="`${member.role_label} · ${member.age ?? '—'} años`">
            <template #actions>
                <BadgeEstado :label="member.active ? 'Activo' : 'Baja'" :color="member.active ? 'green' : 'gray'" />
                <Link
                    v-if="canManage"
                    :href="route('members.edit', member.id)"
                    class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                >
                    Editar
                </Link>
                <Link :href="route('members.index')" class="text-sm text-slate-500 hover:underline">Volver</Link>
            </template>
        </PageHeader>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Datos generales -->
            <section class="rounded-lg border border-slate-200 bg-white p-6">
                <h2 class="mb-4 text-lg font-semibold text-slate-800">Datos generales</h2>
                <dl class="grid grid-cols-2 gap-3 text-sm">
                    <dt class="text-slate-500">Teléfono</dt>
                    <dd class="text-slate-800">{{ member.phone ?? '—' }}</dd>
                    <dt class="text-slate-500">Correo</dt>
                    <dd class="text-slate-800">{{ member.email ?? '—' }}</dd>
                    <dt class="text-slate-500">Fecha de nacimiento</dt>
                    <dd class="text-slate-800">{{ member.birth_date ?? '—' }}</dd>
                    <dt class="text-slate-500">Fecha de alta</dt>
                    <dd class="text-slate-800">{{ member.joined_at ?? '—' }}</dd>
                    <dt class="text-slate-500">Notas</dt>
                    <dd class="text-slate-800">{{ member.notes ?? '—' }}</dd>
                </dl>
            </section>

            <!-- Familiares -->
            <section class="rounded-lg border border-slate-200 bg-white p-6">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-slate-800">Familiares</h2>
                    <button
                        v-if="canManage"
                        class="text-sm font-semibold text-emerald-700 hover:underline"
                        @click="showFamilyLink = !showFamilyLink"
                    >
                        + Vincular familiar
                    </button>
                </div>

                <div v-if="showFamilyLink" class="mb-4 space-y-2 rounded-md bg-slate-50 p-3">
                    <FormField
                        v-model="familyForm.family_id"
                        type="select"
                        label="Familia"
                        :options="allFamilies.map((f) => ({ value: f.id, label: f.name }))"
                        :error="familyForm.errors.family_id"
                    />
                    <FormField
                        v-model="familyForm.relationship"
                        type="select"
                        label="Parentesco"
                        :options="familyRelationships"
                        :error="familyForm.errors.relationship"
                    />
                    <button
                        class="rounded-md bg-emerald-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-emerald-700"
                        @click="linkFamily"
                    >
                        Vincular
                    </button>
                </div>

                <ul v-if="families.length" class="space-y-2 text-sm">
                    <li v-for="f in families" :key="f.id" class="flex items-center justify-between">
                        <Link :href="route('families.edit', f.id)" class="text-emerald-700 hover:underline">
                            {{ f.name }} <span class="text-slate-400">({{ f.relationship_label }})</span>
                        </Link>
                        <button v-if="canManage" class="text-xs text-red-600 hover:underline" @click="unlinkFamily(f)">
                            Desvincular
                        </button>
                    </li>
                </ul>
                <p v-else class="text-sm text-slate-400">Sin familiares vinculados.</p>
            </section>

            <!-- Ficha sanitaria -->
            <section v-if="canSeeSensitive" class="rounded-lg border border-slate-200 bg-white p-6 lg:col-span-2">
                <h2 class="mb-4 text-lg font-semibold text-slate-800">Ficha sanitaria</h2>
                <form class="grid grid-cols-1 gap-4 sm:grid-cols-2" @submit.prevent="saveHealth">
                    <FormField v-model="healthForm.allergies" type="textarea" label="Alergias" :error="healthForm.errors.allergies" />
                    <FormField v-model="healthForm.intolerances" type="textarea" label="Intolerancias" :error="healthForm.errors.intolerances" />
                    <FormField v-model="healthForm.medication" type="textarea" label="Medicación" :error="healthForm.errors.medication" />
                    <FormField v-model="healthForm.observations" type="textarea" label="Observaciones" :error="healthForm.errors.observations" />
                    <FormField v-model="healthForm.health_card_number" label="Nº tarjeta sanitaria" :error="healthForm.errors.health_card_number" />
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Adjuntar documento</label>
                        <input
                            type="file"
                            class="mt-1 block w-full text-sm"
                            @change="healthForm.attachment = $event.target.files[0]"
                        />
                        <p v-if="healthRecord?.drive_file_id" class="mt-1 text-xs text-emerald-700">Hay un documento adjunto.</p>
                    </div>
                    <div class="sm:col-span-2">
                        <button
                            type="submit"
                            class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-50"
                            :disabled="healthForm.processing"
                        >
                            Guardar ficha sanitaria
                        </button>
                    </div>
                </form>
            </section>

            <!-- Consentimientos -->
            <section v-if="canSeeSensitive" class="rounded-lg border border-slate-200 bg-white p-6 lg:col-span-2">
                <h2 class="mb-4 text-lg font-semibold text-slate-800">Consentimientos</h2>
                <form class="space-y-3" @submit.prevent="saveConsents">
                    <div
                        v-for="(consent, i) in consentForm.consents"
                        :key="consent.type"
                        class="flex flex-col gap-2 rounded-md border border-slate-100 p-3 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <span class="text-sm font-medium text-slate-700">
                            {{ props.consents[i]?.label }}
                        </span>
                        <div class="flex items-center gap-3">
                            <label class="flex items-center gap-1 text-sm text-slate-600">
                                <input v-model="consent.granted" type="checkbox" class="rounded border-slate-300 text-emerald-600" />
                                Concedido
                            </label>
                            <input v-model="consent.signed_at" type="date" class="rounded-md border-slate-300 text-sm shadow-sm" />
                        </div>
                    </div>
                    <button
                        type="submit"
                        class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-50"
                        :disabled="consentForm.processing"
                    >
                        Guardar consentimientos
                    </button>
                </form>
            </section>

            <!-- Perfil de responsable -->
            <section v-if="member.is_leader" class="rounded-lg border border-slate-200 bg-white p-6 lg:col-span-2">
                <div class="mb-4 flex items-center gap-3">
                    <h2 class="text-lg font-semibold text-slate-800">Perfil de responsable</h2>
                    <BadgeEstado :label="certificateBadge.label" :color="certificateBadge.color" />
                </div>

                <form class="grid grid-cols-1 gap-4 sm:grid-cols-3" @submit.prevent="saveLeaderProfile">
                    <FormField
                        v-model="leaderForm.qualification"
                        type="select"
                        label="Titulación"
                        :options="qualifications"
                        :error="leaderForm.errors.qualification"
                    />
                    <FormField
                        v-model="leaderForm.sexual_offenses_certificate_date"
                        type="date"
                        label="Fecha certificado delitos sexuales"
                        :error="leaderForm.errors.sexual_offenses_certificate_date"
                    />
                    <FormField
                        v-model="leaderForm.sexual_offenses_certificate_expires_at"
                        type="date"
                        label="Caducidad del certificado"
                        :error="leaderForm.errors.sexual_offenses_certificate_expires_at"
                    />
                    <div class="sm:col-span-3">
                        <button
                            type="submit"
                            class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-50"
                            :disabled="leaderForm.processing"
                        >
                            Guardar perfil
                        </button>
                    </div>
                </form>

                <h3 class="mb-2 mt-6 text-sm font-semibold text-slate-700">Formaciones</h3>
                <ul class="mb-4 space-y-2 text-sm">
                    <li
                        v-for="t in leaderProfile?.trainings ?? []"
                        :key="t.id"
                        class="flex items-center justify-between rounded-md border border-slate-100 px-3 py-2"
                    >
                        <span>
                            {{ t.name }}
                            <span class="text-slate-400">
                                ({{ t.obtained_at ?? '—' }}<template v-if="t.expires_at"> · caduca {{ t.expires_at }}</template>)
                            </span>
                            <BadgeEstado v-if="t.expired" label="Caducada" color="red" class="ml-2" />
                        </span>
                        <ConfirmButton
                            title="Eliminar formación"
                            message="¿Eliminar esta formación?"
                            confirm-label="Eliminar"
                            @confirm="deleteTraining(t)"
                        >
                            <span class="text-xs text-red-600 hover:underline">Eliminar</span>
                        </ConfirmButton>
                    </li>
                    <li v-if="!leaderProfile?.trainings?.length" class="text-slate-400">Sin formaciones registradas.</li>
                </ul>

                <form class="grid grid-cols-1 gap-3 sm:grid-cols-4" @submit.prevent="addTraining">
                    <FormField v-model="trainingForm.name" label="Formación" :error="trainingForm.errors.name" />
                    <FormField v-model="trainingForm.obtained_at" type="date" label="Obtenida" :error="trainingForm.errors.obtained_at" />
                    <FormField v-model="trainingForm.expires_at" type="date" label="Caduca" :error="trainingForm.errors.expires_at" />
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Adjunto</label>
                        <input
                            type="file"
                            class="mt-1 block w-full text-sm"
                            @change="trainingForm.attachment = $event.target.files[0]"
                        />
                    </div>
                    <div class="flex items-end">
                        <button
                            type="submit"
                            class="rounded-md border border-emerald-600 px-4 py-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-50"
                        >
                            Añadir formación
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </AppLayout>
</template>
