<script setup>
/*
 * Escrever e editar um texto do blog (bloco 3a da 246, 04/11/2026).
 *
 * Os campos são os do cabeçalho dos .md (`resources/blog/_MODELO.md`), com o
 * mesmo significado. Publicar = tirar de rascunho; a data decide quando entra
 * no ar (hoje ou antes: já; futuro: agendado, entra sozinho no dia).
 */
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import axios from 'axios';
import { ExternalLink, Eye } from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TwoStepConfirm from '@/Components/TwoStepConfirm.vue';
import Chip from '@/Components/Chip.vue';
import EditorDeTexto from '@/Components/Painel/EditorDeTexto.vue';
import { situacoes } from './situacoes';

const props = defineProps({
    texto: { type: Object, default: null },
    secoes: { type: Array, default: () => [] },
});

const editando = computed(() => props.texto !== null);
const hoje = new Date().toLocaleDateString('sv-SE');

const form = useForm({
    titulo: props.texto?.titulo ?? '',
    descricao: props.texto?.descricao ?? '',
    corpo_html: props.texto?.corpo_html ?? '',
    data_publicacao: props.texto?.data_publicacao ?? hoje,
    atualizado: props.texto?.atualizado ?? '',
    rascunho: props.texto?.rascunho ?? true,
    slug: props.texto?.slug ?? '',
    autor: props.texto?.autor ?? '',
    secao: props.texto?.secao ?? '',
    tags: (props.texto?.tags ?? []).join(', '),
    imagem: props.texto?.imagem ?? '',
    imagem_alt: props.texto?.imagem_alt ?? '',
    imagem_credito: props.texto?.imagem_credito ?? '',
    acao_texto: props.texto?.acao_texto ?? '',
    acao_url: props.texto?.acao_url ?? '',
    parceiro: props.texto?.parceiro ?? '',
    parceiro_logo: props.texto?.parceiro_logo ?? '',
    parceiro_url: props.texto?.parceiro_url ?? '',
});

const quando = computed(() => {
    if (form.rascunho) return 'Fica como rascunho: só o painel vê.';
    if (!form.data_publicacao) return '';
    const [a, m, d] = form.data_publicacao.split('-');
    return form.data_publicacao > hoje ? `Entra no ar sozinho em ${d}/${m}/${a}.` : 'Entra no ar assim que salvar.';
});

function salvar() {
    form.transform((d) => ({ ...d, tags: d.tags.split(',').map((t) => t.trim()).filter(Boolean) }));
    if (editando.value) {
        form.put(route('painel.blog.textos.update', props.texto.uuid), { preserveScroll: true });
    } else {
        form.post(route('painel.blog.textos.store'));
    }
}

const arquivar = () => router.delete(route('painel.blog.textos.destroy', props.texto.uuid));

// Capa: sobe, o servidor recorta 1200×630 no centro, e o endereço vem para o campo.
const capaErro = ref('');
const capaEnviando = ref(false);
async function enviarCapa(evento) {
    const arquivo = evento.target.files?.[0];
    evento.target.value = '';
    if (!arquivo) return;
    capaErro.value = '';
    capaEnviando.value = true;
    try {
        const dados = new FormData();
        dados.append('arquivo', arquivo);
        dados.append('capa', '1');
        const { data } = await axios.post(route('painel.blog.textos.imagem'), dados);
        form.imagem = data.url;
    } catch (e) {
        capaErro.value = e.response?.data?.errors?.arquivo?.[0] ?? 'A capa não subiu. Tente de novo.';
    } finally {
        capaEnviando.value = false;
    }
}
</script>

