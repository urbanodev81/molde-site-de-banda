import base from './tailwind.config.js';

export default {
    ...base,

    content: [
        './resources/js/Components/AcessibilidadeWidget.vue',
        './resources/js/Components/VLibrasWidget.vue',
        './resources/js/Components/AnimacoesModal.vue',
        './resources/js/Composables/useAcessibilidade.js',
    ],

    corePlugins: {

        preflight: false,
    },

    plugins: [],
};
