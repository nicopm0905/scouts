<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import EventForm from './Partials/EventForm.vue'
import { useToast } from '@/composables/useToast'

const props = defineProps({
    event: { type: Object, required: true },
    branchOptions: { type: Array, required: true },
    typeOptions: { type: Array, required: true },
})

const toast = useToast()

const form = useForm({
    title: props.event.title,
    type: props.event.type,
    start_at: props.event.start_at?.slice(0, 16),
    end_at: props.event.end_at?.slice(0, 16) ?? '',
    location: props.event.location ?? '',
    description: props.event.description ?? '',
    branches: [...(props.event.branches ?? [])],
})

function submit() {
    form.put(route('events.update', props.event.id), {
        onSuccess: () => toast.success('Evento actualizado correctamente.'),
    })
}
</script>

<template>
    <Head title="Editar evento" />

    <AppLayout>
        <PageHeader title="Editar evento" :subtitle="event.title">
            <template #actions>
                <Link :href="route('events.show', event.id)" class="text-sm text-slate-500 hover:text-slate-700">
                    ← Volver al evento
                </Link>
            </template>
        </PageHeader>

        <form @submit.prevent="submit" class="max-w-2xl space-y-6 rounded-lg border border-slate-200 bg-white p-6">
            <EventForm :form="form" :branch-options="branchOptions" :type-options="typeOptions" />

            <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                <Link :href="route('events.show', event.id)" class="rounded-md px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">
                    Cancelar
                </Link>
                <button
                    type="submit"
                    class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-50"
                    :disabled="form.processing"
                >
                    Guardar cambios
                </button>
            </div>
        </form>
    </AppLayout>
</template>
