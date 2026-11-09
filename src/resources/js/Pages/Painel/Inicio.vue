<script setup>
/*
 * Início do painel (03/11/2026, bloco 1 da pendência 246).
 *
 * Mostra o que já existe: o blog (lido dos `.md`, como o site lê) e, para quem
 * pode ver a trilha, o que mudou por último no painel. Desde o bloco 2
 * (04/11/2026), os contatos que esperam resposta.
 */
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';

defineProps({
    contatos: { type: Object, default: null },
    visitas: { type: Object, default: null },
    blog: { type: Object, required: true },
    atividade: { type: Array, default: null },
});
</script>

<template>
    <Head title="Início" />

    <AuthenticatedLayout>
        <PageHeader
            titulo="Início"
            descricao="O painel do site. Blog e configurações chegam aqui nas próximas etapas."
        />

        <div class="grid gap-4 lg:grid-cols-2">
            <section v-if="contatos !== null" class="rounded-card border border-line bg-surface p-6" aria-labelledby="titulo-contatos">
                <h2 id="titulo-contatos" class="font-display text-lg font-bold text-fg">Contatos</h2>

                <p class="mt-3 text-3xl font-bold text-fg">
                    {{ contatos.novos }}
                    <span class="text-base font-normal text-fg-muted">
                        esperando resposta
                    </span>
                </p>

                <p v-if="contatos.semAviso > 0" class="mt-2 text-sm text-danger">
                    {{ contatos.semAviso === 1 ? '1 chegou' : `${contatos.semAviso} chegaram` }} sem o e-mail de aviso.
                </p>

                <ul v-if="contatos.esperando.length" class="mt-4 divide-y divide-line">
                    <li v-for="c in contatos.esperando" :key="c.uuid" class="py-2">
                        <Link :href="route('painel.contatos.show', c.uuid)" class="flex items-baseline justify-between gap-4 text-sm">
                            <span class="min-w-0">
                                <span class="block truncate font-medium text-fg">{{ c.nome }}</span>
                                <span class="block truncate text-xs text-fg-subtle">{{ c.origem }}</span>
                            </span>
                            <span class="shrink-0 font-mono text-xs text-fg-muted">{{ c.dias === 0 ? 'hoje' : `${c.dias} d` }}</span>
                        </Link>
                    </li>
                </ul>
                <p v-else class="mt-2 text-sm text-fg-muted">Ninguém esperando. Contato novo aparece aqui sozinho.</p>

                <Link :href="route('painel.contatos.index')" class="mt-4 inline-block text-sm text-accent underline">Abrir a caixa de contatos</Link>
            </section>

            <section v-if="visitas !== null" class="rounded-card border border-line bg-surface p-6" aria-labelledby="titulo-visitas">
                <h2 id="titulo-visitas" class="font-display text-lg font-bold text-fg">Visitas do site</h2>

                <p class="mt-3 text-3xl font-bold tabular-nums text-fg">
                    {{ visitas.atual.visitantes.toLocaleString('pt-BR') }}
                    <span class="text-base font-normal text-fg-muted">
                        {{ visitas.atual.visitantes === 1 ? 'visita' : 'visitas' }} nos últimos 7 dias
                    </span>
                </p>
                <p class="mt-2 text-sm text-fg-muted">
                    {{ visitas.atual.visualizacoes.toLocaleString('pt-BR') }} {{ visitas.atual.visualizacoes === 1 ? 'página aberta' : 'páginas abertas' }};
                    nos 7 dias antes, {{ visitas.anterior.visitantes.toLocaleString('pt-BR') }} {{ visitas.anterior.visitantes === 1 ? 'visita' : 'visitas' }}.
                </p>
                <p v-if="!visitas.desde" class="mt-2 text-sm text-fg-muted">A contagem começa na primeira visita depois da publicação.</p>

                <Link :href="route('painel.visitas')" class="mt-4 inline-block text-sm text-accent underline">Ver dia a dia e as páginas mais abertas</Link>
            </section>

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

                <p v-if="blog.comentariosPendentes" class="mt-2 text-sm text-fg">
                    <Link :href="route('painel.blog.comentarios.index')" class="text-accent underline">
                        {{ blog.comentariosPendentes === 1 ? '1 comentário aguardando' : `${blog.comentariosPendentes} comentários aguardando` }} moderação
                    </Link>
                </p>

                <h3 class="mt-6 font-label text-label uppercase text-fg-subtle">Agendados</h3>
                <ul v-if="blog.agendados.length" class="mt-2 divide-y divide-line">
                    <li v-for="texto in blog.agendados" :key="texto.slug" class="flex items-baseline justify-between gap-4 py-2">
                        <a
                            v-if="texto.previa"
                            :href="texto.previa"
                            target="_blank"
                            rel="noopener"
                            class="text-sm text-fg underline decoration-line underline-offset-4 hover:text-accent focus-visible:outline-2"
                        >{{ texto.titulo }} <span class="sr-only">(abre a prévia em outra aba)</span></a>
                        <span v-else class="text-sm text-fg">{{ texto.titulo }}</span>
                        <span class="shrink-0 font-mono text-xs text-fg-muted">{{ texto.data }}</span>
                    </li>
                </ul>
                <p v-else class="mt-2 text-sm text-fg-muted">Nenhum texto agendado.</p>

                <Link :href="route('painel.blog.leitores.index')" class="mt-6 inline-block text-sm text-accent underline">Ver o que os leitores leram</Link>
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
