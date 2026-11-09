<script setup>
/*
 * Visitas do site (09/11/2026, pendência 263): os últimos 30 dias, dia a dia,
 * e as páginas mais abertas. Contagem do pacote urbanodev/visitas: sem cookie,
 * sem IP guardado, sem robô e sem quem está logado no painel.
 *
 * "Visitas" e não "pessoas": quem volta em três dias conta três. Saber quantas
 * pessoas DIFERENTES vieram no mês exigiria seguir cada uma o mês inteiro, que
 * é o que este contador se recusa a fazer.
 */
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import EstadoVazio from '@/Components/EstadoVazio.vue';

const props = defineProps({
    dias: { type: Number, default: 30 },
    erro: { type: Boolean, default: false },
    resumo: { type: Object, default: null },
    anterior: { type: Object, default: null },
    porDia: { type: Array, default: () => [] },
    paginas: { type: Array, default: () => [] },
    desde: { type: String, default: null },
});

const n = (v) => v.toLocaleString('pt-BR');

/** "+12% que os 30 dias anteriores", ou null quando não há base para comparar. */
const variacao = (atual, antes) => {
    if (!antes) return null;
    const pct = Math.round(((atual - antes) / antes) * 100);

    return pct === 0 ? 'igual aos 30 dias anteriores' : `${pct > 0 ? '+' : ''}${pct}% que os 30 dias anteriores`;
};

const maior = computed(() => Math.max(1, ...props.porDia.map((d) => d.visitantes)));
const vazio = computed(() => !props.erro && props.resumo && props.resumo.visualizacoes === 0);
const sobre = ref(null);

const tentarDeNovo = () => router.reload();
</script>

