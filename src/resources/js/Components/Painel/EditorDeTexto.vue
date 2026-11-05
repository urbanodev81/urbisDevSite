<script setup>
/*
 * Editor dos textos do blog (bloco 3a da 246, 04/11/2026). TipTap 3, MIT.
 *
 * Gera só o que o `App\Blog\SaneadorDeTexto` aceita: parágrafo, H2/H3,
 * negrito, itálico, riscado, listas, citação, link, código, linha, imagem do
 * próprio blog e o cartão de vídeo `<figure data-video data-id>`. O editor
 * NÃO é a barreira — o servidor limpa de novo, sempre. Aqui é conforto.
 *
 * Imagem: escolhida na galeria (`GaleriaDeImagens`, modal com tudo o que o
 * sistema tem + "Subir nova") e só entra com a descrição preenchida — quem usa
 * leitor de tela depende dela. Tamanho (pequena/média/inteira) e posição
 * (esquerda/centro/direita, com o texto ao lado) viram `data-tamanho` e
 * `data-alinhar`; o servidor só aceita esses valores. Clique com o botão
 * direito numa imagem abre o menu rápido; a mesma barra aparece quando ela
 * está selecionada (teclado e celular não têm botão direito).
 * Vídeo: cola o link do YouTube ou do Vimeo; no site vira o player sem cookie.
 */
import { computed, onBeforeUnmount, onMounted, ref, watch, nextTick } from 'vue';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import { Node } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import Image from '@tiptap/extension-image';
import { Placeholder } from '@tiptap/extensions';
import GaleriaDeImagens from '@/Components/Painel/GaleriaDeImagens.vue';
import { lerVideo } from '@/Composables/lerVideo';
import {
    Bold, Italic, Strikethrough, Heading2, Heading3, List, ListOrdered, Quote,
    Link2, Code, SquareCode, Minus, ImagePlus, Video, Undo2, Redo2,
    AlignLeft, AlignCenter, AlignRight, Replace, Trash2, Type,
} from 'lucide-vue-next';

const props = defineProps({
    modelValue: { type: String, default: '' },
    rotulo: { type: String, default: 'Texto' },
    invalido: { type: Boolean, default: false },
});
const emit = defineEmits(['update:modelValue']);

const CartaoDeVideo = Node.create({
    name: 'video',
    group: 'block',
    atom: true,
    draggable: true,
    addAttributes() {
        return {
            provedor: { default: null, parseHTML: (el) => el.getAttribute('data-video'), renderHTML: (a) => ({ 'data-video': a.provedor }) },
            id: { default: null, parseHTML: (el) => el.getAttribute('data-id'), renderHTML: (a) => ({ 'data-id': a.id }) },
        };
    },
    parseHTML() {
        return [{ tag: 'figure[data-video]' }];
    },
    renderHTML({ HTMLAttributes }) {
        return ['figure', HTMLAttributes];
    },
    addNodeView() {
        return ({ node }) => {
            const dom = document.createElement('div');
            dom.className = 'cartao-video';
            dom.setAttribute('contenteditable', 'false');
            const nome = node.attrs.provedor === 'youtube' ? 'YouTube' : 'Vimeo';
            dom.textContent = `▶ Vídeo do ${nome} · ${node.attrs.id} — no site, vira o player sem cookie`;
            return { dom };
        };
    },
});

// A imagem do texto com tamanho e posição (05/11/2026).
const atributoDeDados = (nome, dado) => ({
    default: null,
    parseHTML: (el) => el.getAttribute(dado),
    renderHTML: (a) => (a[nome] ? { [dado]: a[nome] } : {}),
});
const ImagemDoTexto = Image.extend({
    addAttributes() {
        return {
            ...this.parent?.(),
            tamanho: atributoDeDados('tamanho', 'data-tamanho'),
            alinhar: atributoDeDados('alinhar', 'data-alinhar'),
        };
    },
});

