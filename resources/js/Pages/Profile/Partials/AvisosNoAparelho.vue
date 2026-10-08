<script setup>
import { usePage } from '@inertiajs/vue3';
import { Bell, BellOff, Smartphone } from 'lucide-vue-next';
import { computed, onMounted } from 'vue';
import { ativar, desativar, estadoPush, sincronizarEstado } from '@/push';

const page = usePage();

const chavePublica = computed(() => page.props.push?.chave_publica ?? null);

const disponivel = computed(() => Boolean(chavePublica.value) && estadoPush.suportado);

onMounted(sincronizarEstado);

async function alternar() {
    if (estadoPush.inscrito) {
        await desativar();
        return;
    }

    await ativar(chavePublica.value);
}
</script>

<template>
    <section v-if="disponivel || estadoPush.precisaInstalarNoIos">
        <header>
            <h2 class="text-base font-semibold text-fg">
                Avisos neste aparelho
            </h2>
            <p class="mt-1 text-sm text-fg-muted">
                Os avisos que você já recebe por e-mail passam a chegar também como notificação
                do sistema, mesmo com o painel fechado. O aviso traz só o assunto; o conteúdo fica
                atrás do clique. Vale só para este aparelho.
            </p>
        </header>

        <div
            v-if="estadoPush.precisaInstalarNoIos"
            class="mt-4 flex items-start gap-3 rounded-lg border border-line bg-bg px-4 py-3 /40"
        >
            <Smartphone class="mt-0.5 h-5 w-5 flex-none text-fg-subtle" aria-hidden="true" />
            <p class="text-sm text-fg-muted">
                <span class="block font-semibold text-fg">Instale o aplicativo primeiro</span>
                No iPhone os avisos só chegam pelo aplicativo adicionado à tela de início.
            </p>
        </div>

        <div
            v-else-if="estadoPush.permissao === 'denied'"
            class="mt-4 flex items-start gap-3 rounded-lg border border-line bg-bg px-4 py-3 /40"
        >
            <BellOff class="mt-0.5 h-5 w-5 flex-none text-fg-subtle" aria-hidden="true" />
            <p class="text-sm text-fg-muted">
                <span class="block font-semibold text-fg">Avisos bloqueados</span>
                Você negou a permissão para este endereço. Para voltar atrás é preciso liberar nas
                configurações do navegador — daqui não dá para perguntar de novo.
            </p>
        </div>

        <button
            v-else
            type="button"
            :disabled="estadoPush.ocupado"
            :aria-pressed="estadoPush.inscrito"
            class="mt-4 flex w-full items-center gap-3 rounded-lg border border-line px-4 py-3 text-left transition hover:bg-bg disabled:opacity-50 dark:hover:bg-accent-hover/40"
            @click="alternar"
        >
            <component
                :is="estadoPush.inscrito ? BellOff : Bell"
                class="h-5 w-5 flex-none"
                :class="estadoPush.inscrito ? 'text-fg-subtle' : 'text-fg-muted '"
                aria-hidden="true"
            />
            <span class="min-w-0 flex-1 text-sm">
                <span class="block font-semibold text-fg">
                    {{ estadoPush.inscrito ? 'Desligar avisos neste aparelho' : 'Receber avisos neste aparelho' }}
                </span>
                <span class="text-fg-muted">
                    {{
                        estadoPush.inscrito
                            ? 'Este aparelho para de receber. Os outros continuam.'
                            : 'Chegam mesmo com o sistema fechado, só neste aparelho.'
                    }}
                </span>
            </span>
        </button>
    </section>
</template>
