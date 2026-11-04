<script setup>
/*
 * Comentários do blog (04/11/2026, bloco 3b da 246). Sem filtro, a tela é a
 * FILA de moderação: os que aguardam, do mais antigo ao mais novo. Nada
 * aparece no site sem passar por aqui. A resposta da equipe sai no texto como
 * "urbisDev respondeu", embaixo do comentário.
 */
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import AbasDeArquivados from '@/Components/AbasDeArquivados.vue';
import EstadoVazio from '@/Components/EstadoVazio.vue';
import Chip from '@/Components/Chip.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TwoStepConfirm from '@/Components/TwoStepConfirm.vue';

const props = defineProps({
    comentarios: { type: Object, required: true },
    filtro: { type: String, default: 'pendente' },
    status: { type: Array, default: () => [] },
    arquivados: { type: Boolean, default: false },
    totais: { type: Array, default: () => [0, 0] },
    pendentes: { type: Number, default: 0 },
});

// Resposta em edição, por comentário. Abre só quando alguém clica em "Responder".
const respostas = reactive({});
const abrirResposta = (c) => { respostas[c.uuid] = c.resposta ?? ''; };

const mudar = (c, dados) => router.put(route('painel.blog.comentarios.update', c.uuid), dados, {
    preserveScroll: true,
    onSuccess: () => { delete respostas[c.uuid]; },
});

const campo = 'w-full rounded-md border-line-input bg-surface text-sm text-fg focus:border-line-strong focus:ring-2 focus:ring-accent-ring';
</script>