const editor = useEditor({
    content: props.modelValue,
    extensions: [
        StarterKit.configure({
            heading: { levels: [2, 3] },
            underline: false,
            link: {
                openOnClick: false,
                autolink: true,
                protocols: ['mailto'],
                HTMLAttributes: { rel: null, target: null },
                isAllowedUri: (url) => /^(https?:\/\/|mailto:|\/(?!\/))/i.test(url),
            },
        }),
        ImagemDoTexto.configure({ inline: false }),
        CartaoDeVideo,
        Placeholder.configure({ placeholder: 'Comece respondendo à pergunta do título, sem rodeio.' }),
    ],
    editorProps: {
        attributes: { class: 'texto-do-blog', 'aria-label': props.rotulo, 'aria-multiline': 'true', role: 'textbox' },
        handleDOMEvents: {
            // Botão direito numa imagem: seleciona e abre o menu rápido.
            contextmenu: (view, evento) => {
                if (evento.target?.tagName !== 'IMG') return false;
                const pos = view.posAtDOM(evento.target, 0);
                editor.value.chain().focus().setNodeSelection(pos).run();
                menu.value = { x: Math.min(evento.clientX, window.innerWidth - 240), y: Math.min(evento.clientY, window.innerHeight - 370) };
                evento.preventDefault();
                return true;
            },
        },
    },
    onUpdate: ({ editor: e }) => emit('update:modelValue', e.isEmpty ? '' : e.getHTML()),
});

watch(() => props.modelValue, (valor) => {
    if (editor.value && valor !== editor.value.getHTML() && !(valor === '' && editor.value.isEmpty)) {
        editor.value.commands.setContent(valor, { emitUpdate: false });
    }
});

onBeforeUnmount(() => editor.value?.destroy());

// Painel de inserção: link, vídeo ou imagem, um de cada vez, logo abaixo da barra.
const modo = ref(null);
const campo = ref('');
const erro = ref('');
const campoRef = ref(null);

function abrir(qual) {
    erro.value = '';
    campo.value = qual === 'link' ? (editor.value.getAttributes('link').href ?? '') : '';
    modo.value = modo.value === qual ? null : qual;
    nextTick(() => campoRef.value?.focus());
}

function fechar() {
    modo.value = null;
    editor.value.commands.focus();
}

function aplicarLink() {
    const href = campo.value.trim();
    if (href === '') {
        editor.value.chain().focus().extendMarkRange('link').unsetLink().run();
        return fechar();
    }
    if (!/^(https?:\/\/|mailto:|\/(?!\/))/i.test(href)) {
        erro.value = 'Use um endereço que comece com https://, mailto: ou /.';
        return;
    }
    const cadeia = editor.value.chain().focus().extendMarkRange('link');
    if (editor.value.state.selection.empty && !editor.value.isActive('link')) {
        cadeia.insertContent({ type: 'text', text: href, marks: [{ type: 'link', attrs: { href } }] }).run();
    } else {
        cadeia.setLink({ href }).run();
    }
    fechar();
}

function aplicarVideo() {
    const video = lerVideo(campo.value);
    if (!video) {
        erro.value = 'Não reconheci o link. Cole o endereço de um vídeo do YouTube ou do Vimeo.';
        return;
    }
    // Com um parágrafo depois: sem ele, o vídeo fica selecionado e a próxima
    // inserção (uma imagem, por exemplo) o substitui.
    editor.value.chain().focus().insertContent([{ type: 'video', attrs: video }, { type: 'paragraph' }]).run();
    fechar();
}

// ---------- Imagem: galeria, menu rápido e barra da imagem selecionada ----------
const galeriaAberta = ref(false);
const trocando = ref(false);
const menu = ref(null);
const editandoAlt = ref(false);
const novoAlt = ref('');
const altRef = ref(null);

const imagemSelecionada = computed(() => !!editor.value?.isActive('image'));
const atual = computed(() => (imagemSelecionada.value ? editor.value.getAttributes('image') : {}));

function abrirGaleria(trocar = false) {
    modo.value = null;
    menu.value = null;
    trocando.value = trocar;
    galeriaAberta.value = true;
}

// O <dialog> devolve o foco ao botão que o abriu uns 200 ms depois de fechar;
// sem isto, quem segue digitando aperta o botão "Imagem" com o espaço.
function voltarAoTexto() {
    setTimeout(() => editor.value?.commands.focus(), 260);
}

function aoEscolher(img) {
    galeriaAberta.value = false;
    voltarAoTexto();
    if (trocando.value) {
        editor.value.chain().focus().updateAttributes('image', { src: img.url, width: img.largura, height: img.altura }).run();
        return;
    }
    const tamanho = img.tamanho === 'inteira' ? null : img.tamanho;
    const alinhar = tamanho ? img.alinhar : null;
    editor.value.chain().focus()
        .insertContent([{ type: 'image', attrs: { src: img.url, alt: img.alt, width: img.largura, height: img.altura, tamanho, alinhar } }, { type: 'paragraph' }])
        .run();
}

