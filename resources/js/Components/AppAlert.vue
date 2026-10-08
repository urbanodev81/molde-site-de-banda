<script setup>
import { CheckCircle2, AlertTriangle, X } from 'lucide-vue-next';

defineProps({
    message: { type: String, default: null },
    type: { type: String, default: 'success' },
    show: Boolean,
});

defineEmits(['close']);
</script>

<template>
    <Transition
        enter-active-class="transition duration-200"
        enter-from-class="translate-y-2 opacity-0"
        leave-active-class="transition duration-150"
        leave-to-class="opacity-0"
    >
        <div
            v-if="show"
            role="status"
            aria-live="polite"
            class="fixed bottom-5 left-1/2 z-[120] w-[min(28rem,calc(100vw-2rem))] -translate-x-1/2
                   rounded-lg border p-4 shadow-lg sm:bottom-auto sm:left-auto sm:right-5 sm:top-5 sm:translate-x-0"
            :class="type === 'success'
                ? 'border-success bg-success-subtle text-success'
                : 'border-danger bg-danger-subtle text-danger'"
        >
            <div class="flex items-start gap-3">
                <component
                    :is="type === 'success' ? CheckCircle2 : AlertTriangle"
                    class="mt-0.5 h-5 w-5 shrink-0"
                    aria-hidden="true"
                />
                <p class="flex-1 text-sm font-medium">{{ message }}</p>
                <button
                    type="button"
                    class="rounded p-1 opacity-70 transition hover:opacity-100"
                    aria-label="Fechar aviso"
                    @click="$emit('close')"
                >
                    <X class="h-4 w-4" aria-hidden="true" />
                </button>
            </div>
        </div>
    </Transition>
</template>
