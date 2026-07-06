<script setup>
import FormField from '@/Components/Shared/FormField.vue'

const props = defineProps({
    form: { type: Object, required: true },
    branchOptions: { type: Array, required: true },
    typeOptions: { type: Array, required: true },
})

function toggleBranch(value) {
    const i = props.form.branches.indexOf(value)
    if (i === -1) props.form.branches.push(value)
    else props.form.branches.splice(i, 1)
}
</script>

<template>
    <div class="space-y-4">
        <FormField
            v-model="form.title"
            label="Título"
            required
            :error="form.errors.title"
        />

        <FormField
            v-model="form.type"
            label="Tipo de evento"
            type="select"
            :options="typeOptions"
            required
            :error="form.errors.type"
        />

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <FormField
                v-model="form.start_at"
                label="Inicio"
                type="datetime-local"
                required
                :error="form.errors.start_at"
            />
            <FormField
                v-model="form.end_at"
                label="Fin"
                type="datetime-local"
                :error="form.errors.end_at"
            />
        </div>

        <FormField
            v-model="form.location"
            label="Lugar"
            :error="form.errors.location"
        />

        <FormField
            v-model="form.description"
            label="Descripción"
            type="textarea"
            :error="form.errors.description"
        />

        <div>
            <label class="block text-sm font-medium text-slate-700">Ramas afectadas</label>
            <p class="text-xs text-slate-500">
                Si el evento requiere inscripción (salida, acampada, campamento), se generarán
                automáticamente las inscripciones de los miembros y responsables de estas ramas.
            </p>
            <div class="mt-2 flex flex-wrap gap-2">
                <button
                    v-for="b in branchOptions"
                    :key="b.value"
                    type="button"
                    class="rounded-full border px-3 py-1 text-sm"
                    :class="form.branches.includes(b.value)
                        ? 'border-brand-600 bg-brand-600 text-white'
                        : 'border-slate-300 text-slate-600 hover:bg-slate-50'"
                    @click="toggleBranch(b.value)"
                >
                    {{ b.label }}
                </button>
            </div>
        </div>
    </div>
</template>
