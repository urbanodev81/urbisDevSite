<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, ref } from 'vue';
import { ArrowDown, ArrowUp, EyeOff, ImageUp, Plus } from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import EstadoVazio from '@/Components/EstadoVazio.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TwoStepConfirm from '@/Components/TwoStepConfirm.vue';

const props = defineProps({
    lancamentos: { type: Array, default: () => [] },
    medida: { type: Array, required: true },
});

/*
 * Site › Lançamentos (bloco 4c, 06/11/2026). Mesmo desenho de Site › Serviços:
 * um formulário só, no topo, para adicionar e para editar. A imagem vai no
 * mesmo envio (multipart); ao editar, o envio é POST com `_method: put`,
 * porque o PHP não lê arquivo em PUT. O servidor recorta o centro na medida.
 */
const vazio = () => ({
    etiqueta: '', titulo: '', texto: '', url: '', chamada: '',
    secundario_url: '', secundario: '', nota: '', ativo: true, arquivo: null,
});

const form = useForm(vazio());
const editando = ref(null);
const caixa = ref(null);
const entrada = ref(null);
const imagemAtual = ref(null);
const previa = ref(null);

const [largura, altura] = props.medida;
const titulo = computed(() => (editando.value ? 'Editar lançamento' : 'Novo lançamento'));
const imagemNaTela = computed(() => previa.value ?? imagemAtual.value);

function limparPrevia() {
    if (previa.value) URL.revokeObjectURL(previa.value);
    previa.value = null;
    if (entrada.value) entrada.value.value = '';
}
onBeforeUnmount(limparPrevia);

function escolherArquivo(evento) {
    // Lê o arquivo ANTES de soltar a prévia antiga: `limparPrevia()` esvazia o campo.
    const arquivo = evento.target.files[0] ?? null;
    if (previa.value) URL.revokeObjectURL(previa.value);
    form.arquivo = arquivo;
    previa.value = arquivo ? URL.createObjectURL(arquivo) : null;
}

async function editar(item) {
    editando.value = item.id;
    form.clearErrors();
    limparPrevia();
    Object.assign(form, {
        ...vazio(),
        ...Object.fromEntries(Object.entries(item).map(([k, v]) => [k, v ?? ''])),
        ativo: item.ativo,
        arquivo: null,
    });
    imagemAtual.value = item.imagem_url;
    await nextTick();
    caixa.value?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    caixa.value?.querySelector('input')?.focus();
}

function cancelar() {
    editando.value = null;
    imagemAtual.value = null;
    limparPrevia();
    form.clearErrors();
    Object.assign(form, vazio());
}

function salvar() {
    const opcoes = { preserveScroll: true, forceFormData: true, onSuccess: cancelar };
    if (editando.value) {
        form.transform((d) => ({ ...d, ativo: d.ativo ? 1 : 0, _method: 'put' }))
            .post(route('painel.lancamentos.update', editando.value), opcoes);
    } else {
        form.transform((d) => ({ ...d, ativo: d.ativo ? 1 : 0 }))
            .post(route('painel.lancamentos.store'), opcoes);
    }
}

// Ocultar/mostrar regrava os campos de texto como estão; a imagem não muda.
const camposDeTexto = (item) => Object.fromEntries(
    ['etiqueta', 'titulo', 'texto', 'url', 'chamada', 'secundario_url', 'secundario', 'nota'].map((k) => [k, item[k] ?? '']),
);
const mover = (item, direcao) => router.post(route('painel.lancamentos.mover', item.id), { direcao }, { preserveScroll: true });
const alternar = (item) => router.put(route('painel.lancamentos.update', item.id), { ...camposDeTexto(item), ativo: ! item.ativo }, { preserveScroll: true });
const remover = (item) => router.delete(route('painel.lancamentos.destroy', item.id), { preserveScroll: true });

const campoClasse = 'w-full rounded-md border-line-input bg-surface text-sm text-fg focus:border-line-strong focus:ring-2 focus:ring-accent-ring';
</script>

