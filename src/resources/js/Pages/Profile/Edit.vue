<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AparelhosConfiaveis from './Partials/AparelhosConfiaveis.vue';
import TwoFactorForm from './Partials/TwoFactorForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';

defineProps({
    mustVerifyEmail: { type: Boolean },
    status: { type: String },
    twoFactorEnabled: { type: Boolean, default: false },
    twoFactorRequired: { type: Boolean, default: false },
    twoFactorPending: { type: Object, default: null },
    twoFactorConfirmedAt: { type: String, default: null },
    twoFactorRecoveryCodesLeft: { type: Number, default: 0 },
    twoFactorRecoveryCodes: { type: Array, default: null },
    aparelhosConfiaveis: { type: Array, default: () => [] },
});
</script>

<template>
    <Head title="Minha conta" />

    <AuthenticatedLayout>
        <template #cabecalho>
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

            <!-- Colado na senha: os dois são o que protege a entrada. -->
            <section
                class="rounded-card border border-line bg-surface p-6"
            >
                <TwoFactorForm
                    class="max-w-xl"
                    :enabled="twoFactorEnabled"
                    :required="twoFactorRequired"
                    :pending="twoFactorPending"
                    :confirmed-at="twoFactorConfirmedAt"
                    :recovery-codes-left="twoFactorRecoveryCodesLeft"
                    :recovery-codes="twoFactorRecoveryCodes"
                />
            </section>

            <!-- Só para quem tem o segundo fator ligado: sem ele não há código
                 para dispensar, e a lista seria sempre vazia. -->
            <section v-if="twoFactorEnabled" class="rounded-card border border-line bg-surface p-6">
                <AparelhosConfiaveis class="max-w-xl" :aparelhos="aparelhosConfiaveis" />
            </section>

        </div>
    </AuthenticatedLayout>
</template>
