<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { Accessibility, ChevronDown, ChevronRight, ChevronUp, Hand, Minus, Pause, Plus, X } from 'lucide-vue-next';
import { useAcessibilidade } from '@/Composables/useAcessibilidade';

/**
 * Painel de acessibilidade, com FAB próprio no canto inferior ESQUERDO.
 *
 * Por que FAB próprio e não um item de menu: quem precisa de fonte maior ou de
 * mais contraste tem de achar o recurso de cara (LBI / WCAG), não atrás de dois
 * cliques num menu genérico. Padrão aprovado no SSB em 28/07/2026, replicado no
 * Movana em 30/07 e neste repo em 10/08.
 *
 * Fica SEMPRE montado (é Fragment raiz no `app.js`, fora do shell): o overlay do
 * guia de leitura e as preferências salvas dependem disso, e as telas de
 * visitante — login, site público, portal do cliente — são justamente as que
 * mais precisam do recurso.
 *
 * ## Régua de z-index deste repo
 *
 * `TransitionRightCard.vue` usa véu 60 / painel 70, e o CORE §10 manda a gaveta ficar
 * ACIMA dos flutuantes. Então o FAB e o painel daqui ficam em 50 — abaixo da
 * gaveta de propósito. A única exceção é o guia de leitura em `z-[999]`, que
 * precisa cobrir inclusive a gaveta e o modal: uma faixa de leitura que fica
 * por baixo do conteúdo não guia nada.
 *
 * ## VLibras
 *
 * O widget do governo continua montado no `app.js`; aqui ele é só ACIONADO.
 * Fail-safe: o item só aparece — e o botão nativo só é escondido — depois de
 * confirmar em runtime que `window.VLibras` existe e que o botão de acesso foi
 * criado. Script do governo fora do ar: sem item, sem classe, botão nativo
 * intacto.
 */
/*
 * `escolherAnimacoes` é opcional, e é ele que permite a escolha UMA A UMA.
 *
 * A lista do que se mexe é de cada produto (no site da banda: pano, cortina,
 * faixa…), então quem a conhece é quem monta o widget. Sem a prop, o bloco de
 * animações é só a chave "parar todas", como sempre foi — o painel e os outros
 * repos não mudam de comportamento.
 */
const props = defineProps({
    escolherAnimacoes: { type: Function, default: null },
});

const aberto = ref(false);

// Seções colapsáveis: só a essencial (texto e contraste) nasce aberta. O estado
// não persiste de propósito — o painel é curto e reabrir é um clique.
const secoes = ref({ texto: true, leitura: false, libras: false });

const vlibrasPronto = ref(false);
let vlibrasTimer = null;

/*
 * ⚠️ O plugin mudou de contrato (medido em 06/10/2026, v7.12.2): ele não
 * preenche mais a marcação `[vw]` do `VLibrasWidget.vue`. Cria o próprio
 * `#vlibras-access-wrapper` no `<body>`, com `#vlibras-button` num shadow DOM
 * aberto. Procurar só `[vw-access-button]` acha a div vazia da nossa marcação:
 * o item aparecia, clicava em nada, e o botão azul do governo seguia na tela.
 * O formato novo vem primeiro; o antigo só vale se o plugin o preencheu.
 */
function botaoDoVLibras() {
    const novo = document
        .getElementById('vlibras-access-wrapper')
        ?.shadowRoot?.getElementById('vlibras-button');

    if (novo) return novo;

    const antigo = document.querySelector('[vw-access-button]');

    return antigo?.firstElementChild ? antigo : null;
}

function checarVLibras() {
    if (window.VLibras && botaoDoVLibras()) {
        vlibrasPronto.value = true;
        document.documentElement.classList.add('dock-vlibras');

        return true;
    }

    return false;
}

function acionarVLibras() {
    botaoDoVLibras()?.click();
    aberto.value = false;
}

const {
    fonteNivel,
    contrasteNivel,
    escalaCinza,
    sublinharLinks,
    espacamentoTexto,
    pausarAnimacoes,
    cursorGrande,
    guiaLeitura,
    aumentarFonte,
    diminuirFonte,
    aumentarContraste,
    diminuirContraste,
    toggleEscalaCinza,
    toggleSublinharLinks,
    toggleEspacamentoTexto,
    togglePausarAnimacoes,
    toggleCursorGrande,
    toggleGuiaLeitura,
    resetar,
} = useAcessibilidade();

