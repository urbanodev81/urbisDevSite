<script setup>
import { router } from '@inertiajs/vue3';
import { MonitorSmartphone, Trash2 } from 'lucide-vue-next';
import SecondaryButton from '@/Components/SecondaryButton.vue';

/*
 * Os aparelhos em que a pessoa pediu para não digitar o código. A lista existe
 * para ela conferir e remover: confiar sem porta de saída é porta que não fecha.
 */
defineProps({
    aparelhos: { type: Array, default: () => [] },
});

const remover = (uuid) =>
    router.delete(route('two-factor.aparelhos.destroy', uuid), { preserveScroll: true });

const removerTodos = () =>
    router.delete(route('two-factor.aparelhos.destroy-all'), { preserveScroll: true });
</script>

<template>
    <section>
        <header class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0 flex-1">
                <h2 class="text-lg font-medium text-fg">Aparelhos confiáveis</h2>
                <p class="mt-1 text-sm text-fg-subtle">
                    Nestes aparelhos o acesso pede só a senha. Trocar a senha ou desligar a verificação em
                    duas etapas remove todos.
                </p>
            </div>
            <SecondaryButton v-if="aparelhos.length > 1" type="button" class="min-h-11" @click="removerTodos">
                Remover todos
            </SecondaryButton>
        </header>

        <div v-if="!aparelhos.length" class="mt-4 flex items-start gap-3 text-sm text-fg-subtle">
            <MonitorSmartphone class="mt-0.5 h-5 w-5 shrink-0 text-fg-subtle" aria-hidden="true" />
            <p>
                Nenhum aparelho confiável. Para confiar em um, marque a opção na tela do código, ao entrar.
            </p>
        </div>

        <ul v-else class="mt-4 divide-y divide-line">
            <li v-for="a in aparelhos" :key="a.uuid" class="flex items-center gap-3 py-3">
                <MonitorSmartphone class="h-5 w-5 shrink-0 text-fg-subtle" aria-hidden="true" />
                <div class="min-w-0 flex-1">
                    <p class="truncate font-medium text-fg">
                        {{ a.apelido }}
                        <span v-if="a.atual" class="ml-1 text-xs font-normal text-fg-subtle">
                            (este aparelho)
                        </span>
                    </p>
                    <p class="mt-0.5 text-sm text-fg-subtle">
                        <span v-if="a.descricao">{{ a.descricao }} · </span>último uso em {{ a.ultimoUso }} ·
                        vale até {{ a.expiraEm }}
                    </p>
                </div>
                <button
                    type="button"
                    class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-md text-fg-subtle transition-colors hover:text-fg"
                    :aria-label="`Remover o aparelho ${a.apelido}`"
                    @click="remover(a.uuid)"
                >
                    <Trash2 class="h-4 w-4" aria-hidden="true" />
                </button>
            </li>
        </ul>
    </section>
</template>
