<script setup>
import { computed } from 'vue'
import { Head, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PortalLayout from '@/Layouts/PortalLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import DeleteUserForm from './Partials/DeleteUserForm.vue'
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue'
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue'

defineProps({
    mustVerifyEmail: { type: Boolean },
    status: { type: String },
})

// El portal de familias y el panel de gestión usan layouts distintos.
const isFamilia = computed(() => usePage().props.auth?.user?.is_familia === true)
const Layout = computed(() => (isFamilia.value ? PortalLayout : AppLayout))
</script>

<template>
    <Head title="Mi cuenta" />

    <component :is="Layout">
        <PageHeader v-if="!isFamilia" title="Mi cuenta" icon="cog" />
        <h1 v-else class="text-2xl font-extrabold tracking-tight text-slate-900">Mi cuenta</h1>

        <div class="mt-6 space-y-6">
            <div class="rounded-lg border border-slate-200 bg-white p-6">
                <UpdateProfileInformationForm
                    :must-verify-email="mustVerifyEmail"
                    :status="status"
                    class="max-w-xl"
                />
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-6">
                <UpdatePasswordForm class="max-w-xl" />
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-6">
                <DeleteUserForm class="max-w-xl" />
            </div>
        </div>
    </component>
</template>