// "inteira" é o padrão: sem atributo, como as imagens de antes.
function definirTamanho(valor) {
    const tamanho = valor === 'inteira' ? null : valor;
    editor.value.chain().focus().updateAttributes('image', { tamanho, alinhar: tamanho ? (atual.value.alinhar ?? 'centro') : null }).run();
    menu.value = null;
}
function definirAlinhamento(valor) {
    editor.value.chain().focus().updateAttributes('image', { alinhar: valor, tamanho: atual.value.tamanho ?? 'media' }).run();
    menu.value = null;
}
function abrirDescricao() {
    novoAlt.value = atual.value.alt ?? '';
    editandoAlt.value = true;
    menu.value = null;
    nextTick(() => altRef.value?.focus());
}
function salvarDescricao() {
    if (novoAlt.value.trim() === '') return;
    editor.value.chain().focus().updateAttributes('image', { alt: novoAlt.value.trim() }).run();
    editandoAlt.value = false;
}
function removerImagem() {
    editor.value.chain().focus().deleteSelection().run();
    menu.value = null;
}

const TAMANHOS = [['pequena', 'P', 'Pequena'], ['media', 'M', 'Média'], ['inteira', 'G', 'Inteira']];
const ALINHAMENTOS = [['esquerda', AlignLeft, 'À esquerda, texto ao lado'], ['centro', AlignCenter, 'No centro'], ['direita', AlignRight, 'À direita, texto ao lado']];
const tamanhoAtual = computed(() => atual.value.tamanho ?? 'inteira');
const alinhamentoAtual = computed(() => (atual.value.tamanho ? (atual.value.alinhar ?? 'centro') : null));

watch(imagemSelecionada, (sim) => { if (!sim) editandoAlt.value = false; });

const fecharMenu = () => { menu.value = null; };
const fecharMenuNoEsc = (e) => { if (e.key === 'Escape' && menu.value) { menu.value = null; editor.value?.commands.focus(); } };
onMounted(() => {
    document.addEventListener('click', fecharMenu);
    document.addEventListener('keydown', fecharMenuNoEsc);
    window.addEventListener('scroll', fecharMenu, true);
});
onBeforeUnmount(() => {
    document.removeEventListener('click', fecharMenu);
    document.removeEventListener('keydown', fecharMenuNoEsc);
    window.removeEventListener('scroll', fecharMenu, true);
});

const botoes = [
    { rotulo: 'Negrito', icone: Bold, ativo: 'bold', acao: (c) => c.toggleBold() },
    { rotulo: 'Itálico', icone: Italic, ativo: 'italic', acao: (c) => c.toggleItalic() },
    { rotulo: 'Riscado', icone: Strikethrough, ativo: 'strike', acao: (c) => c.toggleStrike() },
    null,
    { rotulo: 'Título de seção', icone: Heading2, ativo: ['heading', { level: 2 }], acao: (c) => c.toggleHeading({ level: 2 }) },
    { rotulo: 'Subtítulo', icone: Heading3, ativo: ['heading', { level: 3 }], acao: (c) => c.toggleHeading({ level: 3 }) },
    null,
    { rotulo: 'Lista', icone: List, ativo: 'bulletList', acao: (c) => c.toggleBulletList() },
    { rotulo: 'Lista numerada', icone: ListOrdered, ativo: 'orderedList', acao: (c) => c.toggleOrderedList() },
    { rotulo: 'Citação', icone: Quote, ativo: 'blockquote', acao: (c) => c.toggleBlockquote() },
    null,
    { rotulo: 'Código no meio da frase', icone: Code, ativo: 'code', acao: (c) => c.toggleCode() },
    { rotulo: 'Bloco de código', icone: SquareCode, ativo: 'codeBlock', acao: (c) => c.toggleCodeBlock() },
    { rotulo: 'Linha separadora', icone: Minus, ativo: null, acao: (c) => c.setHorizontalRule() },
];

const ativo = (b) => (b.ativo === null ? false : Array.isArray(b.ativo) ? editor.value?.isActive(...b.ativo) : editor.value?.isActive(b.ativo));
</script>

