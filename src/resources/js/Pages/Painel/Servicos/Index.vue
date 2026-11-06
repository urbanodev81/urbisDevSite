<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, nextTick, ref } from 'vue';
import { ArrowDown, ArrowUp, EyeOff, Plus } from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import EstadoVazio from '@/Components/EstadoVazio.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TwoStepConfirm from '@/Components/TwoStepConfirm.vue';

const props = defineProps({
    servicos: { type: Array, default: () => [] },
    icones: { type: Array, required: true },
    cores: { type: Array, required: true },
    situacoes: { type: Array, required: true },
});

/*
 * Site › Serviços (bloco 4b, 06/11/2026). Um formulário só, no topo, para
 * adicionar e para editar. Ícone e cor são listas fechadas que vêm do servidor
 * (App\Support\ServicosDoSite): o desenho do ícone é constante de lá, não
 * texto digitado, por isso pode entrar por v-html.
 */
const vazio = () => ({
    titulo: '', frente: '', texto: '', icone: 'painel', cor: 'ambar', situacao: 'no_ar',
    link_url: '', link_rotulo: '', nome_no_catalogo: '', ativo: true,
});

const form = useForm(vazio());
const editando = ref(null);
const caixa = ref(null);

const rotulo = (lista, valor) => lista.find((o) => o.valor === valor)?.rotulo ?? valor;
const svg = (valor) => props.icones.find((o) => o.valor === valor)?.svg ?? '';
const titulo = computed(() => (editando.value ? 'Editar serviço' : 'Novo serviço'));

async function editar(item) {
    editando.value = item.id;
    form.clearErrors();
    Object.assign(form, {
        ...vazio(),
        ...Object.fromEntries(Object.entries(item).map(([k, v]) => [k, v ?? ''])),
        ativo: item.ativo,
    });
    await nextTick();
    caixa.value?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    caixa.value?.querySelector('input')?.focus();
}

function cancelar() {
    editando.value = null;
    form.clearErrors();
    Object.assign(form, vazio());
}

function salvar() {
    const opcoes = { preserveScroll: true, onSuccess: cancelar };
    editando.value
        ? form.put(route('painel.servicos.update', editando.value), opcoes)
        : form.post(route('painel.servicos.store'), opcoes);
}

const mover = (item, direcao) => router.post(route('painel.servicos.mover', item.id), { direcao }, { preserveScroll: true });
const alternar = (item) => router.put(route('painel.servicos.update', item.id), { ...item, ativo: ! item.ativo }, { preserveScroll: true });
const remover = (item) => router.delete(route('painel.servicos.destroy', item.id), { preserveScroll: true });

const campoClasse = 'w-full rounded-md border-line-input bg-surface text-sm text-fg focus:border-line-strong focus:ring-2 focus:ring-accent-ring';
</script>

