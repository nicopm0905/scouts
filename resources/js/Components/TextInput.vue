<script setup>
import { onMounted, ref } from 'vue';

const model = defineModel({
    type: String,
    required: true,
});

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        class="rounded-xl border-slate-200 bg-slate-50/50 px-4 py-2.5 shadow-sm transition-all focus:border-brand-500 focus:bg-white focus:ring-brand-500"
        v-model="model"
        ref="input"
    />
</template>
