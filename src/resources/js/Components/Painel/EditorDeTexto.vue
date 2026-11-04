<script setup>
/*
 * Editor dos textos do blog (bloco 3a da 246, 04/11/2026). TipTap 3, MIT.
 *
 * Gera só o que o `App\Blog\SaneadorDeTexto` aceita: parágrafo, H2/H3,
 * negrito, itálico, riscado, listas, citação, link, código, linha, imagem do
 * próprio blog e o cartão de vídeo `<figure data-video data-id>`. O editor
 * NÃO é a barreira — o servidor limpa de novo, sempre. Aqui é conforto.
 *
 * Imagem: sobe pelo painel (o servidor reduz e tira o EXIF) e só entra no
 * texto com a descrição preenchida — quem usa leitor de tela depende dela.
 * Vídeo: cola o link do YouTube ou do Vimeo; no site vira o player sem cookie.
 */
import { onBeforeUnmount, ref, watch, nextTick } from 'vue';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import { Node } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import Image from '@tiptap/extension-image';
import { Placeholder } from '@tiptap/extensions';
import axios from 'axios';
import { lerVideo } from '@/Composables/lerVideo';
import {
    Bold, Italic, Strikethrough, Heading2, Heading3, List, ListOrdered, Quote,
    Link2, Code, SquareCode, Minus, ImagePlus, Video, Undo2, Redo2,
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
        Image.configure({ inline: false }),
        CartaoDeVideo,
        Placeholder.configure({ placeholder: 'Comece respondendo à pergunta do título, sem rodeio.' }),
    ],
    editorProps: {
        attributes: { class: 'texto-do-blog', 'aria-label': props.rotulo, 'aria-multiline': 'true', role: 'textbox' },
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
const alt = ref('');
const erro = ref('');
const enviando = ref(false);
const imagemPendente = ref(null);
const campoRef = ref(null);
const arquivoRef = ref(null);

function abrir(qual) {
    erro.value = '';
    alt.value = '';
    imagemPendente.value = null;
    campo.value = qual === 'link' ? (editor.value.getAttributes('link').href ?? '') : '';
    modo.value = modo.value === qual ? null : qual;
    if (qual === 'imagem' && modo.value) {
        nextTick(() => arquivoRef.value?.click());
    } else {
        nextTick(() => campoRef.value?.focus());
    }
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

async function enviarImagem(evento) {
    const arquivo = evento.target.files?.[0];
    evento.target.value = '';
    if (!arquivo) return;
    erro.value = '';
    enviando.value = true;
    try {
        const dados = new FormData();
        dados.append('arquivo', arquivo);
        const { data } = await axios.post(route('painel.blog.textos.imagem'), dados);
        imagemPendente.value = data;
        nextTick(() => campoRef.value?.focus());
    } catch (e) {
        erro.value = e.response?.data?.errors?.arquivo?.[0] ?? 'A imagem não subiu. Tente de novo.';
    } finally {
        enviando.value = false;
    }
}

function inserirImagem() {
    if (alt.value.trim() === '') {
        erro.value = 'Descreva a imagem: é o que o leitor de tela lê para quem não enxerga.';
        return;
    }
    const { url, largura, altura } = imagemPendente.value;
    editor.value.chain().focus()
        .insertContent([{ type: 'image', attrs: { src: url, alt: alt.value.trim(), width: largura, height: altura } }, { type: 'paragraph' }])
        .run();
    fechar();
}

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
            <button type="button" class="botao" :class="{ ligado: modo === 'imagem' }" title="Imagem" aria-label="Imagem" @click="abrir('imagem')">
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

        <input ref="arquivoRef" type="file" accept="image/jpeg,image/png,image/webp" class="sr-only" tabindex="-1" aria-hidden="true" @change="enviarImagem" />

        <div v-if="modo" class="border-b border-line bg-surface-sunken p-3 text-sm" role="group" :aria-label="modo === 'link' ? 'Link' : modo === 'video' ? 'Vídeo' : 'Imagem'">
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

            <div v-else>
                <p v-if="enviando" role="status">Enviando e preparando a imagem…</p>
                <form v-else-if="imagemPendente" class="flex flex-wrap items-center gap-3" @submit.prevent="inserirImagem">
                    <img :src="imagemPendente.url" alt="" class="h-16 w-auto rounded border border-line" />
                    <label for="editor-alt" class="text-fg-muted">Descreva a imagem</label>
                    <input id="editor-alt" ref="campoRef" v-model="alt" type="text" maxlength="300" placeholder="O que a imagem mostra" class="campo min-w-[14rem] flex-1" @keydown.esc.prevent="fechar" />
                    <button type="submit" class="acao">Inserir no texto</button>
                    <button type="button" class="acao-sec" @click="fechar">Cancelar</button>
                </form>
                <p v-else class="flex flex-wrap items-center gap-2">
                    <button type="button" class="acao" @click="arquivoRef?.click()">Escolher imagem</button>
                    <span class="text-fg-subtle">JPG, PNG ou WebP até 8 MB. O site reduz e tira a localização da foto.</span>
                    <button type="button" class="acao-sec" @click="fechar">Cancelar</button>
                </p>
            </div>
            <p v-if="erro" class="mt-2 text-danger" role="alert">{{ erro }}</p>
        </div>

        <EditorContent :editor="editor" />
    </div>
</template>

<style scoped>
.botao { @apply inline-flex min-h-[36px] min-w-[36px] items-center justify-center rounded-md text-fg-muted transition hover:bg-surface-sunken hover:text-fg disabled:opacity-40 disabled:hover:bg-transparent; }
.botao.ligado { @apply bg-surface-sunken text-fg ring-1 ring-line-strong; }
.botao:focus-visible, .acao:focus-visible, .acao-sec:focus-visible { @apply outline-none ring-2 ring-accent-ring; }
.campo { @apply rounded-md border-line-input bg-surface text-sm text-fg placeholder:text-fg-subtle focus:border-line-strong focus:ring-2 focus:ring-accent-ring; }
.acao { @apply inline-flex min-h-[36px] items-center rounded-md bg-accent px-3 font-semibold text-fg-on-accent; }
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
:deep(.texto-do-blog img.ProseMirror-selectednode), :deep(.cartao-video.ProseMirror-selectednode) { outline: 3px solid currentColor; }
:deep(.cartao-video) { padding: 1.25rem; border-radius: 0.5rem; border: 1px dashed currentColor; text-align: center; font-size: 0.9rem; opacity: 0.85; }
:deep(.texto-do-blog p.is-editor-empty:first-child::before) { content: attr(data-placeholder); float: left; height: 0; pointer-events: none; opacity: 0.5; }
</style>
