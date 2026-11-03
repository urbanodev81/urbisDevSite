<script setup>
import { ref, watch } from 'vue';
import { AlertTriangle } from 'lucide-vue-next';
import DangerButton from './DangerButton.vue';
import SecondaryButton from './SecondaryButton.vue';

/**
 * Confirmação em dois passos, com o MESMO nome do componente do SSB e do BHD.
 *
 * O botão vira "tem certeza?" no lugar, sem modal. Modal para cada exclusão de
 * linha de tabela é fricção que ensina a clicar em "sim" sem ler — e aí o
 * segundo passo não protege mais nada.
 *
 * `nome` aparece na confirmação porque exclusão sem nome é exclusão às cegas:
 * "remover show" e "remover Saraiva's · 29/08" custam o mesmo e só o segundo
 * dá chance de perceber a linha errada.
 */
const props = defineProps({
    nome: { type: String, default: null },
    rotulo: { type: String, default: 'Remover' },
    // O verbo da pergunta. "Arquivar" onde a ação tem volta — chamar de
    // "remover" o que só guarda assusta quem opera e mente sobre o que acontece.
    verbo: { type: String, default: 'Remover' },
});

const emit = defineEmits(['confirmar']);

const armado = ref(false);
let relogio = null;

// Volta ao normal sozinho depois de 5 segundos: um botão vermelho "tem
// certeza?" esquecido na tela é o que a próxima pessoa clica sem contexto.
watch(armado, (valor) => {
    clearTimeout(relogio);

    if (valor) {
        relogio = setTimeout(() => (armado.value = false), 5000);
    }
});
</script>

<template>
    <div class="inline-flex items-center gap-2">
        <SecondaryButton v-if="!armado" @click="armado = true">
            <AlertTriangle class="h-4 w-4" aria-hidden="true" />
            {{ rotulo }}
        </SecondaryButton>

        <template v-else>
            <span class="text-sm text-fg-muted">
                {{ verbo }}<template v-if="nome"> <strong class="text-fg">{{ nome }}</strong></template>?
            </span>
            <DangerButton @click="emit('confirmar'); armado = false">Sim, {{ verbo.toLowerCase() }}</DangerButton>
            <SecondaryButton @click="armado = false">Cancelar</SecondaryButton>
        </template>
    </div>
</template>
