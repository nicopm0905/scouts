<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import FormField from '@/Components/Shared/FormField.vue'
import ConfirmButton from '@/Components/Shared/ConfirmButton.vue'
import { useToast } from '@/composables/useToast'

const props = defineProps({
    family: { type: Object, required: true },
})

const toast = useToast()

const form = useForm({
    name: props.family.name,
    contact_phone: props.family.contact_phone,
    contact_email: props.family.contact_email,
    notes: props.family.notes,
})

function submit() {
    form.put(route('families.update', props.family.id), {
        onSuccess: () => toast.success('Familia actualizada.'),
    })
}

function destroy() {
    form.delete(route('families.destroy', props.family.id))
}
</script>

<template>
    <Head :title="family.name" />
    <AppLayout>
        <PageHeader :title="family.name">
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
                    class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-50"
                    :disabled="form.processing"
                >
                    Guardar cambios
                </button>
            </form>

            <section class="rounded-lg border border-slate-200 bg-white p-6">
                <h2 class="mb-4 text-lg font-semibold text-slate-800">Miembros vinculados</h2>
                <ul v-if="family.members.length" class="space-y-2 text-sm">
                    <li v-for="m in family.members" :key="m.id" class="flex items-center justify-between">
                        <Link :href="route('members.show', m.id)" class="text-emerald-700 hover:underline">{{ m.full_name }}</Link>
                        <span class="text-slate-400">{{ m.relationship }}</span>
                    </li>
                </ul>
                <p v-else class="text-sm text-slate-400">
                    Sin miembros vinculados. Ve a la ficha de un miembro para vincularlo a esta familia.
                </p>
            </section>
        </div>
    </AppLayout>
</template>
