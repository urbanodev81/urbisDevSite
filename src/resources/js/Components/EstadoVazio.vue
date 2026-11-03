<script setup>
// Lista FECHADA (03/11/2026): o `import *` levava a biblioteca inteira.
import { Inbox, ScrollText } from 'lucide-vue-next';

const icones = { Inbox, ScrollText };
import { computed } from 'vue';

/**
 * O estado VAZIO do CORE §5: ícone pequeno, uma frase, UMA ação.
 *
 * Ele ocupa a altura do conteúdo, não a de um card cheio — tela de dados não
 * ganha nada com meio metro de ar. E a frase diz o que fazer, não só que não
 * há nada: "nenhum show cadastrado" sozinho não ajuda quem abriu a tela pela
 * primeira vez.
 */
const props = defineProps({
    icone: { type: String, default: 'inbox' },
    titulo: { type: String, required: true },
    descricao: { type: String, default: null },
});

const Icone = computed(() => {
    const nome = props.icone.split('-').map((p) => p[0].toUpperCase() + p.slice(1)).join('');

    return icones[nome] ?? icones.Inbox;
});
</script>

<template>
    <div class="flex flex-col items-center justify-center gap-3 rounded-lg border border-dashed border-line px-6 py-12 text-center">
        <span class="grid h-12 w-12 place-items-center rounded-full bg-surface-sunken text-fg-subtle">
            <component :is="Icone" class="h-6 w-6" aria-hidden="true" />
        </span>
        <p class="font-display text-xl font-bold uppercase text-fg">{{ titulo }}</p>
        <p v-if="descricao" class="max-w-md text-sm text-fg-muted">{{ descricao }}</p>
        <div class="mt-1"><slot /></div>
    </div>
</template>
