<script setup>
/*
 * Leitores do blog (04/11/2026, bloco 3b da 246): o que foi lido, o que
 * ajudou e o que trouxe contato, texto a texto. A contagem é nossa (sem
 * script de terceiro, sem IP): uma leitura por pessoa por visita, robô fora.
 */
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import EstadoVazio from '@/Components/EstadoVazio.vue';

defineProps({
    textos: { type: Array, default: () => [] },
    faltou: { type: Array, default: () => [] },
    totais: { type: Object, required: true },
});

const n = (v) => v.toLocaleString('pt-BR');
</script>

<template>
    <Head title="Leitores do blog" />

    <AuthenticatedLayout>
        <PageHeader
            titulo="Leitores do blog"
            descricao="Leituras contadas pelo próprio site (sem Google Analytics, sem cookie de rastreio), respostas do &quot;Este texto ajudou?&quot; e contatos que vieram de cada texto."
        />

        <div class="mb-6 grid gap-4 sm:grid-cols-2">
            <div class="rounded-card border border-line bg-surface p-5">
                <p class="text-sm text-fg-muted">Leituras nos últimos 30 dias</p>
                <p class="mt-1 text-3xl font-bold text-fg">{{ n(totais.trinta) }}</p>
            </div>
            <div class="rounded-card border border-line bg-surface p-5">
                <p class="text-sm text-fg-muted">Leituras hoje</p>
                <p class="mt-1 text-3xl font-bold text-fg">{{ n(totais.hoje) }}</p>
            </div>
        </div>

        <div v-if="textos.length" class="overflow-x-auto rounded-lg border border-line bg-surface">
            <table class="w-full min-w-[720px] text-sm">
                <caption class="sr-only">Leituras, respostas e contatos por texto, do mais lido em 30 dias ao menos lido</caption>
                <thead class="border-b border-line text-left text-xs uppercase text-fg-subtle">
                    <tr>
                        <th scope="col" class="px-4 py-3 font-medium">Texto</th>
                        <th scope="col" class="px-3 py-3 text-right font-medium">7 dias</th>
                        <th scope="col" class="px-3 py-3 text-right font-medium">30 dias</th>
                        <th scope="col" class="px-3 py-3 text-right font-medium">Total</th>
                        <th scope="col" class="px-3 py-3 text-right font-medium">Ajudou</th>
                        <th scope="col" class="px-3 py-3 text-right font-medium">Comentários</th>
                        <th scope="col" class="px-4 py-3 text-right font-medium">Contatos</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    <tr v-for="t in textos" :key="t.slug">
                        <th scope="row" class="px-4 py-3 text-left font-normal">
                            <a :href="t.url" target="_blank" rel="noopener" class="font-medium text-fg underline decoration-line underline-offset-4 hover:text-accent">{{ t.titulo }}<span class="sr-only"> (abre em outra aba)</span></a>
                            <span class="block font-mono text-xs text-fg-subtle">{{ t.data }}</span>
                        </th>
                        <td class="px-3 py-3 text-right font-mono">{{ n(t.leituras.sete) }}</td>
                        <td class="px-3 py-3 text-right font-mono">{{ n(t.leituras.trinta) }}</td>
                        <td class="px-3 py-3 text-right font-mono">{{ n(t.leituras.total) }}</td>
                        <td class="px-3 py-3 text-right font-mono">
                            <template v-if="t.votos.pct !== null">{{ t.votos.pct }}% <span class="text-xs text-fg-subtle">({{ t.votos.sim }}/{{ t.votos.sim + t.votos.nao }})</span></template>
                            <span v-else class="text-fg-subtle">—</span>
                        </td>
                        <td class="px-3 py-3 text-right font-mono">
                            {{ t.comentarios.aprovados }}<span v-if="t.comentarios.pendentes" class="text-xs text-warning"> +{{ t.comentarios.pendentes }} aguardando</span>
                        </td>
                        <td class="px-4 py-3 text-right font-mono">{{ t.contatos }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <EstadoVazio v-else icone="inbox" titulo="Nenhum texto publicado" descricao="Quando houver texto no ar, as leituras aparecem aqui." />

        <section class="mt-8" aria-labelledby="titulo-faltou">
            <h2 id="titulo-faltou" class="font-display text-lg font-bold text-fg">O que faltou</h2>
            <p class="mt-1 text-sm text-fg-muted">O que os leitores escreveram depois de responder "Não" ao "Este texto ajudou?". Os 20 mais recentes.</p>
            <ul v-if="faltou.length" class="mt-3 divide-y divide-line rounded-lg border border-line bg-surface">
                <li v-for="f in faltou" :key="f.id" class="px-4 py-3 text-sm">
                    <p class="text-xs text-fg-subtle">{{ f.titulo }} · {{ f.quando }}</p>
                    <p class="mt-1 whitespace-pre-line text-fg">{{ f.texto }}</p>
                </li>
            </ul>
            <p v-else class="mt-3 text-sm text-fg-muted">Ninguém escreveu ainda.</p>
        </section>
    </AuthenticatedLayout>
</template>
