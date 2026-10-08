<script setup>
import { ArchiveRestore } from 'lucide-vue-next';
import SecondaryButton from './SecondaryButton.vue';

defineProps({
    lote: { type: Object, required: true },
    id: { type: [String, Number], required: true },
});
</script>

<template>
    <SecondaryButton type="button" @click="lote.emLote('restaurar', [id])">
        <ArchiveRestore class="h-4 w-4" aria-hidden="true" />Restaurar
    </SecondaryButton>
</template>