<template>
    <div class="rounded-md border bg-surface" :class="invalido ? 'border-danger' : 'border-line-input'">
        <div v-if="editor" class="flex flex-wrap items-center gap-0.5 border-b border-line p-1" role="toolbar" :aria-label="`Formatação do ${rotulo.toLowerCase()}`">
            <template v-for="(b, i) in botoes" :key="i">
                <span v-if="b === null" class="mx-1 h-6 w-px bg-line" aria-hidden="true" />
                <button
                    v-else
                    type="button"
                    class="botao"
                    :class="{ ligado: ativo(b) }"
                    :title="b.rotulo"
                    :aria-label="b.rotulo"
                    :aria-pressed="b.ativo === null ? undefined : String(!!ativo(b))"
                    @click="b.acao(editor.chain().focus()).run()"
                >
                    <component :is="b.icone" class="h-4 w-4" aria-hidden="true" />
                </button>
            </template>
            <span class="mx-1 h-6 w-px bg-line" aria-hidden="true" />
            <button type="button" class="botao" :class="{ ligado: modo === 'link' || editor.isActive('link') }" title="Link" aria-label="Link" @click="abrir('link')">
                <Link2 class="h-4 w-4" aria-hidden="true" />
            </button>
            <button type="button" class="botao" :class="{ ligado: galeriaAberta }" title="Imagem" aria-label="Imagem" aria-haspopup="dialog" @click="abrirGaleria(false)">
                <ImagePlus class="h-4 w-4" aria-hidden="true" />
            </button>
            <button type="button" class="botao" :class="{ ligado: modo === 'video' }" title="Vídeo do YouTube ou Vimeo" aria-label="Vídeo do YouTube ou Vimeo" @click="abrir('video')">
                <Video class="h-4 w-4" aria-hidden="true" />
            </button>
            <span class="ml-auto flex">
                <button type="button" class="botao" title="Desfazer" aria-label="Desfazer" :disabled="!editor.can().undo()" @click="editor.chain().focus().undo().run()">
                    <Undo2 class="h-4 w-4" aria-hidden="true" />
                </button>
                <button type="button" class="botao" title="Refazer" aria-label="Refazer" :disabled="!editor.can().redo()" @click="editor.chain().focus().redo().run()">
                    <Redo2 class="h-4 w-4" aria-hidden="true" />
                </button>
            </span>
        </div>

        <div v-if="modo" class="border-b border-line bg-surface-sunken p-3 text-sm" role="group" :aria-label="modo === 'link' ? 'Link' : 'Vídeo'">
            <form v-if="modo === 'link'" class="flex flex-wrap items-center gap-2" @submit.prevent="aplicarLink">
                <label for="editor-link" class="text-fg-muted">Endereço do link</label>
                <input id="editor-link" ref="campoRef" v-model="campo" type="text" inputmode="url" placeholder="https://… ou /blog/…" class="campo min-w-[16rem] flex-1" @keydown.esc.prevent="fechar" />
                <button type="submit" class="acao">Aplicar</button>
                <button type="button" class="acao-sec" @click="fechar">Cancelar</button>
                <span class="w-full text-xs text-fg-subtle">Deixe em branco e aplique para tirar o link.</span>
            </form>

            <form v-else-if="modo === 'video'" class="flex flex-wrap items-center gap-2" @submit.prevent="aplicarVideo">
                <label for="editor-video" class="text-fg-muted">Link do vídeo</label>
                <input id="editor-video" ref="campoRef" v-model="campo" type="text" inputmode="url" placeholder="https://www.youtube.com/watch?v=… ou https://vimeo.com/…" class="campo min-w-[16rem] flex-1" @keydown.esc.prevent="fechar" />
                <button type="submit" class="acao">Inserir</button>
                <button type="button" class="acao-sec" @click="fechar">Cancelar</button>
            </form>

            <p v-if="erro" class="mt-2 text-danger" role="alert">{{ erro }}</p>
        </div>

        <!-- Barra da imagem selecionada: o mesmo do menu rápido, para teclado e celular. -->
        <div v-if="editor && imagemSelecionada" class="flex flex-wrap items-center gap-1 border-b border-line bg-surface-sunken px-2 py-1.5 text-sm" role="toolbar" aria-label="Imagem selecionada">
            <span class="mr-1 text-xs font-semibold uppercase tracking-wide text-fg-subtle">Imagem</span>
            <button v-for="[valor, letra, nome] in TAMANHOS" :key="valor" type="button" class="botao px-2 text-xs font-bold" :class="{ ligado: tamanhoAtual === valor }" :title="nome" :aria-label="'Tamanho: ' + nome" :aria-pressed="String(tamanhoAtual === valor)" @click="definirTamanho(valor)">{{ letra }}</button>
            <span class="mx-1 h-6 w-px bg-line" aria-hidden="true" />
            <button v-for="[valor, icone, nome] in ALINHAMENTOS" :key="valor" type="button" class="botao" :class="{ ligado: alinhamentoAtual === valor }" :title="nome" :aria-label="'Posição: ' + nome" :aria-pressed="String(alinhamentoAtual === valor)" @click="definirAlinhamento(valor)">
                <component :is="icone" class="h-4 w-4" aria-hidden="true" />
            </button>
            <span class="mx-1 h-6 w-px bg-line" aria-hidden="true" />
            <button type="button" class="botao gap-1 px-2" title="Descrição" @click="abrirDescricao"><Type class="h-4 w-4" aria-hidden="true" /><span class="hidden sm:inline">Descrição</span></button>
            <button type="button" class="botao gap-1 px-2" title="Trocar imagem" aria-haspopup="dialog" @click="abrirGaleria(true)"><Replace class="h-4 w-4" aria-hidden="true" /><span class="hidden sm:inline">Trocar</span></button>
            <button type="button" class="botao gap-1 px-2 text-danger" title="Remover imagem" @click="removerImagem"><Trash2 class="h-4 w-4" aria-hidden="true" /><span class="hidden sm:inline">Remover</span></button>
            <form v-if="editandoAlt" class="flex w-full flex-wrap items-center gap-2 pt-1.5" @submit.prevent="salvarDescricao">
                <label for="editor-alt" class="text-fg-muted">Descrição da imagem</label>
                <input id="editor-alt" ref="altRef" v-model="novoAlt" type="text" maxlength="300" class="campo min-w-[14rem] flex-1" @keydown.esc.prevent="editandoAlt = false" />
                <button type="submit" class="acao" :disabled="novoAlt.trim() === ''">Salvar</button>
                <button type="button" class="acao-sec" @click="editandoAlt = false">Cancelar</button>
            </form>
        </div>

        <EditorContent :editor="editor" />

        <!-- Menu rápido (botão direito numa imagem) -->
        <div v-if="menu && imagemSelecionada" class="menu-rapido" :style="{ left: menu.x + 'px', top: menu.y + 'px' }" role="menu" aria-label="Imagem" @click.stop>
            <p class="px-3 pb-1 pt-2 text-[11px] font-semibold uppercase tracking-wide text-fg-subtle">Tamanho</p>
            <button v-for="[valor, , nome] in TAMANHOS" :key="valor" type="button" role="menuitemradio" :aria-checked="String(tamanhoAtual === valor)" class="item" @click="definirTamanho(valor)">
                <span class="w-4">{{ tamanhoAtual === valor ? '✓' : '' }}</span>{{ nome }}
            </button>
            <p class="border-t border-line px-3 pb-1 pt-2 text-[11px] font-semibold uppercase tracking-wide text-fg-subtle">Posição</p>
            <button v-for="[valor, icone, nome] in ALINHAMENTOS" :key="valor" type="button" role="menuitemradio" :aria-checked="String(alinhamentoAtual === valor)" class="item" @click="definirAlinhamento(valor)">
                <component :is="icone" class="h-4 w-4" aria-hidden="true" />{{ nome }}
            </button>
            <div class="border-t border-line" />
            <button type="button" role="menuitem" class="item" @click="abrirDescricao"><Type class="h-4 w-4" aria-hidden="true" />Editar descrição</button>
            <button type="button" role="menuitem" class="item" @click="abrirGaleria(true)"><Replace class="h-4 w-4" aria-hidden="true" />Trocar imagem</button>
            <button type="button" role="menuitem" class="item text-danger" @click="removerImagem"><Trash2 class="h-4 w-4" aria-hidden="true" />Remover</button>
        </div>

        <GaleriaDeImagens :show="galeriaAberta" :trocar="trocando" @close="galeriaAberta = false; voltarAoTexto()" @escolher="aoEscolher" />
    </div>
