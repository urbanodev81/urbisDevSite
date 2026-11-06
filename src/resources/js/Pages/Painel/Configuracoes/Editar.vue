<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    grupos: { type: Object, required: true },
    rotulos: { type: Object, required: true },
});

/*
 * A tela inteira se desenha do catálogo do servidor (veio do Duo, 06/11/2026).
 * Nenhum campo é escrito à mão aqui: chave nova aparece sozinha, já validada.
 * Imagem não entra no formulário de texto: sobe na hora, pela própria rota.
 */
const valores = {};
for (const campos of Object.values(props.grupos)) {
    for (const campo of campos) {
        if (campo.tipo !== 'imagem') valores[campo.chave] = campo.valor ?? '';
    }
}

const form = useForm({ valores });
const abaAtiva = ref(Object.keys(props.grupos)[0]);

const salvar = () => form.put(route('painel.configuracoes.update'), { preserveScroll: true });

const imagem = useForm({ arquivo: null });
const enviarImagem = (chave, evento) => {
    imagem.arquivo = evento.target.files[0] ?? null;
    if (! imagem.arquivo) return;
    imagem.post(route('painel.configuracoes.imagem', chave), {
        preserveScroll: true,
        forceFormData: true,
        onFinish: () => { evento.target.value = ''; },
    });
};
const removerImagem = (chave) => router.post(route('painel.configuracoes.imagem', chave), { remover: true }, { preserveScroll: true });

const erro = (chave) => form.errors[`valores.${chave}`];
</script>

<template>
    <Head title="Configuração do site" />

    <AuthenticatedLayout>
        <template #cabecalho><p class="font-label text-label uppercase text-fg-subtle">Site</p></template>

        <PageHeader
            titulo="Configuração do site"
            descricao="Contato, redes, descrição da empresa e os textos da home. Salvou, já está no ar: confira em urbisdev.tech."
        />

        <div class="mb-5 flex flex-wrap gap-2" role="tablist" aria-label="Partes da configuração">
            <button
                v-for="(campos, grupo) in grupos"
                :key="grupo"
                type="button"
                role="tab"
                :aria-selected="abaAtiva === grupo"
                class="min-h-[44px] rounded-md border px-4 text-sm font-semibold transition"
                :class="abaAtiva === grupo ? 'border-line-strong bg-accent-subtle text-accent' : 'border-line text-fg-muted hover:bg-surface-sunken'"
                @click="abaAtiva = grupo"
            >{{ rotulos[grupo] ?? grupo }}</button>
        </div>

        <form class="max-w-3xl" @submit.prevent="salvar">
            <section
                v-for="(campos, grupo) in grupos"
                v-show="abaAtiva === grupo"
                :key="grupo"
                class="space-y-5 rounded-lg border border-line bg-surface p-5"
            >
                <div v-for="campo in campos" :key="campo.chave">
                    <template v-if="campo.tipo === 'imagem'">
                        <p class="text-sm font-semibold text-fg">{{ campo.rotulo }}</p>
                        <img :src="campo.url" alt="Imagem atual de compartilhamento" class="mt-2 w-full max-w-md rounded-md border border-line" width="1200" height="630">
                        <div class="mt-3 flex flex-wrap items-center gap-3">
                            <label class="inline-flex min-h-[44px] cursor-pointer items-center rounded-md border border-line px-4 text-sm font-semibold text-fg hover:bg-surface-sunken focus-within:ring-2 focus-within:ring-accent-ring">
                                {{ imagem.processing ? 'Enviando…' : 'Trocar imagem' }}
                                <input type="file" accept="image/jpeg,image/png,image/webp" class="sr-only" :disabled="imagem.processing" @change="enviarImagem(campo.chave, $event)">
                            </label>
                            <SecondaryButton v-if="campo.valor" type="button" @click="removerImagem(campo.chave)">Voltar à imagem de sempre</SecondaryButton>
                        </div>
                        <InputError :message="imagem.errors.arquivo" />
                    </template>

                    <template v-else>
                        <InputLabel :for="`c-${campo.chave}`" :value="campo.rotulo" />
                        <textarea
                            v-if="campo.tipo === 'texto_longo'"
                            :id="`c-${campo.chave}`"
                            v-model="form.valores[campo.chave]"
                            rows="3"
                            :maxlength="campo.max ?? undefined"
                            :aria-invalid="!! erro(campo.chave)"
                            class="w-full rounded-md border-line-input bg-surface text-sm text-fg focus:border-line-strong focus:ring-2 focus:ring-accent-ring"
                        />
                        <TextInput
                            v-else
                            :id="`c-${campo.chave}`"
                            v-model="form.valores[campo.chave]"
                            class="w-full"
                            :type="campo.tipo === 'email' ? 'email' : campo.tipo === 'url' ? 'url' : 'text'"
                            :inputmode="campo.tipo === 'whatsapp' ? 'numeric' : undefined"
                            :maxlength="campo.max ?? undefined"
                            :aria-invalid="!! erro(campo.chave)"
                        />
                        <p class="mt-1 flex justify-between gap-3 text-sm text-fg-subtle">
                            <span>{{ campo.ajuda }}<template v-if="campo.padrao && form.valores[campo.chave] === ''"> Vazio, vale o texto de sempre.</template></span>
                            <span v-if="campo.max" class="shrink-0 tabular-nums" aria-hidden="true">{{ (form.valores[campo.chave] ?? '').length }}/{{ campo.max }}</span>
                        </p>
                        <InputError :message="erro(campo.chave)" />
                    </template>
                </div>
            </section>

            <div class="mt-6"><PrimaryButton :disabled="form.processing">Salvar configuração</PrimaryButton></div>
        </form>
    </AuthenticatedLayout>
</template>
