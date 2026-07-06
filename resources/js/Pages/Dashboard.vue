<script setup>
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import { useAuth } from '@/composables/useAuth'

const { user } = useAuth()
</script>

<template>
    <Head title="Inicio" />

    <AppLayout>
        <PageHeader
            title="Panel de control"
            :subtitle="`Hola, ${user?.name}. Bienvenido/a a la gestión del grupo.`"
        />

        <div class="rounded-lg border border-dashed border-slate-300 bg-white p-8 text-center text-slate-500">
            <p class="text-sm">
                El panel con el semáforo documental, próximos eventos y alertas se completará
                en la Fase 2 (Agente H — Dashboard).
            </p>
        </div>
    </AppLayout>
</template>
