<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    sugestaoDeApelido: { type: String, default: '' },
    diasDeConfianca: { type: Number, default: 30 },
});

const form = useForm({
    code: '',
    confiar: false,
    apelido: props.sugestaoDeApelido,
});

const submit = () => {
    form.post(route('two-factor.challenge'), {
        onFinish: () => form.reset('code'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Verificação em duas etapas" />

        <h1 class="text-lg font-semibold text-fg">
            Verificação em duas etapas
        </h1>
        <p class="mt-1 text-sm text-fg-subtle">
            Digite o código de 6 dígitos do seu aplicativo autenticador. Sem o celular por
            perto? Use um dos códigos de recuperação que você guardou ao ativar.
        </p>

        <form class="mt-6" @submit.prevent="submit">
            <div>
                <InputLabel for="code" value="Código" />
                <!-- Um campo só para os dois formatos: o de recuperação
                     (xxxx-xxxx) é o que se tem em mãos quando o aplicativo não
                     está acessível, e mandar procurar outra tela nessa hora
                     seria hostil. -->
                <TextInput
                    id="code"
                    v-model="form.code"
                    type="text"
                    inputmode="text"
                    maxlength="12"
                    class="mt-1 block w-full text-center text-lg tracking-widest"
                    required
                    autofocus
                    autocomplete="one-time-code"
                    autocapitalize="none"
                    spellcheck="false"
                />
                <InputError class="mt-2" :message="form.errors.code" />
            </div>

            <!-- Desmarcado por padrão: confiar é escolha de quem está neste
                 aparelho agora, nunca o caminho de menor esforço. -->
            <div class="mt-5">
                <label class="flex min-h-11 items-center gap-3">
                    <Checkbox v-model:checked="form.confiar" name="confiar" />
                    <span class="text-sm text-fg">Confiar neste aparelho por {{ diasDeConfianca }} dias</span>
                </label>
                <p class="ml-7 text-xs text-fg-subtle">
                    Nele, o acesso passa a pedir só a senha. Não marque em computador que outras pessoas usam.
                </p>

                <div v-if="form.confiar" class="mt-3">
                    <InputLabel for="apelido" value="Apelido do aparelho" />
                    <TextInput
                        id="apelido"
                        v-model="form.apelido"
                        type="text"
                        maxlength="60"
                        class="mt-1 block w-full"
                        placeholder="Notebook de casa"
                        required
                    />
                    <p class="mt-1 text-xs text-fg-subtle">
                        É o nome que aparece em Minha conta, para você reconhecer e remover depois.
                    </p>
                    <InputError class="mt-2" :message="form.errors.apelido" />
                </div>
            </div>

            <PrimaryButton
                class="mt-6 w-full justify-center"
                :class="{ 'opacity-25': form.processing }"
                :disabled="form.processing"
            >
                Confirmar
            </PrimaryButton>
        </form>

        <p class="mt-6 text-center text-sm">
            <Link
                :href="route('login')"
                class="rounded-md text-fg-muted underline hover:text-fg"
            >
                Voltar para o login
            </Link>
        </p>
    </GuestLayout>
</template>
