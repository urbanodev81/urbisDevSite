<script setup>
import { computed, ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Captcha from '@/Components/CaptchaWidget.vue';

defineProps({
    canResetPassword: { type: Boolean },
    status: { type: String },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
    captcha_token: '',
});

const captchaRef = ref(null);

// O botão fica bloqueado até o captcha verificar.
const captchaOk = computed(() => !! form.captcha_token);

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
        onError: () => {
            // O desafio é de uso único: depois de uma recusa o mesmo token não
            // vale de novo, e a segunda tentativa falharia por um motivo que
            // não tem nada a ver com a senha.
            captchaRef.value?.reset?.();
            form.captcha_token = '';
        },
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Entrar" />

        <h1 class="text-lg font-semibold text-fg">Entrar no painel</h1>
        <p class="mt-1 text-sm text-fg-subtle">
            Blog, contatos e o conteúdo do site.
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

            <div class="mt-4">
                <InputLabel for="password" value="Senha" />
                <TextInput
                    id="password"
                    v-model="form.password"
                    type="password"
                    class="mt-1 block w-full"
                    required
                    autocomplete="current-password"
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4 flex items-center justify-between gap-4">
                <label class="flex items-center">
                    <Checkbox v-model:checked="form.remember" name="remember" />
                    <span class="ms-2 text-sm text-fg-muted">
                        Continuar conectado
                    </span>
                </label>

                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="rounded-md text-sm text-fg-muted underline hover:text-fg"
                >
                    Esqueci a senha
                </Link>
            </div>

            <div class="mt-6 flex justify-center">
                <Captcha ref="captchaRef" v-model="form.captcha_token" />
            </div>
            <InputError class="mt-2 text-center" :message="form.errors.captcha_token" />

            <PrimaryButton
                class="mt-6 w-full justify-center"
                :class="{ 'opacity-25': form.processing || !captchaOk }"
                :disabled="form.processing || !captchaOk"
            >
                Entrar
            </PrimaryButton>
        </form>
    </GuestLayout>
</template>
