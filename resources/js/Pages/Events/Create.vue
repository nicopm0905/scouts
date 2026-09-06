<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import EventForm from './Partials/EventForm.vue'
import { useToast } from '@/composables/useToast'

const props = defineProps({
    branchOptions: { type: Array, required: true },
    typeOptions: { type: Array, required: true },
})

const toast = useToast()

const form = useForm({
    title: '',
    type: '',
    start_at: '',
    end_at: '',
    location: '',
    city: '',
    description: '',
    theme: '',
    coordinator: '',
    eucharist: false,
    hike: false,
    branches: [],
})

function submit() {
    form.post(route('events.store'), {
        onSuccess: () => toast.success('Evento creado correctamente.'),
    })
}
</script>

<template>
    <Head title="Nuevo evento" />

    <AppLayout>
        <PageHeader title="Nuevo evento" subtitle="Añade un evento al calendario del grupo." icon="calendarPlus">
            <template #actions>
                <Link :href="route('events.index')" class="text-sm text-slate-500 hover:text-slate-700">
                    ← Volver al calendario
                </Link>
            </template>
        </PageHeader>

        <form @submit.prevent="submit" class="max-w-2xl space-y-6 rounded-lg border border-slate-200 bg-white p-6">
            <EventForm :form="form" :branch-options="branchOptions" :type-options="typeOptions" />

            <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                <Link :href="route('events.index')" class="rounded-md px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">
                    Cancelar
                </Link>
                <button
                    type="submit"
                    class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700 disabled:opacity-50"
                    :disabled="form.processing"
                >
                    Crear evento
                </button>
            </div>
        </form>
    </AppLayout>
</template>
