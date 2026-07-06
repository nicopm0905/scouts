<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import { useToast } from '@/composables/useToast'

const toast = useToast()

const form = useForm({ file: null })

function submit() {
    form.post(route('members.import.store'), {
        forceFormData: true,
        onSuccess: () => {
            form.reset()
            toast.success('Importación procesada.')
        },
    })
}
</script>

<template>
    <Head title="Importar miembros" />
    <AppLayout>
        <PageHeader title="Importar miembros" subtitle="Sube un fichero CSV con los miembros a dar de alta." />

        <div class="max-w-xl space-y-4 rounded-lg border border-slate-200 bg-white p-6">
            <p class="text-sm text-slate-600">
                El fichero debe tener cabecera:
                <code class="rounded bg-slate-100 px-1 py-0.5 text-xs">
                    first_name,last_name,phone,email,role,birth_date,joined_at,active,notes
                </code>
            </p>

            <a
                :href="route('members.import.template')"
                class="inline-block rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
            >
                Descargar plantilla
            </a>

            <form class="space-y-3" @submit.prevent="submit">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Fichero CSV</label>
                    <input
                        type="file"
                        accept=".csv,text/csv"
                        class="mt-1 block w-full text-sm"
                        @change="form.file = $event.target.files[0]"
                    />
                    <p v-if="form.errors.file" class="mt-1 text-xs text-red-600">{{ form.errors.file }}</p>
                </div>
                <button
                    type="submit"
                    class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700 disabled:opacity-50"
                    :disabled="form.processing || !form.file"
                >
                    Importar
                </button>
            </form>
        </div>
    </AppLayout>
</template>
