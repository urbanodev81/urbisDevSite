<script setup>
import { Link } from '@inertiajs/vue3';
import { ChevronLeft } from 'lucide-vue-next';

/**
 * Cabeçalho de tela. Mesmo nome do componente do Sócrates e do USI.
 *
 * O título usa a família de display — é a fonte de cartaz de show, e é ela que
 * diz, na primeira olhada, que este painel é da banda e não um admin genérico.
 */
defineProps({
    titulo: { type: String, required: true },
    descricao: { type: String, default: null },
    voltarPara: { type: String, default: null },
    voltarRotulo: { type: String, default: 'Voltar' },
});
</script>

<template>
    <header class="mb-6" data-tour="cabecalho">
        <Link
            v-if="voltarPara"
            :href="voltarPara"
            class="mb-2 inline-flex items-center gap-1 font-label text-label uppercase text-fg-subtle
                   transition hover:text-accent"
        >
            <ChevronLeft class="h-4 w-4" aria-hidden="true" />
            {{ voltarRotulo }}
        </Link>

        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="font-display text-3xl font-extrabold uppercase leading-none tracking-tight text-fg">
                    {{ titulo }}
                </h1>
                <p v-if="descricao" class="mt-2 max-w-2xl text-sm text-fg-muted">{{ descricao }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-2" data-tour="acoes">
                <slot name="acoes" />
            </div>
        </div>
    </header>
</template>
