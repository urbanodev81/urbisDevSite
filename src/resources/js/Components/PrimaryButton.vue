<script setup>
/**
 * A ação primária da tela — e há exatamente UMA por tela (CORE §6).
 *
 * Ela é preenchida com a cor da MARCA e não com neutro-escuro, ao contrário
 * do Sócrates. A régua do CORE §6 é o peso, não a tinta: ele quer que nada
 * dispute o olho com a ação principal, e diz que a escolha da tinta é de cada
 * produto. Lá o violeta ficava a 22° do canvas lavanda e o neutro separava
 * melhor; aqui o magenta está sozinho numa página de creme e de palco, com
 * 8,8:1 contra o fundo claro e 9,0:1 contra o escuro. É ele que separa.
 */
defineProps({ type: { type: String, default: 'submit' } });
</script>

<template>
    <button
        :type="type"
        class="inline-flex items-center justify-center gap-2 rounded-md bg-accent px-4 py-2
               text-sm font-semibold text-fg-on-accent transition
               hover:bg-accent-hover focus-visible:outline focus-visible:outline-2
               focus-visible:outline-offset-2 focus-visible:outline-accent-ring
               disabled:cursor-not-allowed disabled:opacity-50
               min-h-[44px]"
    >
        <slot />
    </button>
</template>
