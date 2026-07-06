<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import FormField from '@/Components/Shared/FormField.vue'
import { useToast } from '@/composables/useToast'

const props = defineProps({
    settings: { type: Object, required: true },
})

const toast = useToast()

const form = useForm({
    group_name: props.settings.group_name ?? '',
    group_tax_id: props.settings.group_tax_id ?? '',
    group_address: props.settings.group_address ?? '',
    group_postal_code: props.settings.group_postal_code ?? '',
    group_city: props.settings.group_city ?? '',
    group_email: props.settings.group_email ?? '',
    group_phone: props.settings.group_phone ?? '',
    sibling_discount_percent: props.settings.sibling_discount_percent ?? 0,
    reminder_days_before: props.settings.reminder_days_before ?? 5,
})

function submit() {
    form.put(route('settings.update'), {
        onSuccess: () => toast.success('Ajustes guardados.'),
    })
}
</script>

<template>
    <Head title="Ajustes" />
    <AppLayout>
        <PageHeader title="Ajustes" subtitle="Datos fiscales del grupo, descuento por hermanos y recordatorios de pago." />

        <form class="max-w-2xl space-y-6" @submit.prevent="submit">
            <section class="rounded-lg border border-slate-200 bg-white p-4">
                <h2 class="mb-4 text-sm font-semibold text-slate-700">Datos fiscales del grupo</h2>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <FormField v-model="form.group_name" label="Nombre del grupo" required :error="form.errors.group_name" />
                    <FormField v-model="form.group_tax_id" label="NIF/CIF" :error="form.errors.group_tax_id" />
                    <FormField v-model="form.group_address" label="Dirección" :error="form.errors.group_address" />
                    <FormField v-model="form.group_postal_code" label="Código postal" :error="form.errors.group_postal_code" />
                    <FormField v-model="form.group_city" label="Ciudad" :error="form.errors.group_city" />
                    <FormField v-model="form.group_email" type="email" label="Email" :error="form.errors.group_email" />
                    <FormField v-model="form.group_phone" label="Teléfono" :error="form.errors.group_phone" />
                </div>
            </section>

            <section class="rounded-lg border border-slate-200 bg-white p-4">
                <h2 class="mb-4 text-sm font-semibold text-slate-700">Cobros</h2>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <FormField
                        v-model="form.sibling_discount_percent"
                        type="number"
                        label="% descuento a partir del 2º hermano"
                        hint="Se aplica a cuotas anuales/trimestrales con descuento por hermanos activado."
                        :error="form.errors.sibling_discount_percent"
                    />
                    <FormField
                        v-model="form.reminder_days_before"
                        type="number"
                        label="Días de antelación del recordatorio de pago"
                        hint="Además se envía un recordatorio el día del vencimiento."
                        :error="form.errors.reminder_days_before"
                    />
                </div>
            </section>

            <button
                type="submit"
                class="rounded-md bg-brand-600 px-5 py-2 text-sm font-semibold text-white hover:bg-brand-700 disabled:opacity-50"
                :disabled="form.processing"
            >
                Guardar ajustes
            </button>
        </form>
    </AppLayout>
</template>