/**
 * Guia de leitura: uma faixa horizontal segue o mouse e escurece o resto da
 * tela. A faixa só é desenhada depois do primeiro mousemove real — se a
 * preferência já vier `true` do localStorage, sem essa espera as duas faixas
 * nasceriam travadas em y=0 cobrindo a tela inteira. Defeito já pago nos
 * irmãos; portado corrigido.
 */
const guiaY = ref(0);
const mouseJaMoveu = ref(false);

function onMouseMove(evento) {
    guiaY.value = evento.clientY;
    mouseJaMoveu.value = true;
}

// Esc fecha (WCAG 2.1.2) — o mesmo par de teclado que o CORE §10 exige de toda
// superfície flutuante.
function onKeydown(evento) {
    if (evento.key === 'Escape' && aberto.value) aberto.value = false;
}

onMounted(() => {
    window.addEventListener('mousemove', onMouseMove, { passive: true });
    window.addEventListener('keydown', onKeydown);

    // O script carrega assíncrono de vlibras.gov.br: tenta por ~20s e desiste.
    if (checarVLibras()) return;

    let tentativas = 0;
    vlibrasTimer = setInterval(() => {
        if (checarVLibras() || ++tentativas >= 20) clearInterval(vlibrasTimer);
    }, 1000);
});

onBeforeUnmount(() => {
    window.removeEventListener('mousemove', onMouseMove);
    window.removeEventListener('keydown', onKeydown);
    clearInterval(vlibrasTimer);
});
</script>