<template>
    <Head :title="editando ? `Editar: ${texto.titulo}` : 'Novo texto'" />

    <AuthenticatedLayout>
        <PageHeader :titulo="editando ? 'Editar texto' : 'Novo texto'" :voltar-para="route('painel.blog.textos.index')" voltar-rotulo="Blog">
            <template v-if="editando" #acoes>
                <Chip v-bind="situacoes[texto.situacao]" />
                <a :href="texto.previa" target="_blank" rel="noopener" class="inline-flex min-h-[44px] items-center gap-1 rounded-md px-3 text-sm font-medium text-fg-muted hover:bg-surface-sunken">
                    <Eye class="h-4 w-4" aria-hidden="true" /> Prévia
                </a>
                <a v-if="texto.url" :href="texto.url" target="_blank" rel="noopener" class="inline-flex min-h-[44px] items-center gap-1 rounded-md px-3 text-sm font-medium text-fg-muted hover:bg-surface-sunken">
                    <ExternalLink class="h-4 w-4" aria-hidden="true" /> Ver no site
                </a>
            </template>
        </PageHeader>

        <p v-if="editando && texto.origem === 'arquivo'" class="mb-4 max-w-4xl rounded-md border border-line bg-surface-sunken px-4 py-3 text-sm text-fg-muted">
            Este texto veio de um <code>.md</code>. Agora quem vale é o painel: mudar o arquivo no GitHub não muda mais o site.
        </p>

        <form class="max-w-4xl space-y-6" @submit.prevent="salvar">
            <section class="space-y-4 rounded-lg border border-line bg-surface p-5">
                <div>
                    <InputLabel for="titulo" value="Título" obrigatorio />
                    <TextInput id="titulo" v-model="form.titulo" maxlength="255" placeholder="Escreva o título como a pessoa buscaria" />
                    <InputError :message="form.errors.titulo" />
                </div>
                <div>
                    <InputLabel for="descricao" value="Resumo" obrigatorio />
                    <textarea id="descricao" v-model="form.descricao" rows="2" maxlength="300"
                        class="w-full rounded-md border-line-input bg-surface text-sm text-fg focus:border-line-strong focus:ring-2 focus:ring-accent-ring"
                        aria-describedby="descricao-ajuda" />
                    <p id="descricao-ajuda" class="text-xs text-fg-subtle">Aparece no Google e na lista do blog. Até 160 caracteres é o ideal ({{ form.descricao.length }}).</p>
                    <InputError :message="form.errors.descricao" />
                </div>
                <div>
                    <InputLabel value="Texto" obrigatorio />
                    <EditorDeTexto v-model="form.corpo_html" rotulo="Texto" :invalido="!!form.errors.corpo_html" />
                    <InputError :message="form.errors.corpo_html" />
                </div>
            </section>

            <section class="rounded-lg border border-line bg-surface p-5">
                <h2 class="mb-4 font-display text-xl font-bold text-fg">Quando sai</h2>
                <fieldset class="mb-4 flex flex-wrap gap-4">
                    <legend class="sr-only">Situação</legend>
                    <label class="flex min-h-[44px] items-center gap-2"><input v-model="form.rascunho" type="radio" :value="true" /> Rascunho</label>
                    <label class="flex min-h-[44px] items-center gap-2"><input v-model="form.rascunho" type="radio" :value="false" /> Publicar na data</label>
                </fieldset>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel for="data_publicacao" value="Data" obrigatorio />
                        <TextInput id="data_publicacao" v-model="form.data_publicacao" type="date" />
                        <InputError :message="form.errors.data_publicacao" />
                    </div>
                    <div>
                        <InputLabel for="atualizado" value="Atualizado em (opcional)" />
                        <TextInput id="atualizado" v-model="form.atualizado" type="date" />
                        <InputError :message="form.errors.atualizado" />
                    </div>
                </div>
                <p class="mt-3 text-sm font-medium text-fg" role="status">{{ quando }}</p>
            </section>

            <section class="rounded-lg border border-line bg-surface p-5">
                <h2 class="mb-4 font-display text-xl font-bold text-fg">Capa</h2>
                <div class="flex flex-wrap items-start gap-4">
                    <img v-if="form.imagem" :src="form.imagem" alt="" class="h-[105px] w-[200px] rounded-md border border-line object-cover" />
                    <div class="space-y-2">
                        <label class="inline-flex min-h-[44px] cursor-pointer items-center rounded-md border border-line px-4 text-sm font-medium text-fg hover:bg-surface-sunken focus-within:ring-2 focus-within:ring-accent-ring">
                            {{ capaEnviando ? 'Enviando…' : form.imagem ? 'Trocar capa' : 'Enviar capa' }}
                            <input type="file" accept="image/jpeg,image/png,image/webp" class="sr-only" :disabled="capaEnviando" @change="enviarCapa" />
                        </label>
                        <button v-if="form.imagem" type="button" class="ml-2 text-sm text-fg-muted underline" @click="form.imagem = ''; form.imagem_alt = ''">Tirar capa</button>
                        <p class="text-xs text-fg-subtle">Recortada no centro em 1200×630, o tamanho da prévia no LinkedIn e no WhatsApp.</p>
                        <p v-if="capaErro" class="text-sm text-danger" role="alert">{{ capaErro }}</p>
                    </div>
                </div>
                <div v-if="form.imagem" class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel for="imagem_alt" value="Descrição da capa" obrigatorio />
                        <TextInput id="imagem_alt" v-model="form.imagem_alt" maxlength="300" placeholder="O que a imagem mostra" />
                        <InputError :message="form.errors.imagem_alt" />
                    </div>
                    <div>
                        <InputLabel for="imagem_credito" value="Crédito (opcional)" />
                        <TextInput id="imagem_credito" v-model="form.imagem_credito" maxlength="200" placeholder="Foto: …" />
                        <InputError :message="form.errors.imagem_credito" />
                    </div>
                </div>
                <InputError :message="form.errors.imagem" />
            </section>

            <section class="rounded-lg border border-line bg-surface p-5">
                <h2 class="mb-4 font-display text-xl font-bold text-fg">Organização</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel for="secao" value="Seção (opcional)" />
                        <TextInput id="secao" v-model="form.secao" maxlength="60" list="secoes-conhecidas" />
                        <datalist id="secoes-conhecidas"><option v-for="s in secoes" :key="s" :value="s" /></datalist>
                        <InputError :message="form.errors.secao" />
                    </div>
                    <div>
                        <InputLabel for="tags" value="Tags (separadas por vírgula, até 8)" />
                        <TextInput id="tags" v-model="form.tags" placeholder="software, gestão" />
                        <InputError :message="form.errors.tags || form.errors['tags.0']" />
                    </div>
                    <div>
                        <InputLabel for="autor" value="Autor (opcional)" />
                        <TextInput id="autor" v-model="form.autor" maxlength="120" />
                        <InputError :message="form.errors.autor" />
                    </div>
                    <div>
                        <InputLabel for="slug" value="Endereço" />
                        <div class="flex items-center gap-1 text-sm text-fg-subtle">
                            <span class="font-mono">/blog/</span>
                            <TextInput id="slug" v-model="form.slug" maxlength="120" :disabled="texto?.slugTravado" placeholder="nasce do título" aria-describedby="slug-ajuda" />
                        </div>
                        <p id="slug-ajuda" class="text-xs text-fg-subtle">
                            {{ texto?.slugTravado ? 'Já foi ao ar com este endereço: não muda mais.' : 'Em branco, nasce do título. Depois de ir ao ar, trava.' }}
                        </p>
                        <InputError :message="form.errors.slug" />
                    </div>
                </div>
            </section>

            <details class="rounded-lg border border-line bg-surface p-5">
                <summary class="cursor-pointer font-display text-xl font-bold text-fg">Botão no fim e parceiro (opcional)</summary>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel for="acao_texto" value="Texto do botão" />
                        <TextInput id="acao_texto" v-model="form.acao_texto" maxlength="80" placeholder="Conversar sobre o meu caso" />
                        <InputError :message="form.errors.acao_texto" />
                    </div>
                    <div>
                        <InputLabel for="acao_url" value="Endereço do botão" />
                        <TextInput id="acao_url" v-model="form.acao_url" placeholder="/#contato" />
                        <InputError :message="form.errors.acao_url" />
                    </div>
                    <p class="text-xs text-fg-subtle sm:col-span-2">Em branco, vale o botão padrão do blog ("Conversar sobre o meu caso").</p>
                    <div>
                        <InputLabel for="parceiro" value="Parceiro" />
                        <TextInput id="parceiro" v-model="form.parceiro" maxlength="120" />
                        <InputError :message="form.errors.parceiro" />
                    </div>
                    <div>
                        <InputLabel for="parceiro_url" value="Site do parceiro" />
                        <TextInput id="parceiro_url" v-model="form.parceiro_url" placeholder="https://" />
                        <InputError :message="form.errors.parceiro_url" />
                    </div>
                    <div class="sm:col-span-2">
                        <InputLabel for="parceiro_logo" value="Logo do parceiro (endereço)" />
                        <TextInput id="parceiro_logo" v-model="form.parceiro_logo" placeholder="/img/... ou https://" />
                        <InputError :message="form.errors.parceiro_logo" />
                    </div>
                </div>
            </details>

            <div class="flex flex-wrap items-center gap-2">
                <PrimaryButton :disabled="form.processing">{{ editando ? 'Salvar' : 'Criar texto' }}</PrimaryButton>
                <Link :href="route('painel.blog.textos.index')"><SecondaryButton type="button">Voltar</SecondaryButton></Link>
                <span v-if="form.isDirty && !form.processing" class="text-sm text-fg-subtle">Há mudanças não salvas.</span>
            </div>
        </form>

        <section v-if="editando" class="mt-8 max-w-4xl rounded-lg border border-line bg-surface p-5">
            <h2 class="mb-1 font-display text-xl font-bold text-fg">Tirar do site</h2>
            <p class="mb-3 text-sm text-fg-muted">Arquivar tira o texto do site e da lista. Dá para restaurar depois, na aba Arquivados.</p>
            <TwoStepConfirm :nome="texto.titulo" rotulo="Arquivar" verbo="Arquivar" @confirmar="arquivar" />
        </section>
    </AuthenticatedLayout>
</template>
