<script setup>
/*
 * Galeria de imagens do editor do blog (05/11/2026).
 *
 * Abre em modal com TODA imagem que o sistema tem (`GET /painel/blog/imagens`:
 * as que o painel subiu, as capas, as de dentro dos textos e as ilustrações dos
 * guias) e o "Subir nova" no topo. Escolhida a imagem, o segundo passo pede a
 * descrição (vem preenchida com a última usada), o tamanho e o alinhamento.
 * `trocar` = só troca a foto de uma imagem que já está no texto: devolve sem
 * pedir o resto, que fica como estava.
 */
import { computed, nextTick, ref, watch } from 'vue';
import axios from 'axios';
import Modal from '@/Components/Modal.vue';
import { Upload, Search, X } from 'lucide-vue-next';

const props = defineProps({
    show: { type: Boolean, default: false },
    trocar: { type: Boolean, default: false },
});
const emit = defineEmits(['close', 'escolher']);

const TAMANHOS = [
    { valor: 'pequena', rotulo: 'Pequena' },
    { valor: 'media', rotulo: 'Média' },
    { valor: 'inteira', rotulo: 'Inteira' },
];
const ALINHAMENTOS = [
    { valor: 'esquerda', rotulo: 'À esquerda, texto ao lado' },
    { valor: 'centro', rotulo: 'No centro' },
    { valor: 'direita', rotulo: 'À direita, texto ao lado' },
];

const imagens = ref(null);
const erroLista = ref('');
const busca = ref('');
const enviando = ref(false);
const erroEnvio = ref('');
const escolhida = ref(null);
const alt = ref('');
const tamanho = ref('inteira');
const alinhar = ref('centro');
const erroAlt = ref('');
const arquivoRef = ref(null);
const altRef = ref(null);

async function carregar() {
    erroLista.value = '';
    try {
        const { data } = await axios.get(route('painel.blog.textos.imagens'));
        imagens.value = data.imagens;
    } catch {
        erroLista.value = 'Não deu para carregar as imagens. Feche e abra de novo.';
        imagens.value = [];
    }
}

watch(() => props.show, (aberto) => {
    if (!aberto) return;
    escolhida.value = null;
    busca.value = '';
    erroEnvio.value = '';
    carregar();
});

const filtradas = computed(() => {
    const termo = busca.value.trim().toLowerCase();
    if (!imagens.value || termo === '') return imagens.value ?? [];
    return imagens.value.filter((i) => [i.nome, i.alt, ...(i.usos ?? [])].some((t) => (t ?? '').toLowerCase().includes(termo)));
});

function escolher(imagem) {
    if (props.trocar) {
        emit('escolher', { ...imagem });
        return;
    }
    escolhida.value = imagem;
    alt.value = imagem.alt ?? '';
    tamanho.value = 'inteira';
    alinhar.value = 'centro';
    erroAlt.value = '';
    nextTick(() => altRef.value?.focus());
}

async function enviar(evento) {
    const arquivo = evento.target.files?.[0];
    evento.target.value = '';
    if (!arquivo) return;
    erroEnvio.value = '';
    enviando.value = true;
    try {
        const dados = new FormData();
        dados.append('arquivo', arquivo);
        const { data } = await axios.post(route('painel.blog.textos.imagem'), dados);
        const nova = { ...data, nome: arquivo.name, usos: [], alt: null, capa: false };
        imagens.value = [nova, ...(imagens.value ?? []).filter((i) => i.url !== nova.url)];
        escolher(nova);
    } catch (e) {
        erroEnvio.value = e.response?.status === 413
            ? 'A imagem passa do limite de 8 MB.'
            : (e.response?.data?.errors?.arquivo?.[0] ?? 'A imagem não subiu. Tente de novo.');
    } finally {
        enviando.value = false;
    }
}

function inserir() {
    if (alt.value.trim() === '') {
        erroAlt.value = 'Descreva a imagem: é o que o leitor de tela lê para quem não enxerga.';
        altRef.value?.focus();
        return;
    }
    emit('escolher', { ...escolhida.value, alt: alt.value.trim(), tamanho: tamanho.value, alinhar: tamanho.value === 'inteira' ? 'centro' : alinhar.value });
}
</script>

