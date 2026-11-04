<script setup>
/*
 * Contatos (04/11/2026, bloco 2 da 246): quem escreveu pelo formulário da home
 * ou deixou o contato para abrir uma demonstração. Sem filtro, a tela é uma
 * FILA: só os novos, do que espera há mais tempo para o mais recente.
 */
import { Head, Link } from '@inertiajs/vue3';
import { MailWarning } from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import AbasDeArquivados from '@/Components/AbasDeArquivados.vue';
import EstadoVazio from '@/Components/EstadoVazio.vue';
import Chip from '@/Components/Chip.vue';

const props = defineProps({
    contatos: { type: Object, required: true },
    filtro: { type: String, default: 'novo' },
    status: { type: Array, default: () => [] },
    arquivados: { type: Boolean, default: false },
    totais: { type: Array, default: () => [0, 0] },
    resumo: { type: Object, required: true },
});

const filtros = [...props.status, { valor: 'todos', rotulo: 'Todos' }];

const espera = (dias) => (dias === 0 ? 'hoje' : dias === 1 ? 'há 1 dia' : `há ${dias} dias`);
</script>

<template>
    <Head title="Contatos" />

    <AuthenticatedLayout>
        <PageHeader
            titulo="Contatos"
            descricao="Quem escreveu pelo site ou abriu uma demonstração. O e-mail de aviso continua saindo; aqui fica a garantia."
        />

        <p
            v-if="resumo.semAviso > 0"
            class="mb-4 flex items-center gap-2 rounded-md border border-warning bg-warning-subtle px-4 py-3 text-sm text-fg"
            role="status"
        >
            <MailWarning class="h-5 w-5 shrink-0 text-warning" aria-hidden="true" />
            {{ resumo.semAviso === 1 ? '1 contato chegou sem' : `${resumo.semAviso} contatos chegaram sem` }}
            o e-mail de aviso (o envio falhou). Eles estão aqui, guardados.
        </p>

        <AbasDeArquivados
            rota="painel.contatos.index"
            :aberta="arquivados"
            :totais="totais"
            :rotulos="['Em uso', 'Arquivados']"
            descricao="Contatos em uso ou arquivados"
        />

        <nav v-if="!arquivados" class="mb-4 flex flex-wrap gap-1" aria-label="Filtrar por situação">
            <Link
                v-for="f in filtros"
                :key="f.valor"
                :href="route('painel.contatos.index', f.valor === 'novo' ? {} : { status: f.valor })"
                class="flex min-h-[44px] items-center rounded-md px-3 text-sm font-medium"
                :class="filtro === f.valor ? 'bg-surface-sunken text-fg ring-1 ring-line-strong' : 'text-fg-muted hover:bg-surface-sunken'"
                :aria-current="filtro === f.valor ? 'page' : undefined"
            >
                {{ f.rotulo }}<template v-if="f.valor === 'novo'"> ({{ resumo.novos }})</template>
            </Link>
        </nav>

        <div v-if="contatos.data.length" class="overflow-hidden rounded-lg border border-line bg-surface">
            <ul class="divide-y divide-line">
                <li v-for="c in contatos.data" :key="c.uuid">
                    <Link
                        :href="route('painel.contatos.show', c.uuid)"
                        class="flex flex-wrap items-start gap-x-4 gap-y-2 px-4 py-4 transition hover:bg-surface-sunken sm:px-5"
                    >
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-semibold text-fg">{{ c.nome }}</p>
                            <p class="truncate text-sm text-fg-muted">{{ c.email }} · {{ c.telefone }}</p>
                            <p class="mt-1 text-xs text-fg-subtle">{{ c.origem }}</p>
                            <p v-if="c.trecho" class="mt-2 line-clamp-2 text-sm text-fg-muted">{{ c.trecho }}</p>
                        </div>

                        <div class="flex flex-col items-end gap-2">
                            <Chip :rotulo="c.status.rotulo" :token="c.status.token" :icone="c.status.icone" />
                            <Chip v-if="c.emailFalhou" rotulo="Aviso não saiu" token="danger" icone="mail-warning" />
                            <span class="font-mono text-xs text-fg-subtle" :title="c.recebidoEm">{{ espera(c.diasEsperando) }}</span>
                        </div>
                    </Link>
                </li>
            </ul>
        </div>

        <EstadoVazio
            v-else
            icone="inbox"
            :titulo="arquivados ? 'Nada arquivado' : filtro === 'novo' ? 'Ninguém esperando resposta' : 'Nenhum contato nesta situação'"
            descricao="Contato novo chega aqui sozinho, assim que alguém envia o formulário do site ou abre uma demonstração."
        />

        <nav v-if="contatos.links.length > 3" class="mt-6 flex flex-wrap gap-1" aria-label="Paginação">
            <component
                :is="link.url ? Link : 'span'"
                v-for="link in contatos.links"
                :key="link.label"
                :href="link.url"
                class="min-h-[44px] rounded-md px-3 py-2 text-sm"
                :class="link.active ? 'bg-accent text-fg-on-accent' : link.url ? 'text-fg-muted hover:bg-surface-sunken' : 'text-fg-subtle opacity-50'"
                v-html="link.label"
            />
        </nav>
    </AuthenticatedLayout>
</template>