</template>

<style scoped>
.botao { @apply inline-flex min-h-[36px] min-w-[36px] items-center justify-center rounded-md text-fg-muted transition hover:bg-surface-sunken hover:text-fg disabled:opacity-40 disabled:hover:bg-transparent; }
.botao.ligado { @apply bg-surface-sunken text-fg ring-1 ring-line-strong; }
.botao:focus-visible, .acao:focus-visible, .acao-sec:focus-visible { @apply outline-none ring-2 ring-accent-ring; }
.campo { @apply rounded-md border-line-input bg-surface text-sm text-fg placeholder:text-fg-subtle focus:border-line-strong focus:ring-2 focus:ring-accent-ring; }
.acao { @apply inline-flex min-h-[36px] items-center rounded-md bg-accent px-3 font-semibold text-fg-on-accent; }
.menu-rapido { @apply fixed z-40 w-56 overflow-hidden rounded-lg border border-line bg-surface pb-1 text-sm shadow-xl; }
.menu-rapido .item { @apply flex w-full items-center gap-2 px-3 py-1.5 text-left text-fg hover:bg-surface-sunken focus-visible:bg-surface-sunken focus-visible:outline-none; }
.miniatura { @apply relative block aspect-[4/3] w-full overflow-hidden rounded-md border border-line bg-surface transition hover:border-line-strong focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-ring; }
.selo { @apply absolute bottom-1 left-1 rounded bg-surface/90 px-1.5 text-[11px] font-semibold text-fg; }
.acao-sec { @apply inline-flex min-h-[36px] items-center px-2 text-fg-muted underline; }

