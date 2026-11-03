<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

/*
 * A saída de quem perdeu o celular e os códigos de recuperação. Pede a senha
 * de quem está desligando, não a da conta: é essa pessoa que responde pelo ato.
 */
const props = defineProps({
    usuario: { type: Object, required: true },
});

const form = useForm({ current_password: '' });
const pedindoSenha = ref(false);

const desligar = () =>
    form.delete(route('painel.usuarios.dois-fatores.desligar', props.usuario.uuid), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });

const voltar = () => {
    form.reset();
    form.clearErrors();
    pedindoSenha.value = false;
};
</script>

<template>
    <section
        class="rounded-lg border border-line bg-surface p-5"
    >
        <h2 class="mb-4 font-display text-xl font-bold uppercase text-fg">
            Verificação em duas etapas
        </h2>
        <p class="text-sm text-fg-muted">
            Esta conta entra com senha e código do celular. Desligue só se a pessoa ficou sem o
            celular e sem os códigos de recuperação; ela volta a entrar apenas com a senha e
            pode ativar de novo em Minha conta.
        </p>

        <SecondaryButton v-if="!pedindoSenha" class="mt-4" @click="pedindoSenha = true">
            Desligar a verificação desta conta
        </SecondaryButton>

        <form v-else class="mt-4 max-w-sm" @submit.prevent="desligar">
            <InputLabel for="desligar_2fa_senha" value="A sua senha, para confirmar" />
            <TextInput
                id="desligar_2fa_senha"
                v-model="form.current_password"
                type="password"
                class="mt-1 block w-full"
                required
                autocomplete="current-password"
            />
            <InputError class="mt-2" :message="form.errors.current_password" />

            <div class="mt-4 flex items-center gap-3">
                <DangerButton :disabled="form.processing">Desligar</DangerButton>
                <SecondaryButton @click="voltar">Voltar</SecondaryButton>
            </div>
        </form>
    </section>
</template>