<template>
    <Head title="Lançamentos" />

    <AuthenticatedLayout>
        <template #cabecalho><p class="font-label text-label uppercase text-fg-subtle">Site</p></template>

        <PageHeader
            titulo="Lançamentos"
            descricao="O banner logo depois do topo da home, um slide por lançamento. Só entra o que já está no ar e tem endereço público. Salvou, já está no ar."
        />

        <form ref="caixa" class="mb-8 max-w-3xl scroll-mt-24 rounded-lg border border-line bg-surface p-5" @submit.prevent="salvar">
            <h2 class="mb-4 font-display text-xl font-bold uppercase text-fg">{{ titulo }}</h2>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <InputLabel for="l-etiqueta" value="Etiqueta (linha pequena acima)" obrigatorio />
                    <TextInput id="l-etiqueta" v-model="form.etiqueta" class="w-full" maxlength="60" placeholder="Ex.: Cliente · No ar" :aria-invalid="!! form.errors.etiqueta" />
                    <InputError :message="form.errors.etiqueta" />
                </div>
                <div>
                    <InputLabel for="l-titulo" value="Título" obrigatorio />
                    <TextInput id="l-titulo" v-model="form.titulo" class="w-full" maxlength="120" :aria-invalid="!! form.errors.titulo" />
                    <InputError :message="form.errors.titulo" />
                </div>
            </div>

            <div class="mt-4">
                <InputLabel for="l-texto" value="Texto" obrigatorio />
                <textarea id="l-texto" v-model="form.texto" rows="3" maxlength="400" :class="campoClasse" :aria-invalid="!! form.errors.texto" />
                <p class="mt-1 flex justify-between gap-3 text-sm text-fg-subtle">
                    <span>É o único lugar da home que cita cliente pelo nome. Confira se ele autorizou.</span>
                    <span class="shrink-0 tabular-nums" aria-hidden="true">{{ form.texto.length }}/400</span>
                </p>
                <InputError :message="form.errors.texto" />
            </div>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <InputLabel for="l-url" value="Endereço do lançamento" obrigatorio />
                    <TextInput id="l-url" v-model="form.url" type="url" class="w-full" placeholder="https://" maxlength="255" :aria-invalid="!! form.errors.url" />
                    <p class="mt-1 text-sm text-fg-subtle">O botão principal e a imagem levam para cá.</p>
                    <InputError :message="form.errors.url" />
                </div>
                <div>
                    <InputLabel for="l-chamada" value="Texto do botão principal" obrigatorio />
                    <TextInput id="l-chamada" v-model="form.chamada" class="w-full" maxlength="40" placeholder="Ex.: Visitar o site" :aria-invalid="!! form.errors.chamada" />
                    <InputError :message="form.errors.chamada" />
                </div>
            </div>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <InputLabel for="l-sec-url" value="Segundo link (opcional)" />
                    <TextInput id="l-sec-url" v-model="form.secundario_url" type="url" class="w-full" placeholder="https://" maxlength="255" :aria-invalid="!! form.errors.secundario_url" />
                    <InputError :message="form.errors.secundario_url" />
                </div>
                <div>
                    <InputLabel for="l-sec" value="Texto do segundo link" />
                    <TextInput id="l-sec" v-model="form.secundario" class="w-full" maxlength="40" :disabled="! form.secundario_url" :aria-invalid="!! form.errors.secundario" />
                    <InputError :message="form.errors.secundario" />
                </div>
            </div>

            <div class="mt-4">
                <InputLabel for="l-nota" value="Nota abaixo dos botões (opcional)" />
                <TextInput id="l-nota" v-model="form.nota" class="w-full" maxlength="120" placeholder="Ex.: 30 dias grátis, sem cartão de crédito." :aria-invalid="!! form.errors.nota" />
                <InputError :message="form.errors.nota" />
            </div>

            <div class="mt-4">
                <InputLabel for="l-arquivo" :value="editando ? 'Imagem (envie outra só para trocar)' : 'Imagem'" :obrigatorio="! editando" />
                <img
                    v-if="imagemNaTela"
                    :src="imagemNaTela"
                    :alt="previa ? 'Imagem escolhida, antes do recorte' : 'Imagem atual do slide'"
                    class="mt-2 aspect-[8/5] w-full max-w-md rounded-md border border-line object-cover"
                    :width="largura"
                    :height="altura"
                >
                <label class="mt-2 inline-flex min-h-[44px] cursor-pointer items-center gap-2 rounded-md border border-line-input bg-surface px-4 text-sm font-semibold text-fg hover:bg-surface-sunken focus-within:outline focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-accent-ring">
                    <ImageUp class="h-4 w-4" aria-hidden="true" />
                    {{ form.arquivo ? 'Escolher outra' : 'Escolher imagem' }}
                    <input id="l-arquivo" ref="entrada" type="file" accept="image/jpeg,image/png,image/webp" class="sr-only" :aria-invalid="!! form.errors.arquivo" @change="escolherArquivo">
                </label>
                <p class="mt-1 text-sm text-fg-subtle">
                    JPG, PNG ou WebP, até 8 MB, com pelo menos {{ largura }}×{{ altura }}. O centro é recortado nessa proporção
                    (a prévia acima já mostra o corte). Uma captura de tela do site do lançamento costuma servir.
                </p>
                <InputError :message="form.errors.arquivo" />
            </div>

            <div class="mt-5 flex flex-wrap items-center gap-4">
                <PrimaryButton :disabled="form.processing">
                    <Plus v-if="! editando" class="h-4 w-4" aria-hidden="true" />
                    {{ form.processing ? 'Enviando…' : (editando ? 'Salvar lançamento' : 'Adicionar') }}
                </PrimaryButton>
                <SecondaryButton v-if="editando" type="button" @click="cancelar">Cancelar</SecondaryButton>
                <label class="flex min-h-[44px] items-center gap-2 text-sm text-fg"><Checkbox v-model:checked="form.ativo" />Aparece no site</label>
            </div>
        </form>

        <ol v-if="lancamentos.length" class="max-w-3xl space-y-3" aria-label="Lançamentos, na ordem do banner">
            <li
                v-for="(item, i) in lancamentos"
                :key="item.id"
                class="rounded-lg border bg-surface p-5"
                :class="editando === item.id ? 'border-line-strong' : 'border-line'"
            >
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
                    <img :src="item.imagem_url" alt="" class="aspect-[8/5] w-full shrink-0 rounded-md border border-line object-cover sm:w-40" :width="largura" :height="altura" loading="lazy">
                    <div class="min-w-0 flex-1">
                        <p class="font-label text-label uppercase text-fg-subtle">{{ item.etiqueta }}</p>
                        <h2 class="mt-1 font-display text-lg font-bold text-fg">{{ item.titulo }}</h2>
                        <p class="mt-1 text-sm text-fg-muted">{{ item.texto }}</p>
                        <p class="mt-1 break-all text-sm text-fg-subtle">{{ item.chamada }} → {{ item.url }}</p>
                        <p v-if="item.secundario_url" class="break-all text-sm text-fg-subtle">{{ item.secundario }} → {{ item.secundario_url }}</p>
                    </div>
                    <span v-if="! item.ativo" class="inline-flex shrink-0 items-center gap-1 text-sm text-fg-subtle">
                        <EyeOff class="h-4 w-4" aria-hidden="true" />Oculto
                    </span>
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    <SecondaryButton type="button" :disabled="i === 0" :aria-label="`Subir ${item.titulo}`" @click="mover(item, 'subir')">
                        <ArrowUp class="h-4 w-4" aria-hidden="true" />Subir
                    </SecondaryButton>
                    <SecondaryButton type="button" :disabled="i === lancamentos.length - 1" :aria-label="`Descer ${item.titulo}`" @click="mover(item, 'descer')">
                        <ArrowDown class="h-4 w-4" aria-hidden="true" />Descer
                    </SecondaryButton>
                    <SecondaryButton type="button" @click="editar(item)">Editar</SecondaryButton>
                    <SecondaryButton type="button" @click="alternar(item)">{{ item.ativo ? 'Ocultar do site' : 'Mostrar no site' }}</SecondaryButton>
                    <TwoStepConfirm :nome="item.titulo" @confirmar="remover(item)" />
                </div>
            </li>
        </ol>

        <EstadoVazio
            v-else
            icone="inbox"
            titulo="Nenhum lançamento"
            descricao="Sem lançamento, o banner some da home. Adicione um no formulário acima."
        />
    </AuthenticatedLayout>
</template>