<template>
    <Head title="Comentários" />

    <AuthenticatedLayout>
        <PageHeader
            titulo="Comentários"
            descricao="O que os leitores escreveram no fim dos textos. Só aparece no site depois de aprovado aqui; o e-mail de quem comentou nunca é publicado."
        />

        <AbasDeArquivados
            rota="painel.blog.comentarios.index"
            :aberta="arquivados"
            :totais="totais"
            :rotulos="['Em uso', 'Arquivados']"
            descricao="Comentários em uso ou arquivados"
        />

        <nav v-if="!arquivados" class="mb-4 flex flex-wrap gap-1" aria-label="Filtrar por situação">
            <Link
                v-for="s in status"
                :key="s.valor"
                :href="route('painel.blog.comentarios.index', s.valor === 'pendente' ? {} : { status: s.valor })"
                class="flex min-h-[44px] items-center rounded-md px-3 text-sm font-medium"
                :class="filtro === s.valor ? 'bg-surface-sunken text-fg ring-1 ring-line-strong' : 'text-fg-muted hover:bg-surface-sunken'"
                :aria-current="filtro === s.valor ? 'page' : undefined"
            >
                {{ s.rotulo }}<template v-if="s.valor === 'pendente'"> ({{ pendentes }})</template>
            </Link>
        </nav>

        <ul v-if="comentarios.data.length" class="grid gap-4">
            <li v-for="c in comentarios.data" :key="c.uuid" class="rounded-lg border border-line bg-surface p-4 sm:p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-semibold text-fg">{{ c.nome }}</p>
                        <p class="truncate text-sm text-fg-muted">{{ c.email }}</p>
                        <p class="mt-1 text-xs text-fg-subtle">
                            em
                            <a v-if="c.texto.url" :href="c.texto.url" target="_blank" rel="noopener" class="underline hover:text-accent">{{ c.texto.titulo }}<span class="sr-only"> (abre em outra aba)</span></a>
                            <span v-else>{{ c.texto.titulo }} (fora do ar)</span>
                            · {{ c.recebidoEm }}
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <Chip v-if="c.linkedin" rotulo="Verificado pelo LinkedIn" token="info" icone="badge-check" />
                        <Chip :rotulo="c.status.rotulo" :token="c.status.token" :icone="c.status.icone" />
                    </div>
                </div>

                <p class="mt-3 whitespace-pre-line text-sm text-fg">{{ c.corpo }}</p>

                <div v-if="c.resposta && respostas[c.uuid] === undefined" class="mt-3 border-l-4 border-accent bg-surface-sunken px-3 py-2 text-sm">
                    <p class="font-semibold text-fg">urbisDev respondeu</p>
                    <p class="whitespace-pre-line text-fg-muted">{{ c.resposta }}</p>
                </div>

                <form v-if="respostas[c.uuid] !== undefined" class="mt-3 grid gap-2" @submit.prevent="mudar(c, { resposta: respostas[c.uuid] })">
                    <label :for="`resposta-${c.uuid}`" class="text-sm font-medium text-fg">Resposta da equipe (aparece como "urbisDev")</label>
                    <textarea :id="`resposta-${c.uuid}`" v-model="respostas[c.uuid]" rows="3" maxlength="3000" :class="campo" />
                    <div class="flex flex-wrap gap-2">
                        <PrimaryButton type="submit">Salvar resposta</PrimaryButton>
                        <SecondaryButton type="button" @click="delete respostas[c.uuid]">Cancelar</SecondaryButton>
                    </div>
                    <p v-if="c.status.valor !== 'aprovado'" class="text-xs text-fg-subtle">A resposta só aparece no site quando o comentário estiver aprovado.</p>
                </form>

                <p v-if="c.moderado" class="mt-3 text-xs text-fg-subtle">Moderado em {{ c.moderado }}</p>

                <div v-if="!arquivados" class="mt-4 flex flex-wrap gap-2">
                    <PrimaryButton v-if="c.status.valor !== 'aprovado'" type="button" @click="mudar(c, { status: 'aprovado' })">Aprovar</PrimaryButton>
                    <SecondaryButton v-if="c.status.valor !== 'recusado'" type="button" @click="mudar(c, { status: 'recusado' })">
                        {{ c.status.valor === 'aprovado' ? 'Tirar do ar' : 'Recusar' }}
                    </SecondaryButton>
                    <SecondaryButton v-if="c.status.valor === 'recusado'" type="button" @click="mudar(c, { status: 'pendente' })">Voltar para a fila</SecondaryButton>
                    <SecondaryButton v-if="respostas[c.uuid] === undefined" type="button" @click="abrirResposta(c)">
                        {{ c.resposta ? 'Editar resposta' : 'Responder' }}
                    </SecondaryButton>
                    <TwoStepConfirm rotulo="Arquivar" verbo="Arquivar" :nome="c.nome" @confirmar="router.delete(route('painel.blog.comentarios.destroy', c.uuid), { preserveScroll: true })" />
                </div>
                <div v-else class="mt-4">
                    <SecondaryButton type="button" @click="router.patch(route('painel.blog.comentarios.restaurar', c.uuid), {}, { preserveScroll: true })">Restaurar</SecondaryButton>
                </div>
            </li>
        </ul>

        <EstadoVazio
            v-else
            icone="inbox"
            :titulo="arquivados ? 'Nada arquivado' : filtro === 'pendente' ? 'Nenhum comentário aguardando' : 'Nenhum comentário nesta situação'"
            descricao="Comentário novo chega aqui sozinho, e a equipe recebe um e-mail de aviso."
        />

        <nav v-if="comentarios.links.length > 3" class="mt-6 flex flex-wrap gap-1" aria-label="Paginação">
            <component
                :is="link.url ? Link : 'span'"
                v-for="link in comentarios.links"
                :key="link.label"
                :href="link.url"
                class="min-h-[44px] rounded-md px-3 py-2 text-sm"
                :class="link.active ? 'bg-accent text-fg-on-accent' : link.url ? 'text-fg-muted hover:bg-surface-sunken' : 'text-fg-subtle opacity-50'"
                v-html="link.label"
            />
        </nav>
    </AuthenticatedLayout>
</template>