<template>
    <Head title="Visitas do site" />

    <AuthenticatedLayout>
        <PageHeader
            titulo="Visitas do site"
            :descricao="`Últimos ${dias} dias. Contadas pelo próprio site: sem Google Analytics, sem cookie, sem guardar o IP de ninguém. Robôs e quem está logado aqui não entram.`"
        />

        <div v-if="erro" role="alert" class="rounded-card border border-line bg-surface p-6">
            <p class="font-medium text-fg">Não deu para ler as visitas agora.</p>
            <p class="mt-1 text-sm text-fg-muted">O banco do site não respondeu. O site continua no ar e contando.</p>
            <button type="button" class="mt-4 min-h-11 rounded-md border border-line px-4 text-sm text-fg hover:border-accent" @click="tentarDeNovo">
                Tentar de novo
            </button>
        </div>

        <EstadoVazio
            v-else-if="vazio"
            icone="inbox"
            titulo="Nenhuma visita contada ainda"
            :descricao="desde ? `A contagem começou em ${desde}. Abra o site numa janela anônima para ver o número subir.` : 'A contagem começa na primeira visita depois da publicação. Abra o site numa janela anônima para ver o número subir.'"
        />

        <template v-else-if="resumo">
            <div class="mb-6 grid gap-4 sm:grid-cols-3">
                <div class="rounded-card border border-line bg-surface p-5">
                    <p class="text-sm text-fg-muted">Visitas</p>
                    <p class="mt-1 text-3xl font-bold tabular-nums text-fg">{{ n(resumo.visitantes) }}</p>
                    <p v-if="variacao(resumo.visitantes, anterior?.visitantes)" class="mt-1 text-xs text-fg-subtle">
                        {{ variacao(resumo.visitantes, anterior.visitantes) }}
                    </p>
                    <p class="mt-1 text-xs text-fg-subtle">Uma por pessoa por dia.</p>
                </div>
                <div class="rounded-card border border-line bg-surface p-5">
                    <p class="text-sm text-fg-muted">Páginas abertas</p>
                    <p class="mt-1 text-3xl font-bold tabular-nums text-fg">{{ n(resumo.visualizacoes) }}</p>
                    <p v-if="resumo.visitantes" class="mt-1 text-xs text-fg-subtle">
                        {{ (resumo.visualizacoes / resumo.visitantes).toLocaleString('pt-BR', { maximumFractionDigits: 1 }) }} por visita
                    </p>
                </div>
                <div class="rounded-card border border-line bg-surface p-5">
                    <p class="text-sm text-fg-muted">Acessos de robô descartados</p>
                    <p class="mt-1 text-3xl font-bold tabular-nums text-fg">{{ n(resumo.robos) }}</p>
                    <p class="mt-1 text-xs text-fg-subtle">Google, prévia de link do WhatsApp e afins. Não entram nos números ao lado.</p>
                </div>
            </div>

            <section class="mb-6 rounded-card border border-line bg-surface p-5" aria-labelledby="titulo-por-dia">
                <div class="flex items-baseline justify-between gap-4">
                    <h2 id="titulo-por-dia" class="font-display text-lg font-bold text-fg">Visitas por dia</h2>
                    <p class="text-sm tabular-nums text-fg-muted" aria-live="polite">
                        <template v-if="sobre !== null">{{ porDia[sobre].rotulo }}: {{ n(porDia[sobre].visitantes) }} {{ porDia[sobre].visitantes === 1 ? 'visita' : 'visitas' }}, {{ n(porDia[sobre].visualizacoes) }} {{ porDia[sobre].visualizacoes === 1 ? 'página' : 'páginas' }}</template>
                        <template v-else>maior dia: {{ n(maior) }}</template>
                    </p>
                </div>

                <!-- Uma série só: sem legenda, o título nomeia. A tabela abaixo é a versão em texto. -->
                <div class="mt-4 flex h-40 items-end gap-0.5 border-b border-line" aria-hidden="true" @mouseleave="sobre = null">
                    <div
                        v-for="(d, i) in porDia"
                        :key="d.dia"
                        class="flex h-full flex-1 cursor-default items-end"
                        @mouseenter="sobre = i"
                    >
                        <div
                            class="w-full rounded-t bg-accent transition-opacity"
                            :class="sobre !== null && sobre !== i ? 'opacity-40' : ''"
                            :style="{ height: d.visitantes ? `${Math.max(2, (d.visitantes / maior) * 100)}%` : '0' }"
                        />
                    </div>
                </div>
                <div class="mt-1 flex justify-between font-mono text-xs text-fg-subtle" aria-hidden="true">
                    <span>{{ porDia[0]?.rotulo }}</span>
                    <span>{{ porDia[porDia.length - 1]?.rotulo }}</span>
                </div>

                <table class="sr-only">
                    <caption>Visitas e páginas abertas por dia, dos últimos {{ dias }} dias</caption>
                    <thead><tr><th scope="col">Dia</th><th scope="col">Visitas</th><th scope="col">Páginas abertas</th></tr></thead>
                    <tbody>
                        <tr v-for="d in porDia" :key="d.dia"><th scope="row">{{ d.rotulo }}</th><td>{{ d.visitantes }}</td><td>{{ d.visualizacoes }}</td></tr>
                    </tbody>
                </table>
            </section>

            <div class="overflow-x-auto rounded-lg border border-line bg-surface">
                <table class="w-full min-w-[480px] text-sm">
                    <caption class="px-4 pt-4 text-left font-display text-lg font-bold text-fg">Páginas mais abertas</caption>
                    <thead class="border-b border-line text-left text-xs uppercase text-fg-subtle">
                        <tr>
                            <th scope="col" class="px-4 py-3 font-medium">Página</th>
                            <th scope="col" class="px-3 py-3 text-right font-medium">Visitas</th>
                            <th scope="col" class="px-4 py-3 text-right font-medium">Aberturas</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        <tr v-for="p in paginas" :key="p.caminho">
                            <th scope="row" class="px-4 py-3 text-left font-normal">
                                <a :href="p.caminho" target="_blank" rel="noopener" class="font-medium text-fg underline decoration-line underline-offset-4 hover:text-accent">{{ p.titulo }}<span class="sr-only"> (abre em outra aba)</span></a>
                                <span v-if="p.titulo !== p.caminho" class="block font-mono text-xs text-fg-subtle">{{ p.caminho }}</span>
                            </th>
                            <td class="px-3 py-3 text-right tabular-nums text-fg">{{ n(p.visitantes) }}</td>
                            <td class="px-4 py-3 text-right tabular-nums text-fg-muted">{{ n(p.visualizacoes) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <p v-if="desde" class="mt-4 text-xs text-fg-subtle">Contando desde {{ desde }}.</p>
        </template>
    </AuthenticatedLayout>
</template>
