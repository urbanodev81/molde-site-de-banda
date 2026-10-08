import { usePage } from '@inertiajs/vue3';

export function configCaptcha() {
    return usePage().props.captcha ?? { driver: 'null' };
}

export function captchaAtivo() {
    return configCaptcha().driver !== 'null';
}
