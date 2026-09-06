<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import FormField from '@/Components/Shared/FormField.vue'
import ConfirmButton from '@/Components/Shared/ConfirmButton.vue'
import { useToast } from '@/composables/useToast'

const props = defineProps({
    family: { type: Object, required: true },
    inviteUrl: { type: String, default: null },
})

const toast = useToast()

function copyInvite() {
    navigator.clipboard?.writeText(props.inviteUrl).then(
        () => toast.success('Enlace copiado.'),
        () => toast.error('No se pudo copiar.'),
    )
}

const form = useForm({
    name: props.family.name,
    contact_phone: props.family.contact_phone,
    contact_email: props.family.contact_email,
    notes: props.family.notes,
})

const inviteForm = useForm({
    name: '',
    email: props.family.contact_email ?? '',
})

function submit() {
    form.put(route('families.update', props.family.id), {
        onSuccess: () => toast.success('Familia actualizada.'),
    })
}

function destroy() {
    form.delete(route('families.destroy', props.family.id))
}

function invite() {
    inviteForm.post(route('families.invite', props.family.id), {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Acceso generado.')
            inviteForm.reset('name')
        },
    })
}

function revoke(userId) {
    useForm({}).delete(route('families.accounts.revoke', [props.family.id, userId]), {
        preserveScroll: true,
        onSuccess: () => toast.success('Acceso retirado.'),
    })
}

function resend(email) {
    inviteForm
        .transform((d) => ({ ...d, email }))
        .post(route('families.invite', props.family.id), {
            preserveScroll: true,
            onSuccess: () => toast.success('Enlace nuevo generado.'),
        })
}
</script>

<template>
    <Head :title="family.name" />
    <AppLayout>
        <PageHeader :title="family.name" icon="heart">
            <template #actions>
                <ConfirmButton
                    title="Eliminar familia"
                    message="¿Seguro que quieres eliminar esta familia? Se perderán los vínculos con sus miembros."
                    confirm-label="Eliminar"
                    @confirm="destroy"
                >
                    <span class="rounded-md border border-red-300 px-4 py-2 text-sm font-semibold text-red-600 hover:bg-red-50">
                        Eliminar familia
                    </span>
                </ConfirmButton>
            </template>
        </PageHeader>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <form class="space-y-4 rounded-lg border border-slate-200 bg-white p-6" @submit.prevent="submit">
                <FormField v-model="form.name" label="Nombre de familia" required :error="form.errors.name" />
                <FormField v-model="form.contact_phone" label="Teléfono de contacto" :error="form.errors.contact_phone" />
                <FormField v-model="form.contact_email" type="email" label="Correo de contacto" :error="form.errors.contact_email" />
                <FormField v-model="form.notes" type="textarea" label="Notas" :error="form.errors.notes" />
                <button
                    type="submit"
                    class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700 disabled:opacity-50"
                    :disabled="form.processing"
                >
                    Guardar cambios
                </button>
            </form>

            <div class="space-y-6">
                <section class="rounded-lg border border-slate-200 bg-white p-6">
                    <h2 class="mb-4 text-lg font-semibold text-slate-800">Miembros vinculados</h2>
                    <ul v-if="family.members.length" class="space-y-2 text-sm">
                        <li v-for="m in family.members" :key="m.id" class="flex items-center justify-between">
                            <Link :href="route('members.show', m.id)" class="text-brand-700 hover:underline">{{ m.full_name }}</Link>
                            <span class="text-slate-400">{{ m.relationship }}</span>
                        </li>
                    </ul>
                    <p v-else class="text-sm text-slate-400">
                        Sin miembros vinculados. Ve a la ficha de un miembro para vincularlo a esta familia.
                    </p>
                </section>

                <section class="rounded-lg border border-slate-200 bg-white p-6">
                    <h2 class="text-lg font-semibold text-slate-800">Acceso al portal</h2>
                    <p class="mb-4 mt-1 text-sm text-slate-500">
                        Invita a padres, madres o tutores: se genera un enlace para que creen su contraseña y
                        entren al portal (pagos pendientes, calendario y autorizaciones de sus hijos/as).
                    </p>

                    <div v-if="inviteUrl" class="mb-4 rounded-md border border-brand-200 bg-brand-50 p-3 text-sm">
                        <p class="font-semibold text-brand-800">Enlace para crear la contraseña</p>
                        <p class="mt-0.5 text-brand-700">
                            Copia este enlace y dáselo a la familia (caduca en 1&nbsp;hora).
                        </p>
                        <div class="mt-2 flex items-center gap-2">
                            <code class="max-w-full truncate rounded bg-white px-2 py-1 text-xs text-slate-600">{{ inviteUrl }}</code>
                            <button type="button" class="shrink-0 rounded border border-brand-600 px-2 py-1 text-xs font-semibold text-brand-700 hover:bg-white" @click="copyInvite">
                                Copiar
                            </button>
                        </div>
                    </div>

                    <ul v-if="family.accounts.length" class="mb-4 space-y-2 text-sm">
                        <li
                            v-for="a in family.accounts"
                            :key="a.id"
                            class="flex items-center justify-between rounded-md border border-slate-100 bg-slate-50 px-3 py-2"
                        >
                            <span>
                                <span class="font-medium text-slate-700">{{ a.name }}</span>
                                <span class="text-slate-400"> · {{ a.email }}</span>
                                <span v-if="!a.active" class="ml-2 rounded bg-amber-100 px-1.5 py-0.5 text-xs text-amber-700">
                                    inactiva
                                </span>
                            </span>
                            <span class="flex gap-3 text-xs">
                                <button type="button" class="text-brand-700 hover:underline" @click="resend(a.email)">
                                    Nuevo enlace
                                </button>
                                <button type="button" class="text-red-600 hover:underline" @click="revoke(a.id)">
                                    Quitar
                                </button>
                            </span>
                        </li>
                    </ul>

                    <form class="space-y-3" @submit.prevent="invite">
                        <FormField v-model="inviteForm.name" label="Nombre del tutor/a" required :error="inviteForm.errors.name" />
                        <FormField v-model="inviteForm.email" type="email" label="Correo electrónico" required :error="inviteForm.errors.email" />
                        <button
                            type="submit"
                            class="rounded-md border border-brand-600 px-4 py-2 text-sm font-semibold text-brand-700 hover:bg-brand-50 disabled:opacity-50"
                            :disabled="inviteForm.processing"
                        >
                            Generar acceso
                        </button>
                    </form>
                </section>
            </div>
        </div>
    </AppLayout>
</template>
