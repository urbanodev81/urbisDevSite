<script setup>
import { computed, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    enabled: { type: Boolean, default: false },
    // Administrador: as outras telas ficam fechadas até ativar (middleware
    // ExigirSegundoFator).
    required: { type: Boolean, default: false },
    pending: { type: Object, default: null },
    confirmedAt: { type: String, default: null },
    recoveryCodesLeft: { type: Number, default: 0 },
    // Texto puro, só na resposta seguinte à confirmação — no banco só existe
    // o hash, então não há como reexibir depois.
    recoveryCodes: { type: Array, default: null },
});

const enableForm = useForm({});
const confirmForm = useForm({ code: '' });
const disableForm = useForm({ current_password: '' });
const pedindoSenha = ref(false);
const copiado = ref(false);

const poucosCodigos = computed(() => props.enabled && props.recoveryCodesLeft <= 2);

const ativar = () => enableForm.post(route('two-factor.enable'), { preserveScroll: true });

const confirmar = () =>
    confirmForm.post(route('two-factor.confirm'), {
        preserveScroll: true,
        onSuccess: () => confirmForm.reset(),
    });

const desativar = () =>
    disableForm.delete(route('two-factor.disable'), {
        preserveScroll: true,
        onSuccess: () => {
            disableForm.reset();
            pedindoSenha.value = false;
        },
    });

const cancelar = () => {
    disableForm.reset();
    disableForm.clearErrors();
    pedindoSenha.value = false;
};

async function copiarCodigos() {
    await navigator.clipboard?.writeText((props.recoveryCodes ?? []).join('\n'));
    copiado.value = true;
}
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-fg">
                Verificação em duas etapas
            </h2>

            <p class="mt-1 text-sm text-fg-subtle">
                Além da senha, o login pede um código do seu celular. Quem descobrir a sua
                senha não entra sem ele.
            </p>
        </header>

        <!-- Códigos de recuperação recém-gerados: aparecem UMA vez só. -->
        <div
            v-if="recoveryCodes?.length"
            role="status"
            class="mt-6 rounded-md border border-line bg-surface-sunken p-4"
        >
            <p class="text-sm font-semibold text-fg">
                Guarde estes códigos de recuperação agora
            </p>
            <p class="mt-1 text-sm text-fg-muted">
                Eles não serão mostrados de novo. Cada um funciona uma única vez e é a sua
                entrada se você ficar sem o celular.
            </p>
            <ul
                class="mt-3 grid grid-cols-2 gap-1 font-mono text-sm text-fg"
            >
                <li v-for="codigo in recoveryCodes" :key="codigo">{{ codigo }}</li>
            </ul>
            <div class="mt-3 flex items-center gap-3">
                <SecondaryButton type="button" @click="copiarCodigos">Copiar códigos</SecondaryButton>
                <span v-if="copiado" class="text-sm text-fg-subtle">Copiado.</span>
            </div>
        </div>

        <!-- Estado 1: desligada e sem ativação em curso -->
        <div v-if="!enabled && !pending" class="mt-6">
            <p class="text-sm text-fg-muted">
                Situação:
                <span class="font-semibold text-fg">desativada</span>
            </p>
            <p
                v-if="required"
                role="alert"
                class="mt-3 rounded-md border border-line bg-surface-sunken p-3 text-sm text-fg"
            >
                A conta de administrador precisa da verificação em duas etapas. As outras
                telas ficam fechadas até você ativar.
            </p>
            <PrimaryButton
                class="mt-4"
                type="button"
                :disabled="enableForm.processing"
                @click="ativar"
            >
                Ativar verificação em duas etapas
            </PrimaryButton>
            <InputError class="mt-2" :message="enableForm.errors.code" />
        </div>

        <!-- Estado 2: ativação em curso (segredo gerado, falta confirmar) -->
        <div v-else-if="pending" class="mt-6">
            <p class="text-sm text-fg-muted">
                Leia o QR code no seu aplicativo autenticador (Google Authenticator, Microsoft
                Authenticator, Authy, 1Password) e digite o código que ele mostrar. A
                verificação só passa a valer depois dessa confirmação.
            </p>

            <div class="mt-4 flex flex-wrap items-start gap-6">
                <!-- Fundo branco fixo nos dois temas: leitor de QR precisa do
                     contraste, e o SVG vem do servidor com traço preto. -->
                <div
                    class="rounded-md border border-line bg-white p-2"
                    role="img"
                    aria-label="QR code para o aplicativo autenticador"
                    v-html="pending.qrSvg"
                ></div>

                <div class="min-w-0 text-sm">
                    <p class="text-fg-muted">
                        Não consegue ler o QR? Digite esta chave no aplicativo:
                    </p>
                    <p class="mt-1 break-all font-mono text-fg">
                        {{ pending.secret }}
                    </p>
                </div>
            </div>

            <form class="mt-6" @submit.prevent="confirmar">
                <InputLabel for="two_factor_code" value="Código do aplicativo" />
                <TextInput
                    id="two_factor_code"
                    v-model="confirmForm.code"
                    type="text"
                    inputmode="numeric"
                    maxlength="6"
                    class="mt-1 block w-40 text-center tracking-widest"
                    required
                    autocomplete="one-time-code"
                />
                <InputError class="mt-2" :message="confirmForm.errors.code" />

                <PrimaryButton class="mt-4" :disabled="confirmForm.processing">
                    Confirmar e ativar
                </PrimaryButton>
            </form>
        </div>

        <!-- Estado 3: ativa -->
        <div v-else class="mt-6">
            <p class="text-sm text-fg-muted">
                Situação:
                <span class="font-semibold text-fg">ativa</span>
                <span v-if="confirmedAt"> desde {{ confirmedAt }}</span>
            </p>
            <p class="mt-1 text-sm text-fg-muted">
                {{ recoveryCodesLeft }}
                {{ recoveryCodesLeft === 1 ? 'código de recuperação sobrando' : 'códigos de recuperação sobrando' }}.
                <span v-if="poucosCodigos" class="font-semibold text-fg">
                    Estão acabando: desative e ative de novo para gerar outros oito.
                </span>
            </p>
        </div>

        <!-- Desativar (ativa) ou desistir (ativação em curso): os dois pedem a senha. -->
        <div v-if="enabled || pending" class="mt-6">
            <SecondaryButton v-if="!pedindoSenha" type="button" @click="pedindoSenha = true">
                {{ enabled ? 'Desativar' : 'Cancelar ativação' }}
            </SecondaryButton>

            <form v-else @submit.prevent="desativar">
                <InputLabel
                    for="two_factor_senha"
                    :value="enabled ? 'Senha atual, para desativar' : 'Senha atual, para cancelar'"
                />
                <TextInput
                    id="two_factor_senha"
                    v-model="disableForm.current_password"
                    type="password"
                    class="mt-1 block w-full"
                    required
                    autocomplete="current-password"
                />
                <InputError class="mt-2" :message="disableForm.errors.current_password" />

                <div class="mt-4 flex items-center gap-3">
                    <DangerButton :disabled="disableForm.processing">
                        {{ enabled ? 'Desativar' : 'Cancelar ativação' }}
                    </DangerButton>
                    <SecondaryButton type="button" @click="cancelar">Voltar</SecondaryButton>
                </div>
            </form>
        </div>
    </section>
</template>
