<script setup>
/*
 * Blog no painel (bloco 3a da 246, 04/11/2026). Abas por situação; a de
 * "Em arquivo" lista os .md que o banco ainda não cobre — para ninguém achar
 * que sumiram, e para lembrar que esses se editam pelo GitHub (ou importando).
 */
import { Head, Link, router } from '@inertiajs/vue3';
import { ExternalLink, Eye, Plus } from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import EstadoVazio from '@/Components/EstadoVazio.vue';
import Chip from '@/Components/Chip.vue';
import { situacoes } from './situacoes';

const props = defineProps({
    aba: { type: String, required: true },
    contagem: { type: Object, required: true },
    textos: { type: Array, default: () => [] },
});

const abas = [
    { valor: 'publicado', rotulo: 'No ar' },
    { valor: 'agendado', rotulo: 'Agendados' },
    { valor: 'rascunho', rotulo: 'Rascunhos' },
    { valor: 'arquivado', rotulo: 'Arquivados' },
    { valor: 'arquivo', rotulo: 'Em arquivo (.md)' },
];

const vazio = {
    publicado: 'Nenhum texto do painel no ar ainda.',
    agendado: 'Nenhum texto agendado.',
    rascunho: 'Nenhum rascunho.',
    arquivado: 'Nada arquivado.',
    arquivo: 'Todos os .md já estão no painel.',
};

const restaurar = (uuid) => router.patch(route('painel.blog.textos.restaurar', uuid));
</script>

<template>
    <Head title="Blog" />

    <AuthenticatedLayout>
        <PageHeader titulo="Blog" descricao="Escreva, agende e publique sem deploy. Texto com data futura entra no ar sozinho no dia.">
            <template #acoes>
                <Link :href="route('painel.blog.textos.create')" class="inline-flex min-h-[44px] items-center gap-2 rounded-md bg-accent px-4 text-sm font-semibold text-fg-on-accent hover:bg-accent-hover">
                    <Plus class="h-4 w-4" aria-hidden="true" /> Novo texto
                </Link>
            </template>
        </PageHeader>

        <nav class="mb-4 flex flex-wrap gap-1" aria-label="Textos por situação">
            <Link
                v-for="a in abas"
                :key="a.valor"
                :href="route('painel.blog.textos.index', a.valor === 'publicado' ? {} : { aba: a.valor })"
                class="flex min-h-[44px] items-center rounded-md px-3 text-sm font-medium"
                :class="aba === a.valor ? 'bg-surface-sunken text-fg ring-1 ring-line-strong' : 'text-fg-muted hover:bg-surface-sunken'"
                :aria-current="aba === a.valor ? 'page' : undefined"
            >
                {{ a.rotulo }} ({{ contagem[a.valor] }})
            </Link>
        </nav>

        <p v-if="aba === 'arquivo'" class="mb-4 rounded-md border border-line bg-surface-sunken px-4 py-3 text-sm text-fg-muted">
            Estes textos moram em <code>src/resources/blog/*.md</code> e se editam pelo GitHub. Para editar aqui no
            painel, importe-os uma vez com <code>php artisan blog:importar --executar</code>. Depois disso, vale o painel,
            e o <code>.md</code> fica só de reserva.
        </p>

        <div v-if="textos.length" class="overflow-hidden rounded-lg border border-line bg-surface">
            <ul class="divide-y divide-line">
                <li v-for="t in textos" :key="t.uuid ?? t.slug" class="flex flex-wrap items-center gap-x-4 gap-y-2 px-4 py-4 sm:px-5">
                    <div class="min-w-0 flex-1">
                        <Link v-if="t.uuid && aba !== 'arquivado'" :href="route('painel.blog.textos.edit', t.uuid)" class="font-semibold text-fg hover:text-accent hover:underline">
                            {{ t.titulo }}
                        </Link>
                        <span v-else class="font-semibold text-fg">{{ t.titulo }}</span>
                        <p class="mt-1 text-xs text-fg-subtle">
                            <span class="font-mono">/blog/{{ t.slug }}</span> · {{ t.data }}<template v-if="t.secao"> · {{ t.secao }}</template>
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <Chip v-bind="situacoes[aba === 'arquivado' ? 'arquivado' : t.situacao]" />
                        <a v-if="t.previa" :href="t.previa" target="_blank" rel="noopener" class="inline-flex min-h-[44px] items-center gap-1 rounded-md px-2 text-sm text-fg-muted hover:bg-surface-sunken">
                            <Eye class="h-4 w-4" aria-hidden="true" /> Prévia
                        </a>
                        <a v-if="t.url" :href="t.url" target="_blank" rel="noopener" class="inline-flex min-h-[44px] items-center gap-1 rounded-md px-2 text-sm text-fg-muted hover:bg-surface-sunken">
                            <ExternalLink class="h-4 w-4" aria-hidden="true" /> Ver no site
                        </a>
                        <button v-if="aba === 'arquivado'" type="button" class="inline-flex min-h-[44px] items-center rounded-md px-3 text-sm font-medium text-accent hover:bg-surface-sunken" @click="restaurar(t.uuid)">
                            Restaurar
                        </button>
                    </div>
                </li>
            </ul>
        </div>

        <EstadoVazio v-else icone="file-text" :titulo="vazio[aba]" descricao="Texto novo começa como rascunho: só o painel vê até você escolher a data." />
    </AuthenticatedLayout>
</template>
