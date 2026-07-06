<script setup>
import FormField from '@/Components/Shared/FormField.vue'

const props = defineProps({
    form: { type: Object, required: true },
    branches: { type: Array, default: () => [] },
})
</script>

<template>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <FormField v-model="form.first_name" label="Nombre" required :error="form.errors.first_name" />
        <FormField v-model="form.last_name" label="Apellidos" required :error="form.errors.last_name" />
        <FormField v-model="form.phone" label="Teléfono" :error="form.errors.phone" />
        <FormField v-model="form.email" type="email" label="Correo electrónico" :error="form.errors.email" />
        <FormField
            v-model="form.role"
            type="select"
            label="Rama"
            required
            :options="branches"
            :error="form.errors.role"
        />
        <FormField v-model="form.birth_date" type="date" label="Fecha de nacimiento" :error="form.errors.birth_date" />
        <FormField v-model="form.joined_at" type="date" label="Fecha de alta" :error="form.errors.joined_at" />
        <FormField
            v-model="form.active"
            type="checkbox"
            placeholder="Miembro activo"
            :error="form.errors.active"
        />
    </div>
    <FormField v-model="form.notes" type="textarea" label="Notas" :error="form.errors.notes" />
</template>