:deep(.texto-do-blog) { min-height: 22rem; padding: 1rem 1.25rem; outline: none; line-height: 1.7; }
:deep(.texto-do-blog:focus-visible) { box-shadow: inset 0 0 0 2px currentColor; }
:deep(.texto-do-blog > * + *) { margin-top: 0.85em; }
:deep(.texto-do-blog h2) { font-size: 1.5rem; font-weight: 700; margin-top: 1.6em; line-height: 1.25; }
:deep(.texto-do-blog h3) { font-size: 1.2rem; font-weight: 700; margin-top: 1.3em; }
:deep(.texto-do-blog ul) { list-style: disc; padding-left: 1.5rem; }
:deep(.texto-do-blog ol) { list-style: decimal; padding-left: 1.5rem; }
:deep(.texto-do-blog blockquote) { border-left: 3px solid currentColor; padding-left: 1rem; opacity: 0.85; }
:deep(.texto-do-blog a) { text-decoration: underline; }
:deep(.texto-do-blog code) { font-family: ui-monospace, monospace; font-size: 0.9em; padding: 0.1em 0.3em; border-radius: 0.25rem; background: rgba(127, 127, 127, 0.15); }
:deep(.texto-do-blog pre) { padding: 0.85rem 1rem; border-radius: 0.5rem; background: rgba(127, 127, 127, 0.15); overflow-x: auto; }
:deep(.texto-do-blog pre code) { background: none; padding: 0; }
:deep(.texto-do-blog hr) { border: 0; border-top: 1px solid rgba(127, 127, 127, 0.4); margin: 1.5rem 0; }
:deep(.texto-do-blog img) { max-width: 100%; height: auto; border-radius: 0.5rem; }
/* Tamanho e posição: os mesmos números do site (resources/views/blog/imagens.blade.php). */
:deep(.texto-do-blog img[data-tamanho="pequena"]) { width: 33%; }
:deep(.texto-do-blog img[data-tamanho="media"]) { width: 50%; }
:deep(.texto-do-blog img[data-alinhar="centro"]) { display: block; margin-left: auto; margin-right: auto; }
:deep(.texto-do-blog img[data-alinhar="esquerda"]) { float: left; margin: 0.25rem 1.25rem 0.75rem 0; }
:deep(.texto-do-blog img[data-alinhar="direita"]) { float: right; margin: 0.25rem 0 0.75rem 1.25rem; }
:deep(.texto-do-blog::after) { content: ''; display: table; clear: both; }
:deep(.texto-do-blog h2), :deep(.texto-do-blog h3), :deep(.texto-do-blog hr) { clear: both; }
:deep(.texto-do-blog img.ProseMirror-selectednode), :deep(.cartao-video.ProseMirror-selectednode) { outline: 3px solid currentColor; }
:deep(.cartao-video) { padding: 1.25rem; border-radius: 0.5rem; border: 1px dashed currentColor; text-align: center; font-size: 0.9rem; opacity: 0.85; }
:deep(.texto-do-blog p.is-editor-empty:first-child::before) { content: attr(data-placeholder); float: left; height: 0; pointer-events: none; opacity: 0.5; }
</style>