<template>
    <Head title="Serviços" />

    <AuthenticatedLayout>
        <template #cabecalho><p class="font-label text-label uppercase text-fg-subtle">Site</p></template>

        <PageHeader
            titulo="Serviços"
            descricao="As frentes de “O que fazemos” na home. A mesma lista vai para o Google (catálogo de serviços) e para as IAs (llms.txt). Salvou, já está no ar."
        />

        <form ref="caixa" class="mb-8 max-w-3xl scroll-mt-24 rounded-lg border border-line bg-surface p-5" @submit.prevent="salvar">
            <h2 class="mb-4 font-display text-xl font-bold uppercase text-fg">{{ titulo }}</h2>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <InputLabel for="s-titulo" value="Título do cartão" obrigatorio />
                    <TextInput id="s-titulo" v-model="form.titulo" class="w-full" maxlength="80" :aria-invalid="!! form.errors.titulo" />
                    <InputError :message="form.errors.titulo" />
                </div>
                <div>
                    <InputLabel for="s-frente" value="Frente (etiqueta pequena)" obrigatorio />
                    <TextInput id="s-frente" v-model="form.frente" class="w-full" maxlength="40" :aria-invalid="!! form.errors.frente" />
                    <p class="mt-1 text-sm text-fg-subtle">Aparece como “Frente · <b>{{ form.frente || '…' }}</b>”.</p>
                    <InputError :message="form.errors.frente" />
                </div>
            </div>

            <div class="mt-4">
                <InputLabel for="s-texto" value="Texto" obrigatorio />
                <textarea id="s-texto" v-model="form.texto" rows="3" maxlength="400" :class="campoClasse" :aria-invalid="!! form.errors.texto" />
                <p class="mt-1 flex justify-between gap-3 text-sm text-fg-subtle">
                    <span>Sem nome de cliente nem de produto ainda não lançado.</span>
                    <span class="shrink-0 tabular-nums" aria-hidden="true">{{ form.texto.length }}/400</span>
                </p>
                <InputError :message="form.errors.texto" />
            </div>

            <div class="mt-4 grid gap-4 sm:grid-cols-3">
                <div>
                    <InputLabel for="s-icone" value="Ícone" />
                    <div class="flex items-center gap-2">
                        <!-- eslint-disable-next-line vue/no-v-html -->
                        <svg class="h-6 w-6 shrink-0 text-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" v-html="svg(form.icone)" />
                        <select id="s-icone" v-model="form.icone" :class="campoClasse">
                            <option v-for="o in icones" :key="o.valor" :value="o.valor">{{ o.rotulo }}</option>
                        </select>
                    </div>
                    <InputError :message="form.errors.icone" />
                </div>
                <div>
                    <InputLabel for="s-cor" value="Cor" />
                    <select id="s-cor" v-model="form.cor" :class="campoClasse">
                        <option v-for="o in cores" :key="o.valor" :value="o.valor">{{ o.rotulo }}</option>
                    </select>
                    <InputError :message="form.errors.cor" />
                </div>
                <div>
                    <InputLabel for="s-situacao" value="Selo" />
                    <select id="s-situacao" v-model="form.situacao" :class="campoClasse">
                        <option v-for="o in situacoes" :key="o.valor" :value="o.valor">{{ o.rotulo }}</option>
                    </select>
                    <InputError :message="form.errors.situacao" />
                </div>
            </div>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <InputLabel for="s-link" value="Link (opcional)" />
                    <TextInput id="s-link" v-model="form.link_url" type="url" class="w-full" placeholder="https://" maxlength="255" :aria-invalid="!! form.errors.link_url" />
                    <InputError :message="form.errors.link_url" />
                </div>
                <div>
                    <InputLabel for="s-link-rotulo" value="Texto do link" />
                    <TextInput id="s-link-rotulo" v-model="form.link_rotulo" class="w-full" maxlength="40" :disabled="! form.link_url" :aria-invalid="!! form.errors.link_rotulo" />
                    <p class="mt-1 text-sm text-fg-subtle">Ex.: Conhecer o USI. A seta entra sozinha.</p>
                    <InputError :message="form.errors.link_rotulo" />
                </div>
            </div>

            <div class="mt-4">
                <InputLabel for="s-catalogo" value="Nome para o Google e as IAs (opcional)" />
                <TextInput id="s-catalogo" v-model="form.nome_no_catalogo" class="w-full" maxlength="120" :aria-invalid="!! form.errors.nome_no_catalogo" />
                <p class="mt-1 text-sm text-fg-subtle">O nome completo do serviço, que o cartão não precisa repetir. Vazio, vale o título.</p>
                <InputError :message="form.errors.nome_no_catalogo" />
            </div>

            <div class="mt-5 flex flex-wrap items-center gap-4">
                <PrimaryButton :disabled="form.processing">
                    <Plus v-if="! editando" class="h-4 w-4" aria-hidden="true" />
                    {{ editando ? 'Salvar serviço' : 'Adicionar' }}
                </PrimaryButton>
                <SecondaryButton v-if="editando" type="button" @click="cancelar">Cancelar</SecondaryButton>
                <label class="flex min-h-[44px] items-center gap-2 text-sm text-fg"><Checkbox v-model:checked="form.ativo" />Aparece no site</label>
            </div>
        </form>

        <ol v-if="servicos.length" class="max-w-3xl space-y-3" aria-label="Serviços, na ordem da home">
            <li
                v-for="(item, i) in servicos"
                :key="item.id"
                class="rounded-lg border bg-surface p-5"
                :class="editando === item.id ? 'border-line-strong' : 'border-line'"
            >
                <div class="flex items-start gap-3">
                    <!-- eslint-disable-next-line vue/no-v-html -->
                    <svg class="mt-1 h-6 w-6 shrink-0 text-fg-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" v-html="svg(item.icone)" />
                    <div class="min-w-0 flex-1">
                        <p class="font-label text-label uppercase text-fg-subtle">
                            Frente · {{ item.frente }} · {{ rotulo(situacoes, item.situacao) }} · {{ rotulo(cores, item.cor) }}
                        </p>
                        <h2 class="mt-1 font-display text-lg font-bold text-fg">{{ item.titulo }}</h2>
                        <p class="mt-1 text-sm text-fg-muted">{{ item.texto }}</p>
                        <p v-if="item.link_url" class="mt-1 break-all text-sm text-fg-subtle">{{ item.link_rotulo }} → {{ item.link_url }}</p>
                    </div>
                    <span v-if="! item.ativo" class="inline-flex shrink-0 items-center gap-1 text-sm text-fg-subtle">
                        <EyeOff class="h-4 w-4" aria-hidden="true" />Oculto
                    </span>
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    <SecondaryButton type="button" :disabled="i === 0" :aria-label="`Subir ${item.titulo}`" @click="mover(item, 'subir')">
                        <ArrowUp class="h-4 w-4" aria-hidden="true" />Subir
                    </SecondaryButton>
                    <SecondaryButton type="button" :disabled="i === servicos.length - 1" :aria-label="`Descer ${item.titulo}`" @click="mover(item, 'descer')">
                        <ArrowDown class="h-4 w-4" aria-hidden="true" />Descer
                    </SecondaryButton>
                    <SecondaryButton type="button" @click="editar(item)">Editar</SecondaryButton>
                    <SecondaryButton type="button" @click="alternar(item)">{{ item.ativo ? 'Ocultar do site' : 'Mostrar no site' }}</SecondaryButton>
                    <TwoStepConfirm :nome="item.titulo" @confirmar="remover(item)" />
                </div>
            </li>
        </ol>

        <EstadoVazio
            v-else
            icone="inbox"
            titulo="Nenhum serviço"
            descricao="Sem serviço, a seção “O que fazemos” some da home. Adicione um no formulário acima."
        />
    </AuthenticatedLayout>
</template>
