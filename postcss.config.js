export default (ctx = {}) => ({
    plugins: {
        tailwindcss: {
            config: String(ctx.file ?? '').includes('a11y.css')
                ? './tailwind.a11y.config.js'
                : './tailwind.config.js',
        },
        autoprefixer: {},
    },
});
