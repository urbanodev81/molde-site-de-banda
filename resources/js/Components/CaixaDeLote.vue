<script setup>
import Checkbox from './Checkbox.vue';

defineProps({
    lote: { type: Object, required: true },
    id: { type: [String, Number], required: true },

    rotulo: { type: String, default: '' },
});
</script>

<template>
    <label class="flex h-11 w-11 shrink-0 cursor-pointer items-center justify-center">
        <Checkbox :checked="lote.selecao.marcado(id)" @update:checked="lote.selecao.alternar(id)" />
        <span class="sr-only">Marcar {{ rotulo }}</span>
    </label>
</template>
