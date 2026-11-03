<script setup>
import { Trash2 } from 'lucide-vue-next';

/**
 * Ação destrutiva.
 *
 * **O ícone não é enfeite e não pode ser removido.** Neste produto o `--accent`
 * (magenta) e o `--danger` (vermelho) ficam a 25° de matiz em CIELCH — passa
 * raspando no limiar do CORE. Dois botões preenchidos lado a lado, um magenta
 * e um vermelho, são a mesma coisa para boa parte das pessoas, e uma delas
 * apaga um show. A WCAG 1.4.1 diz o mesmo: a cor não pode ser a única
 * portadora do significado.
 *
 * Ver `docs/urbis-design/DUOPATIEGUITA.md` §"A armadilha do magenta".
 */
defineProps({
    type: { type: String, default: 'button' },
    icone: { type: Boolean, default: true },
});
</script>

<template>
    <button
        :type="type"
        class="inline-flex items-center justify-center gap-2 rounded-md bg-danger px-4 py-2
               text-sm font-semibold text-surface transition
               hover:opacity-90 focus-visible:outline focus-visible:outline-2
               focus-visible:outline-offset-2 focus-visible:outline-danger
               disabled:cursor-not-allowed disabled:opacity-50
               min-h-[44px]"
    >
        <Trash2 v-if="icone" class="h-4 w-4" aria-hidden="true" />
        <slot />
    </button>
</template>
