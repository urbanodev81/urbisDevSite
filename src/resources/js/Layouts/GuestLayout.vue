<script setup>
</script>

<template>
    <!--
        A tela de login é a única do painel comprometida com o escuro: ela é a
        porta, e a porta usa a cara do site. Os tokens são os mesmos — o que
        muda é a classe `dark` forçada no contêiner, não um segundo conjunto de
        cor.
    -->
    <div class="dark flex min-h-screen flex-col items-center justify-center bg-bg px-4 py-10">
        <a href="/" class="mb-8 text-center">
            <p class="font-display text-4xl font-black uppercase leading-none text-fg">
                {{ $page.props.marca.nome }}
            </p>
            <p class="mt-2 font-label text-label uppercase tracking-[0.24em] text-accent">
                Painel
            </p>
        </a>

        <div class="w-full max-w-md rounded-lg border border-line bg-surface p-6 shadow-lg sm:p-8">
            <slot />
        </div>

        <p class="mt-8 text-center text-sm text-fg-subtle">
            <a href="/" class="underline transition hover:text-accent">
                Voltar para o site
            </a>
        </p>
    </div>
</template>
