<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AvisosNoAparelho from './Partials/AvisosNoAparelho.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';

defineProps({
    mustVerifyEmail: { type: Boolean },
    status: { type: String },
});
</script>

<template>
    <Head title="Minha conta" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-lg font-semibold text-fg">Minha conta</h1>
        </template>

        <div class="mx-auto max-w-3xl space-y-4">
            <section
                class="rounded-card border border-line bg-surface p-6"
            >
                <UpdateProfileInformationForm
                    :must-verify-email="mustVerifyEmail"
                    :status="status"
                    class="max-w-xl"
                />
            </section>

            <section
                class="rounded-card border border-line bg-surface p-6"
            >
                <UpdatePasswordForm class="max-w-xl" />
            </section>

            <section
                class="rounded-card border border-line bg-surface p-6"
            >
                <AvisosNoAparelho class="max-w-xl" />
            </section>

            <section
                class="rounded-card border border-line bg-surface p-6"
            >
                <DeleteUserForm class="max-w-xl" />
            </section>
        </div>
    </AuthenticatedLayout>
</template>
