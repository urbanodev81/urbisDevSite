<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, nextTick, ref } from 'vue';
import { Copy, History, Plus, QrCode } from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import EstadoVazio from '@/Components/EstadoVazio.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import Chip from '@/Components/Chip.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TwoStepConfirm from '@/Components/TwoStepConfirm.vue';

const props = defineProps({
    compromissos: { type: Array, default: () => [] },
    totais: { type: Object, required: true },
    opcoes: { type: Object, required: true },
    hoje: { type: String, required: true },
});

/*
 * Financeiro (26/11/2026). Um formulário só, no topo, para cadastrar e para
 * editar (o desenho de Perguntas e Serviços); embaixo, a lista pelo próximo
 * vencimento. Nada se apaga: arquivar e estornar.
 */
const filtros = [
    { chave: 'abertos', rotulo: 'Em aberto' },
    { chave: 'atrasados', rotulo: 'Atrasados' },
    { chave: 'arquivados', rotulo: 'Arquivados' },
];
const filtro = ref('abertos');

const visiveis = computed(() => props.compromissos.filter((c) => {
    if (filtro.value === 'arquivados') return c.arquivado;
    if (filtro.value === 'atrasados') return ! c.arquivado && c.dias < 0;
    return ! c.arquivado;
}));
const contagem = (chave) => props.compromissos.filter((c) => (
    chave === 'arquivados' ? c.arquivado : chave === 'atrasados' ? ! c.arquivado && c.dias < 0 : ! c.arquivado
)).length;

// Quantos dias antes avisar, pelo que costuma fazer sentido: mensalidade
// com 3, o resto (domínio, hospedagem anual) com 15. Só sugere ao trocar.
const avisoSugerido = (periodicidade) => (periodicidade === 'mensal' ? 3 : periodicidade === 'unica' ? 2 : 15);

const vazio = () => ({
    tipo: 'hospedagem', sentido: 'pagar', nome: '', contraparte: '', valor: '',
    periodicidade: 'anual', proximo_vencimento: '', avisar_dias_antes: 15, observacao: '',
});
const form = useForm(vazio());
const editando = ref(null);
const caixa = ref(null);
const formAberto = ref(false);

const ehLembrete = computed(() => form.tipo === 'lembrete');

function aoTrocarPeriodicidade() {
    form.avisar_dias_antes = avisoSugerido(form.periodicidade);
}

async function novo() {
    cancelar();
    formAberto.value = true;
    await nextTick();
    caixa.value?.querySelector('select')?.focus();
}

