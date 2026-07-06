<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import MemberForm from '@/Components/Members/MemberForm.vue'
import { useToast } from '@/composables/useToast'

const props = defineProps({
    member: { type: Object, required: true },
    branches: { type: Array, default: () => [] },
})

const toast = useToast()

const form = useForm({
    first_name: props.member.first_name,
    last_name: props.member.last_name,
    phone: props.member.phone,
    email: props.member.email,
    role: props.member.role,
    birth_date: props.member.birth_date,
    joined_at: props.member.joined_at,
    active: props.member.active,
    notes: props.member.notes,
})

function submit() {
    form.put(route('members.update', props.member.id), {
        onSuccess: () => toast.success('Miembro actualizado.'),
    })
}
</script>

<template>
    <Head :title="`Editar ${member.full_name}`" />
    <AppLayout>
        <PageHeader :title="`Editar ${member.full_name}`" />

        <form class="max-w-2xl space-y-4 rounded-lg border border-slate-200 bg-white p-6" @submit.prevent="submit">
            <MemberForm :form="form" :branches="branches" />

            <div class="flex justify-end gap-2 pt-2">
                <Link
                    :href="route('members.show', member.id)"
                    class="rounded-md px-4 py-2 text-sm text-slate-600 hover:bg-slate-100"
                >
                    Cancelar
                </Link>
                <button
                    type="submit"
                    class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700 disabled:opacity-50"
                    :disabled="form.processing"
                >
                    Guardar cambios
                </button>
            </div>
        </form>
    </AppLayout>
</template>
