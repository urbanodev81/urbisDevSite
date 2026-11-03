<script setup>
import { onMounted, onUnmounted, watch } from 'vue';

/**
 * Cartão que entra deslizando pela direita — é o `TransitionRightCard` da casa
 * (SSB, Sócrates), com o nome em português como o resto deste repo.
 *
 * Superfície secundária (avisos, detalhe rápido, filtros) sempre entra por
 * aqui, nunca como dropdown ancorado no botão: dropdown de 20rem preso no
 * canto superior direito cobre o conteúdo da tela e não cabe no celular.
 *
 * Para diálogo que INTERROMPE (confirmar, formulário curto), o certo continua
 * sendo `Modal.vue` — este aqui é para consulta ao lado do trabalho.
 */
const props = defineProps({
    show: { type: Boolean, default: false },
    // Largura do cartão. `narrow` para lista de leitura, `wide` para conteúdo
    // com formulário. Mesmos nomes dos irmãos, para não ter que reaprender.
    variante: { type: String, default: 'default' },
    rotulo: { type: String, default: null },
});

const emit = defineEmits(['close']);

const larguras = {
    narrow: 'max-w-sm',
    default: 'max-w-lg',
    wide: 'max-w-xl',
};

const fecharComEsc = (e) => {
    if (e.key === 'Escape' && props.show) {
        e.preventDefault();
        emit('close');
    }
};

// Trava a rolagem do fundo enquanto o cartão está aberto: sem isso, rolar
// dentro da lista continua rolando a página atrás dela.
watch(
    () => props.show,
    (aberto) => {
        document.body.style.overflow = aberto ? 'hidden' : '';
    },
);

onMounted(() => document.addEventListener('keydown', fecharComEsc));

onUnmounted(() => {
    document.removeEventListener('keydown', fecharComEsc);
    document.body.style.overflow = '';
});
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition-opacity ease-linear duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity ease-linear duration-300"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="show"
                class="fixed inset-0 z-[60] bg-overlay/50 backdrop-blur-sm"
                aria-hidden="true"
                @click="emit('close')"
            />
        </Transition>

        <Transition
            enter-active-class="transform transition ease-in-out duration-300"
            enter-from-class="translate-x-full"
            enter-to-class="translate-x-0"
            leave-active-class="transform transition ease-in-out duration-300"
            leave-from-class="translate-x-0"
            leave-to-class="translate-x-full"
        >
            <div
                v-if="show"
                class="fixed inset-y-0 right-0 z-[70] flex w-full flex-col border-l border-line bg-surface shadow-2xl"
                :class="larguras[variante] || larguras.default"
                role="dialog"
                aria-modal="true"
                :aria-label="rotulo"
            >
                <slot />
            </div>
        </Transition>
    </Teleport>
</template>
