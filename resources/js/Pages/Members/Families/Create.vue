<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import FormField from '@/Components/Shared/FormField.vue'
import { useToast } from '@/composables/useToast'

const toast = useToast()

const form = useForm({
    name: '',
    contact_phone: '',
    contact_email: '',
    notes: '',
})

function submit() {
    form.post(route('families.store'), {
        onSuccess: () => toast.success('Familia creada.'),
    })
}
</script>

<template>
    <Head title="Nueva familia" />
    <AppLayout>
        <PageHeader title="Nueva familia" />

        <form class="max-w-xl space-y-4 rounded-lg border border-slate-200 bg-white p-6" @submit.prevent="submit">
            <FormField v-model="form.name" label="Nombre de familia" required :error="form.errors.name" />
            <FormField v-model="form.contact_phone" label="Teléfono de contacto" :error="form.errors.contact_phone" />
            <FormField v-model="form.contact_email" type="email" label="Correo de contacto" :error="form.errors.contact_email" />
            <FormField v-model="form.notes" type="textarea" label="Notas" :error="form.errors.notes" />

            <div class="flex justify-end gap-2 pt-2">
                <Link :href="route('families.index')" class="rounded-md px-4 py-2 text-sm text-slate-600 hover:bg-slate-100">
                    Cancelar
                </Link>
                <button
                    type="submit"
                    class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700 disabled:opacity-50"
                    :disabled="form.processing"
                >
                    Crear familia
                </button>
            </div>
        </form>
    </AppLayout>
</template>
