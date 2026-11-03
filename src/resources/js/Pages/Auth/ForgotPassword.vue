<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Captcha from '@/Components/CaptchaWidget.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    status: { type: String },
});

const form = useForm({
    email: '',
    captcha_token: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <GuestLayout>
        <Head title="Esqueci a senha" />

        <h1 class="text-lg font-semibold text-fg">Esqueci a senha</h1>
        <p class="mt-1 text-sm text-fg-subtle">
            Informe o e-mail cadastrado e enviaremos um link para você definir uma nova senha.
        </p>

        <div
            v-if="status"
            class="mt-4 rounded-md border border-success/30 bg-success-subtle px-3 py-2 text-sm font-medium text-success"
        >
            {{ status }}
        </div>

        <form class="mt-6" @submit.prevent="submit">
            <div>
                <InputLabel for="email" value="E-mail" />
                <TextInput
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="mt-1 block w-full"
                    required
                    autofocus
                    autocomplete="username"
                />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="flex justify-center">
                <Captcha v-model="form.captcha_token" />
            </div>
            <InputError class="text-center" :message="form.errors.captcha_token" />

            <PrimaryButton
                class="mt-6 w-full justify-center"
                :class="{ 'opacity-25': form.processing }"
                :disabled="form.processing"
            >
                Enviar link de redefinição
            </PrimaryButton>
        </form>

        <div class="mt-4 text-center">
            <Link
                :href="route('login')"
                class="rounded-md text-sm text-fg-muted underline hover:text-fg"
            >
                Voltar para o login
            </Link>
        </div>
    </GuestLayout>
</template>