<template>
    <Modal :show="show" max-width="5xl" @close="emit('close')">
        <div class="flex max-h-[85vh] flex-col" role="document">
            <header class="flex items-center gap-3 border-b border-line px-5 py-4">
                <h2 class="text-lg font-bold text-fg">{{ escolhida ? 'Como a imagem entra no texto' : trocar ? 'Trocar a imagem' : 'Imagens' }}</h2>
                <button type="button" class="ml-auto rounded-md p-2 text-fg-muted hover:bg-surface-sunken hover:text-fg" aria-label="Fechar" @click="emit('close')">
                    <X class="h-5 w-5" aria-hidden="true" />
                </button>
            </header>

            <!-- Passo 2: descrição, tamanho e alinhamento -->
            <form v-if="escolhida" class="grid gap-5 overflow-y-auto p-5 md:grid-cols-[minmax(0,1fr)_minmax(0,1.1fr)]" @submit.prevent="inserir">
                <img :src="escolhida.url" alt="" class="max-h-[50vh] w-full rounded-md border border-line object-contain" />
                <div class="space-y-5 text-sm">
                    <div>
                        <label for="galeria-alt" class="block font-semibold text-fg">Descrição da imagem <span class="text-accent" aria-hidden="true">*</span></label>
                        <input id="galeria-alt" ref="altRef" v-model="alt" type="text" maxlength="300" placeholder="O que a imagem mostra" class="campo mt-1 w-full" :aria-invalid="erroAlt ? 'true' : 'false'" aria-describedby="galeria-alt-ajuda" />
                        <p id="galeria-alt-ajuda" class="mt-1 text-fg-subtle">É o que o leitor de tela lê. {{ escolhida.alt ? 'Veio da última vez que ela foi usada.' : '' }}</p>
                        <p v-if="erroAlt" class="mt-1 text-danger" role="alert">{{ erroAlt }}</p>
                    </div>
                    <fieldset>
                        <legend class="font-semibold text-fg">Tamanho</legend>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <label v-for="t in TAMANHOS" :key="t.valor" class="opcao" :class="{ marcada: tamanho === t.valor }">
                                <input v-model="tamanho" type="radio" name="galeria-tamanho" :value="t.valor" class="sr-only" />{{ t.rotulo }}
                            </label>
                        </div>
                    </fieldset>
                    <fieldset :disabled="tamanho === 'inteira'">
                        <legend class="font-semibold text-fg">Posição</legend>
                        <div class="mt-2 flex flex-wrap gap-2" :class="{ 'opacity-50': tamanho === 'inteira' }">
                            <label v-for="a in ALINHAMENTOS" :key="a.valor" class="opcao" :class="{ marcada: alinhar === a.valor && tamanho !== 'inteira' }">
                                <input v-model="alinhar" type="radio" name="galeria-alinhar" :value="a.valor" class="sr-only" />{{ a.rotulo }}
                            </label>
                        </div>
                        <p v-if="tamanho === 'inteira'" class="mt-1 text-fg-subtle">A imagem inteira ocupa a largura do texto.</p>
                        <p v-else class="mt-1 text-fg-subtle">No celular ela sempre fica sozinha na linha, sem texto ao lado.</p>
                    </fieldset>
                    <div class="flex flex-wrap gap-3 pt-2">
                        <button type="submit" class="acao">Inserir no texto</button>
                        <button type="button" class="acao-sec" @click="escolhida = null">Escolher outra</button>
                    </div>
                </div>
            </form>

            <!-- Passo 1: a grade -->
            <div v-else class="flex min-h-0 flex-col">
                <div class="flex flex-wrap items-center gap-3 border-b border-line px-5 py-3 text-sm">
                    <button type="button" class="acao" :disabled="enviando" @click="arquivoRef?.click()">
                        <Upload class="mr-2 h-4 w-4" aria-hidden="true" />{{ enviando ? 'Enviando…' : 'Subir nova' }}
                    </button>
                    <input ref="arquivoRef" type="file" accept="image/jpeg,image/png,image/webp" class="sr-only" tabindex="-1" aria-hidden="true" @change="enviar" />
                    <span class="text-fg-subtle">JPG, PNG ou WebP até 8 MB. O site reduz e tira a localização da foto.</span>
                    <label class="relative ml-auto">
                        <span class="sr-only">Buscar imagem</span>
                        <Search class="pointer-events-none absolute left-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-fg-subtle" aria-hidden="true" />
                        <input v-model="busca" type="search" placeholder="Buscar por nome ou texto" class="campo w-60 pl-8" />
                    </label>
                    <p v-if="erroEnvio" class="w-full text-danger" role="alert">{{ erroEnvio }}</p>
                </div>
                <div class="overflow-y-auto p-5">
                    <p v-if="imagens === null" class="text-sm text-fg-subtle" role="status">Carregando as imagens…</p>
                    <p v-else-if="erroLista" class="text-sm text-danger" role="alert">{{ erroLista }}</p>
                    <p v-else-if="filtradas.length === 0" class="text-sm text-fg-subtle">{{ busca ? 'Nenhuma imagem com esse nome.' : 'Nenhuma imagem ainda. Suba a primeira.' }}</p>
                    <ul v-else class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4" aria-label="Imagens do sistema">
                        <li v-for="img in filtradas" :key="img.url">
                            <button type="button" class="cartao" @click="escolher(img)">
                                <span class="relative block aspect-[4/3] overflow-hidden rounded-t-md bg-surface-sunken">
                                    <img :src="img.url" alt="" loading="lazy" class="h-full w-full object-cover" />
                                    <span v-if="img.capa" class="selo">capa</span>
                                </span>
                                <span class="block px-2 py-1.5 text-left text-xs">
                                    <span class="block truncate font-medium text-fg">{{ img.alt || img.nome }}</span>
                                    <span class="block truncate text-fg-subtle">
                                        {{ img.usos?.length ? 'Usada em ' + img.usos.join(', ') : 'Ainda não usada' }}
                                    </span>
                                </span>
                            </button>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </Modal>
</template>

<style scoped>
.campo { @apply rounded-md border-line-input bg-surface text-sm text-fg placeholder:text-fg-subtle focus:border-line-strong focus:ring-2 focus:ring-accent-ring; }
.acao { @apply inline-flex min-h-[40px] items-center rounded-md bg-accent px-4 font-semibold text-fg-on-accent disabled:opacity-60; }
.acao-sec { @apply inline-flex min-h-[40px] items-center px-2 text-fg-muted underline; }
.acao:focus-visible, .acao-sec:focus-visible, .cartao:focus-visible { @apply outline-none ring-2 ring-accent-ring; }
.cartao { @apply block w-full overflow-hidden rounded-md border border-line bg-surface transition hover:border-line-strong hover:shadow-md; }
.selo { @apply absolute bottom-1.5 left-1.5 rounded bg-surface/90 px-1.5 text-[11px] font-semibold text-fg; }
.opcao { @apply inline-flex min-h-[40px] cursor-pointer items-center rounded-md border border-line px-3 text-fg-muted transition hover:border-line-strong; }
.opcao.marcada { @apply border-accent bg-surface-sunken font-semibold text-fg ring-1 ring-accent; }
.opcao:focus-within { @apply ring-2 ring-accent-ring; }
</style>