async function editar(c) {
    editando.value = c.id;
    formAberto.value = true;
    form.clearErrors();
    Object.assign(form, {
        tipo: c.tipo, sentido: c.sentido, nome: c.nome, contraparte: c.contraparte ?? '', valor: c.valor,
        periodicidade: c.periodicidade, proximo_vencimento: c.proximoVencimento,
        avisar_dias_antes: c.avisarDiasAntes, observacao: c.observacao ?? '',
    });
    await nextTick();
    caixa.value?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function cancelar() {
    editando.value = null;
    formAberto.value = false;
    form.clearErrors();
    Object.assign(form, vazio());
}

function salvar() {
    if (ehLembrete.value) form.valor = '';
    const opcoes = { preserveScroll: true, onSuccess: cancelar };
    editando.value
        ? form.put(route('painel.financeiro.update', editando.value), opcoes)
        : form.post(route('painel.financeiro.store'), opcoes);
}

// Registrar pagamento: um formulário por vez, aberto dentro do cartão.
const pagando = ref(null);
const pagamento = useForm({ valor: '', pago_em: '', forma: 'pix', observacao: '' });

function abrirPagamento(c) {
    pagando.value = c.id;
    pagamento.clearErrors();
    Object.assign(pagamento, { valor: c.valor, pago_em: props.hoje, forma: c.tipo === 'lembrete' ? '' : 'pix', observacao: '' });
}

function registrarPagamento(c) {
    pagamento.post(route('painel.financeiro.pagar', c.id), {
        preserveScroll: true,
        onSuccess: () => { pagando.value = null; },
    });
}

// PIX da mensalidade (pacote urbanodev/financeiro): QR + copia e cola para
// mandar ao cliente. Estático: não avisa que caiu; confere-se no extrato.
const pixAberto = ref({});
const alternarPix = (c) => { pixAberto.value[c.id] = ! pixAberto.value[c.id]; };
const copiado = ref(null);
async function copiarPix(c) {
    await navigator.clipboard.writeText(c.pix.copiaECola);
    copiado.value = c.id;
    setTimeout(() => { if (copiado.value === c.id) copiado.value = null; }, 3000);
}

const historico = ref({});
const alternarHistorico = (c) => { historico.value[c.id] = ! historico.value[c.id]; };

const arquivar = (c) => router.post(route('painel.financeiro.arquivar', c.id), {}, { preserveScroll: true });
const reativar = (c) => router.post(route('painel.financeiro.reativar', c.id), {}, { preserveScroll: true });
const estornar = (c, p) => router.post(route('painel.financeiro.estornar', [c.id, p.id]), {}, { preserveScroll: true });

// Cor nunca sozinha (WCAG 1.4.1): a etiqueta sempre leva ícone e texto.
const estiloDaSituacao = {
    atrasado: { token: 'danger', icone: 'clock' },
    hoje: { token: 'warning', icone: 'calendar-clock' },
    perto: { token: 'warning', icone: 'calendar-clock' },
    em_dia: { token: 'success', icone: 'circle-check' },
    arquivado: { token: 'fg-subtle', icone: 'archive' },
};

const ultimoValido = (c) => c.pagamentos.find((p) => ! p.estornado);

const campoClasse = 'w-full rounded-md border-line-input bg-surface text-sm text-fg focus:border-line-strong focus:ring-2 focus:ring-accent-ring';
</script>

<template>
    <Head title="Financeiro" />

    <AuthenticatedLayout>
        <template #cabecalho><p class="font-label text-label uppercase text-fg-subtle">Casa</p></template>

        <PageHeader
            titulo="Financeiro"
            descricao="O que vence (hospedagem, domínio, mensalidade de cliente, lembrete), quanto e quando. Entrou na janela de aviso, sai uma mensagem no Telegram às 08:00; atrasado, avisa de novo a cada 7 dias até registrar o pagamento."
        />

        <dl class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-card border border-line bg-surface p-4">
                <dt class="text-sm text-fg-muted">A pagar por mês</dt>
                <dd class="mt-1 text-2xl font-bold tabular-nums text-fg">{{ totais.pagarPorMes }}</dd>
            </div>
            <div class="rounded-card border border-line bg-surface p-4">
                <dt class="text-sm text-fg-muted">A receber por mês</dt>
                <dd class="mt-1 text-2xl font-bold tabular-nums text-fg">{{ totais.receberPorMes }}</dd>
            </div>
            <div class="rounded-card border border-line bg-surface p-4">
                <dt class="text-sm text-fg-muted">Próximos 30 dias</dt>
                <dd class="mt-1 text-sm tabular-nums text-fg">Pagar <strong>{{ totais.pagar30 }}</strong><br>Receber <strong>{{ totais.receber30 }}</strong></dd>
            </div>
            <div class="rounded-card border p-4" :class="totais.atrasados ? 'border-danger bg-danger-subtle' : 'border-line bg-surface'">
                <dt class="text-sm text-fg-muted">Atrasados</dt>
                <dd class="mt-1 text-2xl font-bold tabular-nums" :class="totais.atrasados ? 'text-danger' : 'text-fg'">{{ totais.atrasados }}</dd>
            </div>
        </dl>

        <div class="mb-6">
            <PrimaryButton v-if="! formAberto" type="button" @click="novo">
                <Plus class="h-4 w-4" aria-hidden="true" />Novo compromisso
            </PrimaryButton>
        </div>

        <form v-if="formAberto" ref="caixa" class="mb-8 max-w-3xl scroll-mt-24 rounded-lg border border-line bg-surface p-5" @submit.prevent="salvar">
            <h2 class="mb-4 font-display text-xl font-bold uppercase text-fg">{{ editando ? 'Editar compromisso' : 'Novo compromisso' }}</h2>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <InputLabel for="f-tipo" value="Tipo" obrigatorio />
                    <select id="f-tipo" v-model="form.tipo" :class="campoClasse">
                        <option v-for="o in opcoes.tipos" :key="o.valor" :value="o.valor">{{ o.rotulo }}</option>
                    </select>
                    <InputError :message="form.errors.tipo" />
                </div>
                <div>
                    <InputLabel for="f-sentido" value="Quem paga" obrigatorio />
                    <select id="f-sentido" v-model="form.sentido" :class="campoClasse">
                        <option v-for="o in opcoes.sentidos" :key="o.valor" :value="o.valor">{{ o.valor === 'pagar' ? 'Nós pagamos (a pagar)' : 'O cliente nos paga (a receber)' }}</option>
                    </select>
                    <InputError :message="form.errors.sentido" />
                </div>

                <div class="sm:col-span-2">
                    <InputLabel for="f-nome" value="Nome" obrigatorio />
                    <TextInput id="f-nome" v-model="form.nome" maxlength="120" placeholder="Ex.: Hospedagem urbanodev.com.br" :aria-invalid="!! form.errors.nome" />
                    <InputError :message="form.errors.nome" />
                </div>

                <div>
                    <InputLabel for="f-contraparte" value="Com quem (opcional)" />
                    <TextInput id="f-contraparte" v-model="form.contraparte" maxlength="120" placeholder="MCO2, Imóveis Jacob…" />
                    <InputError :message="form.errors.contraparte" />
                </div>
                <div v-if="! ehLembrete">
                    <InputLabel for="f-valor" value="Valor (opcional)" />
                    <TextInput id="f-valor" v-model="form.valor" inputmode="decimal" placeholder="1.234,56" :aria-invalid="!! form.errors.valor" />
                    <InputError :message="form.errors.valor" />
                </div>

                <div>
                    <InputLabel for="f-periodicidade" value="Repete" obrigatorio />
                    <select id="f-periodicidade" v-model="form.periodicidade" :class="campoClasse" @change="aoTrocarPeriodicidade">
                        <option v-for="o in opcoes.periodicidades" :key="o.valor" :value="o.valor">{{ o.rotulo }}</option>
                    </select>
                    <InputError :message="form.errors.periodicidade" />
                </div>
                <div>
                    <InputLabel for="f-vencimento" :value="ehLembrete ? 'Data' : 'Próximo vencimento'" obrigatorio />
                    <TextInput id="f-vencimento" v-model="form.proximo_vencimento" type="date" :aria-invalid="!! form.errors.proximo_vencimento" />
                    <InputError :message="form.errors.proximo_vencimento" />
                </div>

                <div>
                    <InputLabel for="f-aviso" value="Avisar quantos dias antes" obrigatorio />
                    <TextInput id="f-aviso" v-model="form.avisar_dias_antes" type="number" min="0" max="90" :aria-invalid="!! form.errors.avisar_dias_antes" />
                    <p class="mt-1 text-sm text-fg-subtle">0 = só no dia.</p>
                    <InputError :message="form.errors.avisar_dias_antes" />
                </div>

                <div class="sm:col-span-2">
                    <InputLabel for="f-observacao" value="Observação (opcional)" />
                    <textarea id="f-observacao" v-model="form.observacao" rows="2" maxlength="2000" :class="campoClasse" placeholder="Onde se paga, número do contrato, login do painel (a senha não: ela fica no cofre)." />
                    <InputError :message="form.errors.observacao" />
                </div>
            </div>

            <div class="mt-5 flex flex-wrap gap-3">
                <PrimaryButton :disabled="form.processing">{{ editando ? 'Salvar' : 'Adicionar' }}</PrimaryButton>
                <SecondaryButton type="button" @click="cancelar">Cancelar</SecondaryButton>
            </div>
        </form>

        <div class="mb-4 flex flex-wrap gap-2" role="group" aria-label="Filtrar compromissos">
            <button
                v-for="f in filtros"
                :key="f.chave"
                type="button"
                class="min-h-[44px] rounded-full border px-4 text-sm"
                :class="filtro === f.chave ? 'border-accent bg-accent-subtle text-accent' : 'border-line text-fg-muted hover:text-fg'"
                :aria-pressed="filtro === f.chave ? 'true' : 'false'"
                @click="filtro = f.chave"
            >
                {{ f.rotulo }} <span class="tabular-nums">{{ contagem(f.chave) }}</span>
            </button>
        </div>

        <ul v-if="visiveis.length" class="max-w-4xl space-y-3" aria-label="Compromissos, pelo próximo vencimento">
            <li v-for="c in visiveis" :key="c.id" class="rounded-lg border bg-surface p-5" :class="editando === c.id ? 'border-line-strong' : 'border-line'">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm text-fg-subtle">{{ c.tipoRotulo }} · {{ c.sentidoRotulo }}<template v-if="c.contraparte"> · {{ c.contraparte }}</template></p>
                        <h2 class="font-display text-lg font-bold text-fg">{{ c.nome }}</h2>
                        <p class="mt-1 text-sm text-fg-muted">
                            <template v-if="c.valorFormatado"><strong class="tabular-nums text-fg">{{ c.valorFormatado }}</strong> · </template>
                            {{ c.periodicidadeRotulo }} ·
                            {{ c.arquivado ? 'último vencimento' : c.tipo === 'lembrete' ? 'data' : 'vence' }} <span class="tabular-nums">{{ c.proximoVencimentoFormatado }}</span>
                        </p>
                        <p v-if="c.observacao" class="mt-1 whitespace-pre-line text-sm text-fg-subtle">{{ c.observacao }}</p>
                    </div>
                    <Chip :rotulo="c.situacao.texto" :token="estiloDaSituacao[c.situacao.chave].token" :icone="estiloDaSituacao[c.situacao.chave].icone" />
                </div>

                <form v-if="pagando === c.id" class="mt-4 grid gap-3 rounded-md border border-line bg-surface-sunken p-4 sm:grid-cols-2" @submit.prevent="registrarPagamento(c)">
                    <p class="text-sm text-fg sm:col-span-2">
                        {{ c.tipo === 'lembrete' ? 'Marcar como feito' : 'Registrar o pagamento' }} de <strong class="tabular-nums">{{ c.proximoVencimentoFormatado }}</strong>.
                        <template v-if="c.periodicidade !== 'unica'">O próximo vencimento avança sozinho.</template>
                        <template v-else>Como é uma vez só, ele vai para os arquivados.</template>
                    </p>
                    <div>
                        <InputLabel :for="`p-data-${c.id}`" :value="c.tipo === 'lembrete' ? 'Feito em' : 'Pago em'" obrigatorio />
                        <TextInput :id="`p-data-${c.id}`" v-model="pagamento.pago_em" type="date" :max="hoje" />
                        <InputError :message="pagamento.errors.pago_em" />
                    </div>
                    <template v-if="c.tipo !== 'lembrete'">
                        <div>
                            <InputLabel :for="`p-valor-${c.id}`" value="Valor pago" />
                            <TextInput :id="`p-valor-${c.id}`" v-model="pagamento.valor" inputmode="decimal" placeholder="1.234,56" />
                            <InputError :message="pagamento.errors.valor" />
                        </div>
                        <div>
                            <InputLabel :for="`p-forma-${c.id}`" value="Forma" />
                            <select :id="`p-forma-${c.id}`" v-model="pagamento.forma" :class="campoClasse">
                                <option value="">—</option>
                                <option v-for="o in opcoes.formas" :key="o.valor" :value="o.valor">{{ o.rotulo }}</option>
                            </select>
                        </div>
                    </template>
                    <div :class="c.tipo === 'lembrete' ? '' : 'sm:col-span-1'">
                        <InputLabel :for="`p-obs-${c.id}`" value="Observação" />
                        <TextInput :id="`p-obs-${c.id}`" v-model="pagamento.observacao" maxlength="300" />
                    </div>
                    <div class="flex flex-wrap gap-3 sm:col-span-2">
                        <PrimaryButton :disabled="pagamento.processing">Confirmar</PrimaryButton>
                        <SecondaryButton type="button" @click="pagando = null">Cancelar</SecondaryButton>
                    </div>
                </form>

                <div class="mt-4 flex flex-wrap gap-2">
                    <PrimaryButton v-if="! c.arquivado && pagando !== c.id" type="button" @click="abrirPagamento(c)">
                        {{ c.tipo === 'lembrete' ? 'Marcar como feito' : 'Registrar pagamento' }}
                    </PrimaryButton>
                    <SecondaryButton v-if="! c.arquivado" type="button" @click="editar(c)">Editar</SecondaryButton>
                    <SecondaryButton
                        v-if="c.pagamentos.length"
                        type="button"
                        :aria-expanded="historico[c.id] ? 'true' : 'false'"
                        :aria-controls="`historico-${c.id}`"
                        @click="alternarHistorico(c)"
                    >
                        <History class="h-4 w-4" aria-hidden="true" />Histórico ({{ c.pagamentos.length }})
                    </SecondaryButton>
                    <SecondaryButton
                        v-if="c.pix"
                        type="button"
                        :aria-expanded="pixAberto[c.id] ? 'true' : 'false'"
                        :aria-controls="`pix-${c.id}`"
                        @click="alternarPix(c)"
                    >
                        <QrCode class="h-4 w-4" aria-hidden="true" />Cobrar por PIX
                    </SecondaryButton>
                    <TwoStepConfirm v-if="! c.arquivado" :nome="c.nome" rotulo="Arquivar" verbo="Arquivar" @confirmar="arquivar(c)" />
                    <SecondaryButton v-else type="button" @click="reativar(c)">Reativar</SecondaryButton>
                </div>

                <div v-if="c.pix && pixAberto[c.id]" :id="`pix-${c.id}`" class="mt-4 flex flex-wrap items-start gap-5 rounded-md border border-line bg-surface-sunken p-4">
                    <!-- SVG gerado no servidor pelo pacote (sem texto de usuário dentro). -->
                    <div class="shrink-0 rounded bg-white p-1" v-html="c.pix.svg" />
                    <div class="min-w-0 flex-1 space-y-3 text-sm">
                        <p class="text-fg">
                            PIX de <strong class="tabular-nums">{{ c.valorFormatado }}</strong> referente a
                            <strong class="tabular-nums">{{ c.proximoVencimentoFormatado }}</strong>. Mande o QR ou o copia e cola ao cliente.
                        </p>
                        <div>
                            <InputLabel :for="`pix-cc-${c.id}`" value="Copia e cola" />
                            <textarea :id="`pix-cc-${c.id}`" readonly rows="3" :value="c.pix.copiaECola" :class="[campoClasse, 'font-mono text-xs']" @focus="$event.target.select()" />
                        </div>
                        <SecondaryButton type="button" @click="copiarPix(c)">
                            <Copy class="h-4 w-4" aria-hidden="true" />{{ copiado === c.id ? 'Copiado' : 'Copiar' }}
                        </SecondaryButton>
                        <p class="text-fg-subtle" aria-live="polite">
                            O PIX cai direto na conta e <strong>não avisa o painel</strong>. Achou no extrato (identificador
                            <span class="font-mono">{{ c.pix.txid }}</span>)? Use “Registrar pagamento”.
                        </p>
                    </div>
                </div>

                <div v-if="historico[c.id]" :id="`historico-${c.id}`" class="relative mt-4 overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <caption class="sr-only">Pagamentos de {{ c.nome }}</caption>
                        <thead class="text-fg-subtle">
                            <tr>
                                <th scope="col" class="py-1 pr-4 font-medium">Referente a</th>
                                <th scope="col" class="py-1 pr-4 font-medium">Pago em</th>
                                <th scope="col" class="py-1 pr-4 font-medium">Valor</th>
                                <th scope="col" class="py-1 pr-4 font-medium">Forma</th>
                                <th scope="col" class="py-1 pr-4 font-medium">Observação</th>
                                <th scope="col" class="py-1 font-medium"><span class="sr-only">Ação</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            <tr v-for="p in c.pagamentos" :key="p.id" :class="p.estornado ? 'text-fg-subtle line-through' : 'text-fg'">
                                <td class="py-2 pr-4 tabular-nums">{{ p.referenteA }}</td>
                                <td class="py-2 pr-4 tabular-nums">{{ p.pagoEm }}</td>
                                <td class="py-2 pr-4 tabular-nums">{{ p.valor ?? '—' }}</td>
                                <td class="py-2 pr-4">{{ p.forma ?? '—' }}</td>
                                <td class="py-2 pr-4">{{ p.observacao ?? '' }}<span v-if="p.estornado" class="no-underline"> (estornado)</span></td>
                                <td class="py-2 text-right">
                                    <TwoStepConfirm
                                        v-if="! p.estornado && ultimoValido(c)?.id === p.id"
                                        :nome="`pagamento de ${p.referenteA}`"
                                        rotulo="Estornar"
                                        verbo="Estornar"
                                        @confirmar="estornar(c, p)"
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </li>
        </ul>

        <EstadoVazio
            v-else
            icone="inbox"
            :titulo="filtro === 'arquivados' ? 'Nada arquivado' : filtro === 'atrasados' ? 'Nada atrasado' : 'Nenhum compromisso'"
            :descricao="filtro === 'abertos' ? 'Cadastre a hospedagem, o domínio, a mensalidade de um cliente ou um lembrete no botão acima.' : 'Aparece aqui quando houver.'"
        />
    </AuthenticatedLayout>
</template>
