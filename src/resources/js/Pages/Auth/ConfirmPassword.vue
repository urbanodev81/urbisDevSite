<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Confirmar senha" />

        <h1 class="text-lg font-semibold text-fg">Confirmar senha</h1>
        <p class="mt-1 text-sm text-fg-subtle">
            Esta é uma área protegida. Confirme sua senha para continuar.
        </p>

        <form class="mt-6" @submit.prevent="submit">
            <div>
                <InputLabel for="password" value="Senha" />
                <TextInput
                    id="password"
                    v-model="form.password"
                    type="password"
                    class="mt-1 block w-full"
                    required
                    autofocus
                    autocomplete="current-password"
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <PrimaryButton
                class="mt-6 w-full justify-center"
                :class="{ 'opacity-25': form.processing }"
                :disabled="form.processing"
            >
                Confirmar
            </PrimaryButton>
        </form>
    </GuestLayout>
</template>
