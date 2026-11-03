<script setup>
/*
 * Início do painel (03/11/2026, bloco 1 da pendência 246).
 *
 * Mostra o que já existe: o blog (lido dos `.md`, como o site lê) e, para quem
 * pode ver a trilha, o que mudou por último no painel. A caixa de contatos
 * entra aqui no bloco 2.
 */
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';

defineProps({
    blog: { type: Object, required: true },
    atividade: { type: Array, default: null },
});
</script>

<template>
    <Head title="Início" />

    <AuthenticatedLayout>
        <PageHeader
            titulo="Início"
            descricao="O painel do site. Contatos, blog e configurações chegam aqui por etapas."
        />

        <div class="grid gap-4 lg:grid-cols-2">
            <section class="rounded-card border border-line bg-surface p-6" aria-labelledby="titulo-blog">
                <h2 id="titulo-blog" class="font-display text-lg font-bold text-fg">Blog</h2>

                <p class="mt-3 text-3xl font-bold text-fg">
                    {{ blog.publicados }}
                    <span class="text-base font-normal text-fg-muted">
                        {{ blog.publicados === 1 ? 'texto publicado' : 'textos publicados' }}
                    </span>
                </p>

                <p v-if="blog.ultimo" class="mt-2 text-sm text-fg-muted">
                    O mais recente:
                    <a :href="blog.ultimo.url" target="_blank" rel="noopener" class="text-accent underline">
                        {{ blog.ultimo.titulo }}
                    </a>
                    ({{ blog.ultimo.data }})
                </p>

                <h3 class="mt-6 font-label text-label uppercase text-fg-subtle">Agendados</h3>
                <ul v-if="blog.agendados.length" class="mt-2 divide-y divide-line">
                    <li v-for="texto in blog.agendados" :key="texto.slug" class="flex items-baseline justify-between gap-4 py-2">
                        <span class="text-sm text-fg">{{ texto.titulo }}</span>
                        <span class="shrink-0 font-mono text-xs text-fg-muted">{{ texto.data }}</span>
                    </li>
                </ul>
                <p v-else class="mt-2 text-sm text-fg-muted">Nenhum texto agendado.</p>

                <p class="mt-6 text-xs text-fg-subtle">
                    Por enquanto o texto ainda se escreve pelo GitHub, como antes
                    (o passo a passo está em <code>resources/blog/_LEIA.md</code>).
                </p>
            </section>

            <section
                v-if="atividade !== null"
                class="rounded-card border border-line bg-surface p-6"
                aria-labelledby="titulo-atividade"
            >
                <h2 id="titulo-atividade" class="font-display text-lg font-bold text-fg">Últimas alterações</h2>

                <ul v-if="atividade.length" class="mt-3 divide-y divide-line">
                    <li v-for="(linha, i) in atividade" :key="i" class="py-2 text-sm">
                        <p class="font-mono text-xs text-fg-muted">{{ linha.quando }}</p>
                        <p>
                            <span class="font-medium text-fg">{{ linha.quem }}</span>
                            {{ ' ' }}
                            <span class="text-fg-muted">{{ linha.evento }} · {{ linha.tipo }}<template v-if="linha.campo"> · {{ linha.campo }}</template></span>
                        </p>
                    </li>
                </ul>
                <p v-else class="mt-3 text-sm text-fg-muted">Nada alterado ainda.</p>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
