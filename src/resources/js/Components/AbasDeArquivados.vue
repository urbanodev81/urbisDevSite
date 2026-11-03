<script setup>
import { Link } from '@inertiajs/vue3';
import { Archive } from 'lucide-vue-next';

/**
 * As duas abas de toda lista com lote: o que está em uso e o que foi
 * arquivado. Arquivar não apaga — é aqui que a pessoa vê para onde foi.
 */
defineProps({
    // Nome da rota da lista, ex.: 'painel.videos.index'.
    rota: { type: String, required: true },
    // O parâmetro da aba: 'arquivados' ou 'arquivadas', como o controller lê.
    parametro: { type: String, default: 'arquivados' },
    aberta: { type: Boolean, default: false },
    // [rótulo da aba ativa, rótulo da aba de arquivados]
    rotulos: { type: Array, default: () => ['Em uso', 'Arquivados'] },
    // [quantos em uso, quantos arquivados]
    totais: { type: Array, default: () => [0, 0] },
    descricao: { type: String, default: 'Em uso ou arquivados' },
});

const ativa = 'bg-accent text-fg-on-accent';
const inativa = 'text-fg-muted hover:bg-surface-sunken';
</script>

<template>
    <nav class="mb-4 flex flex-wrap gap-1" :aria-label="descricao" data-tour="arquivados">
        <Link :href="route(rota)" class="flex min-h-[44px] items-center gap-2 rounded-md px-4 text-sm font-semibold" :class="aberta ? inativa : ativa" :aria-current="aberta ? undefined : 'page'">
            {{ rotulos[0] }} ({{ totais[0] }})
        </Link>
        <Link :href="route(rota, { [parametro]: 1 })" class="flex min-h-[44px] items-center gap-2 rounded-md px-4 text-sm font-semibold" :class="aberta ? ativa : inativa" :aria-current="aberta ? 'page' : undefined">
            <Archive class="h-4 w-4" aria-hidden="true" />{{ rotulos[1] }} ({{ totais[1] }})
        </Link>
    </nav>
</template>
