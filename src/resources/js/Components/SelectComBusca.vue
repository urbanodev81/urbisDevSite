<script>
// Contador por MÓDULO, e não por instância — o motivo está em `ids.js`.
let sequencia = 0;
</script>

<script setup>
/**
 * Select com busca — 07/10/2026. Mesmo nome e mesmo comportamento do
 * componente do Sócrates, de onde veio (pendência 179). Pedido do Ricardo:
 * "onde for uma lista select, deve ser possível buscar — amanhã temos 1000
 * clientes".
 *
 * É para lista que CRESCE (show, local, vídeo, música, pessoa). Lista fixa e
 * curta (situação, tipo, origem) continua `<select>`: ali a busca é atrito.
 *
 * Um combobox do padrão WAI-ARIA: o campo é a busca, a lista abre embaixo, e
 * seta/Enter/Esc funcionam sem mouse. A busca ignora acento e caixa. O valor
 * emitido é o da opção, como num `<select>`; `opcaoVazia` é o rótulo de
 * "nenhum" (valor ''), ou null para não oferecer.
 */
import { computed, nextTick, ref, watch } from 'vue';

const props = defineProps({
    modelValue: { type: [String, Number], default: '' },
    // [{ valor, rotulo }] — os nomes dos campos mudam em `campoValor`/`campoRotulo`.
    opcoes: { type: Array, required: true },
    campoValor: { type: String, default: 'valor' },
    campoRotulo: { type: String, default: 'rotulo' },
    id: { type: String, default: null },
    opcaoVazia: { type: String, default: 'Nenhum' },
    placeholder: { type: String, default: 'Digite para buscar…' },
    // Para campo sem `<label>` ligado pelo `id`.
    rotulo: { type: String, default: null },
});
const emit = defineEmits(['update:modelValue']);

const uid = props.id ?? `busca-${++sequencia}`;

const semAcento = (t) => String(t ?? '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();

const todas = computed(() => [
    ...(props.opcaoVazia === null ? [] : [{ value: '', label: props.opcaoVazia }]),
    ...props.opcoes.map((o) => ({ value: o[props.campoValor], label: String(o[props.campoRotulo] ?? '') })),
]);
// `==` de propósito: o formulário guarda '' ou null para "nenhum", e id que
// veio de `<select>` pode ser texto onde a opção é número.
// eslint-disable-next-line eqeqeq
const mesmo = (a, b) => (a ?? '') == (b ?? '');
const rotuloAtual = computed(() => todas.value.find((o) => mesmo(o.value, props.modelValue))?.label ?? '');

const aberto = ref(false);
const busca = ref('');
const ativo = ref(0);
const lista = ref(null);

const filtradas = computed(() => {
    const termo = semAcento(busca.value.trim());
    return termo ? todas.value.filter((o) => semAcento(o.label).includes(termo)) : todas.value;
});

watch(filtradas, () => { ativo.value = 0; });

const abrir = () => {
    if (aberto.value) return;
    aberto.value = true;
    busca.value = '';
    ativo.value = Math.max(0, todas.value.findIndex((o) => mesmo(o.value, props.modelValue)));
};
const fechar = () => { aberto.value = false; busca.value = ''; };
const escolher = (o) => { emit('update:modelValue', o.value); fechar(); };

const mover = async (passo) => {
    abrir();
    const n = filtradas.value.length;
    if (!n) return;
    ativo.value = (ativo.value + passo + n) % n;
    await nextTick();
    lista.value?.querySelector('[aria-selected="true"]')?.scrollIntoView({ block: 'nearest' });
};
const confirmar = () => {
    if (aberto.value && filtradas.value[ativo.value]) escolher(filtradas.value[ativo.value]);
};
</script>

<template>
    <div class="relative" @focusout="(e) => { if (!e.currentTarget.contains(e.relatedTarget)) fechar(); }">
        <input
            :id="uid"
            type="text"
            role="combobox"
            autocomplete="off"
            aria-autocomplete="list"
            :aria-label="rotulo ?? undefined"
            :aria-expanded="aberto"
            :aria-controls="`${uid}-lista`"
            :aria-activedescendant="aberto && filtradas.length ? `${uid}-op-${ativo}` : undefined"
            :value="aberto ? busca : rotuloAtual"
            :placeholder="aberto ? placeholder : opcaoVazia ?? placeholder"
            class="min-h-[44px] w-full rounded-md border-line-input bg-surface pr-9 text-sm text-fg placeholder:text-fg-subtle focus:border-line-strong focus:ring-2 focus:ring-accent-ring"
            @focus="abrir"
            @click="abrir"
            @input="(e) => { abrir(); busca = e.target.value; }"
            @keydown.down.prevent="mover(1)"
            @keydown.up.prevent="mover(-1)"
            @keydown.enter.prevent="confirmar"
            @keydown.esc="fechar"
        />
        <svg class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-fg-subtle" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" />
        </svg>

        <ul
            v-show="aberto"
            :id="`${uid}-lista`"
            ref="lista"
            role="listbox"
            class="absolute z-30 mt-1 max-h-64 w-full overflow-auto rounded-md border border-line-strong bg-surface py-1 text-sm shadow-lg"
        >
            <li
                v-for="(o, i) in filtradas"
                :id="`${uid}-op-${i}`"
                :key="o.value"
                role="option"
                :aria-selected="i === ativo"
                class="flex min-h-[44px] cursor-pointer items-center px-3 py-2 text-fg"
                :class="[i === ativo ? 'bg-accent-subtle' : '', mesmo(o.value, modelValue) ? 'font-semibold' : '']"
                @mousedown.prevent="escolher(o)"
                @mousemove="ativo = i"
            >{{ o.label }}</li>
            <li v-if="!filtradas.length" class="px-3 py-2 text-fg-muted" role="presentation">Nada encontrado para “{{ busca }}”.</li>
        </ul>
        <span class="sr-only" role="status">{{ aberto ? `${filtradas.length} opção(ões)` : '' }}</span>
    </div>
</template>
