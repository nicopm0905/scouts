<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import MemberForm from '@/Components/Members/MemberForm.vue'
import { useToast } from '@/composables/useToast'

const props = defineProps({
    branches: { type: Array, default: () => [] },
})

const toast = useToast()

const form = useForm({
    first_name: '',
    last_name: '',
    phone: '',
    email: '',
    role: props.branches[0]?.value ?? '',
    birth_date: '',
    joined_at: '',
    active: true,
    notes: '',
})

function submit() {
    form.post(route('members.store'), {
        onSuccess: () => toast.success('Miembro creado.'),
    })
}
</script>

<template>
    <Head title="Nuevo miembro" />
    <AppLayout>
        <PageHeader title="Nuevo miembro" subtitle="Da de alta a un scout o responsable." icon="userPlus" />

        <form class="max-w-2xl space-y-4 rounded-lg border border-slate-200 bg-white p-6" @submit.prevent="submit">
            <MemberForm :form="form" :branches="branches" />

            <div class="flex justify-end gap-2 pt-2">
                <Link
                    :href="route('members.index')"
                    class="rounded-md px-4 py-2 text-sm text-slate-600 hover:bg-slate-100"
                >
                    Cancelar
                </Link>
                <button
                    type="submit"
                    class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700 disabled:opacity-50"
                    :disabled="form.processing"
                >
                    Crear miembro
                </button>
            </div>
        </form>
    </AppLayout>
</template>