<template>
    <!-- FAB: 44x44 é o mínimo de alvo de toque do CORE §4, e aqui ele é 48. -->
    <button
        type="button"
        :aria-expanded="aberto"
        aria-label="Abrir opções de acessibilidade"
        class="fixed bottom-4 left-4 z-50 grid h-12 w-12 place-items-center rounded-full bg-accent text-white shadow-lg transition hover:bg-accent-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-accent-ring focus-visible:ring-offset-2 dark:ring-offset-gray-950"
        @click="aberto = !aberto"
    >
        <Accessibility class="h-6 w-6" aria-hidden="true" />
    </button>

    <div
        v-if="aberto"
        role="dialog"
        aria-modal="false"
        aria-label="Opções de acessibilidade"
        class="fixed bottom-20 left-4 z-50 max-h-[70vh] w-[calc(100vw-2rem)] overflow-y-auto rounded-2xl border border-line bg-surface p-4 text-fg shadow-xl sm:w-80"
    >
        <div class="flex items-center justify-between">
            <p class="text-xs font-bold uppercase tracking-wide text-fg-subtle">Acessibilidade</p>
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class="rounded text-[11px] font-bold text-fg-subtle hover:text-fg focus:outline-none focus-visible:ring-2 focus-visible:ring-accent-ring"
                    @click="resetar"
                >
                    Resetar
                </button>
                <button
                    type="button"
                    aria-label="Fechar opções de acessibilidade"
                    class="rounded text-fg-subtle hover:text-fg focus:outline-none focus-visible:ring-2 focus-visible:ring-accent-ring"
                    @click="aberto = false"
                >
                    <X class="h-4 w-4" aria-hidden="true" />
                </button>
            </div>
        </div>

        <!--
            ----------------------------------------------------- animações
            Em DESTAQUE e fora das seções recolhíveis — 19/09/2026: "o botão
            de parar animações pode ficar mais destacado no menu". Antes era a
            quarta caixinha de "Leitura e navegação", que nasce fechada: quem
            se incomoda com movimento precisava adivinhar onde estava.
        -->
        <div class="mt-3 rounded-xl border-2 border-accent bg-accent-subtle p-3">
            <label class="flex cursor-pointer items-center justify-between gap-3">
                <span class="flex items-center gap-2.5">
                    <span class="grid h-9 w-9 flex-none place-items-center rounded-full bg-accent text-fg-on-accent">
                        <Pause class="h-[18px] w-[18px]" aria-hidden="true" />
                    </span>
                    <span>
                        <span class="block text-sm font-bold">Parar animações</span>
                        <span class="block text-[11px] text-fg-subtle">
                            {{ pausarAnimacoes ? 'Ligado: nada se mexe sozinho' : 'Para tudo o que se mexe na tela' }}
                        </span>
                    </span>
                </span>
                <input
                    type="checkbox"
                    :checked="pausarAnimacoes"
                    class="h-5 w-5 flex-none cursor-pointer accent-accent"
                    @change="togglePausarAnimacoes"
                />
            </label>
            <button
                v-if="props.escolherAnimacoes"
                type="button"
                class="mt-2.5 flex min-h-[44px] w-full items-center justify-between gap-2 rounded-lg border border-accent px-3 text-left text-sm font-bold hover:bg-accent hover:text-fg-on-accent focus:outline-none focus-visible:ring-2 focus-visible:ring-accent-ring"
                @click="props.escolherAnimacoes()"
            >
                Escolher quais parar
                <ChevronRight class="h-4 w-4" aria-hidden="true" />
            </button>
        </div>

        <!-- ------------------------------------------- texto e contraste -->
        <button
            type="button"
            :aria-expanded="secoes.texto"
            class="mt-3.5 flex w-full items-center justify-between gap-2 rounded border-t border-line pt-3.5 text-left focus:outline-none focus-visible:ring-2 focus-visible:ring-accent-ring"
            @click="secoes.texto = !secoes.texto"
        >
            <span class="text-base font-bold">Texto e contraste</span>
            <component :is="secoes.texto ? ChevronUp : ChevronDown" class="h-4 w-4 text-fg-subtle" aria-hidden="true" />
        </button>

        <div v-show="secoes.texto">
            <div class="mt-3 flex items-center justify-between gap-2">
                <span class="text-sm font-bold">Tamanho do texto</span>
                <div class="flex items-center gap-1">
                    <button
                        type="button"
                        :disabled="fonteNivel === 0"
                        aria-label="Diminuir tamanho do texto"
                        class="grid h-9 w-9 place-items-center rounded-lg border border-line text-xs font-black disabled:opacity-30 focus:outline-none focus-visible:ring-2 focus-visible:ring-accent-ring"
                        @click="diminuirFonte"
                    >
                        A-
                    </button>
                    <button
                        type="button"
                        :disabled="fonteNivel === 2"
                        aria-label="Aumentar tamanho do texto"
                        class="grid h-9 w-9 place-items-center rounded-lg border border-line text-sm font-black disabled:opacity-30 focus:outline-none focus-visible:ring-2 focus-visible:ring-accent-ring"
                        @click="aumentarFonte"
                    >
                        A+
                    </button>
                </div>
            </div>

            <div class="mt-3 flex items-center justify-between gap-2">
                <span class="text-sm font-bold">Contraste</span>
                <div class="flex items-center gap-1">
                    <button
                        type="button"
                        :disabled="contrasteNivel === -2"
                        aria-label="Reduzir contraste (tela mais suave)"
                        class="grid h-9 w-9 place-items-center rounded-lg border border-line disabled:opacity-30 focus:outline-none focus-visible:ring-2 focus-visible:ring-accent-ring"
                        @click="diminuirContraste"
                    >
                        <Minus class="h-3.5 w-3.5" aria-hidden="true" />
                    </button>
                    <span class="w-6 text-center text-[11px] font-bold tabular-nums text-fg-subtle">
                        {{ contrasteNivel }}
                    </span>
                    <button
                        type="button"
                        :disabled="contrasteNivel === 2"
                        aria-label="Aumentar contraste"
                        class="grid h-9 w-9 place-items-center rounded-lg border border-line disabled:opacity-30 focus:outline-none focus-visible:ring-2 focus-visible:ring-accent-ring"
                        @click="aumentarContraste"
                    >
                        <Plus class="h-3.5 w-3.5" aria-hidden="true" />
                    </button>
                </div>
            </div>
            <p class="mt-1 text-[10.5px] text-fg-subtle">
                Negativo deixa a tela mais suave; positivo aumenta o contraste.
            </p>
        </div>

        <!-- ------------------------------------------ leitura e navegação -->
        <button
            type="button"
            :aria-expanded="secoes.leitura"
            class="mt-3.5 flex w-full items-center justify-between gap-2 rounded border-t border-line pt-3.5 text-left focus:outline-none focus-visible:ring-2 focus-visible:ring-accent-ring"
            @click="secoes.leitura = !secoes.leitura"
        >
            <span class="text-base font-bold">Leitura e navegação</span>
            <component :is="secoes.leitura ? ChevronUp : ChevronDown" class="h-4 w-4 text-fg-subtle" aria-hidden="true" />
        </button>

        <div v-show="secoes.leitura" class="mt-2.5 flex flex-col gap-2.5">
            <label class="flex cursor-pointer items-center justify-between gap-2">
                <span class="text-sm font-bold">Escala de cinza</span>
                <input
                    type="checkbox"
                    :checked="escalaCinza"
                    class="rounded border-line-input text-accent focus:ring-2 focus:ring-accent-ring"
                    @change="toggleEscalaCinza"
                />
            </label>
            <label class="flex cursor-pointer items-center justify-between gap-2">
                <span class="text-sm font-bold">Sublinhar links</span>
                <input
                    type="checkbox"
                    :checked="sublinharLinks"
                    class="rounded border-line-input text-accent focus:ring-2 focus:ring-accent-ring"
                    @change="toggleSublinharLinks"
                />
            </label>
            <label class="flex cursor-pointer items-center justify-between gap-2">
                <span class="text-sm font-bold">Espaçamento de texto</span>
                <input
                    type="checkbox"
                    :checked="espacamentoTexto"
                    class="rounded border-line-input text-accent focus:ring-2 focus:ring-accent-ring"
                    @change="toggleEspacamentoTexto"
                />
            </label>
            <label class="flex cursor-pointer items-center justify-between gap-2">
                <span class="text-sm font-bold">Cursor grande</span>
                <input
                    type="checkbox"
                    :checked="cursorGrande"
                    class="rounded border-line-input text-accent focus:ring-2 focus:ring-accent-ring"
                    @change="toggleCursorGrande"
                />
            </label>
            <label class="flex cursor-pointer items-center justify-between gap-2">
                <span class="text-sm font-bold">Guia de leitura</span>
                <input
                    type="checkbox"
                    :checked="guiaLeitura"
                    class="rounded border-line-input text-accent focus:ring-2 focus:ring-accent-ring"
                    @change="toggleGuiaLeitura"
                />
            </label>
        </div>

        <!-- ------------------------------------------------------- Libras -->
        <template v-if="vlibrasPronto">
            <button
                type="button"
                :aria-expanded="secoes.libras"
                class="mt-3.5 flex w-full items-center justify-between gap-2 rounded border-t border-line pt-3.5 text-left focus:outline-none focus-visible:ring-2 focus-visible:ring-accent-ring"
                @click="secoes.libras = !secoes.libras"
            >
                <span class="text-base font-bold">Tradução em Libras</span>
                <component
                    :is="secoes.libras ? ChevronUp : ChevronDown"
                    class="h-4 w-4 text-fg-subtle"
                    aria-hidden="true"
                />
            </button>

            <div v-show="secoes.libras" class="mt-1.5">
                <button
                    type="button"
                    class="flex w-full items-center gap-3 rounded-xl p-2.5 text-left transition-colors hover:bg-accent-subtle focus:outline-none focus-visible:ring-2 focus-visible:ring-accent-ring"
                    @click="acionarVLibras"
                >
                    <span
                        class="grid h-9 w-9 flex-none place-items-center rounded-full bg-accent-subtle text-accent"
                    >
                        <Hand class="h-[18px] w-[18px]" aria-hidden="true" />
                    </span>
                    <span class="min-w-0">
                        <span class="block text-sm font-bold">Ativar VLibras</span>
                        <span class="block text-[10px] text-fg-subtle">
                            Tradutor do governo nesta tela
                        </span>
                    </span>
                </button>
            </div>
        </template>
    </div>

    <!--
        Guia de leitura: duas faixas escurecem tudo fora de uma tira de 120px em
        volta do mouse. `z-[999]` é a exceção consciente da régua deste repo
        (véu 60 / gaveta 70 / FAB 50) — a faixa precisa cobrir inclusive a
        gaveta e o modal, senão não guia nada.
    -->
    <template v-if="guiaLeitura && mouseJaMoveu">
        <div
            class="pointer-events-none fixed left-0 right-0 top-0 z-[999] bg-black/50"
            :style="{ height: Math.max(0, guiaY - 60) + 'px' }"
        ></div>
        <div
            class="pointer-events-none fixed bottom-0 left-0 right-0 z-[999] bg-black/50"
            :style="{ top: guiaY + 60 + 'px' }"
        ></div>
        <div
            class="pointer-events-none fixed left-0 right-0 z-[999] border-y-2 border-line-strong"
            :style="{ top: Math.max(0, guiaY - 60) + 'px', height: '120px' }"
        ></div>
    </template>
</template>
