<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, nextTick, ref } from 'vue';
import { ArrowDown, ArrowUp, EyeOff, Plus } from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import EstadoVazio from '@/Components/EstadoVazio.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import Checkbox from '@/Components/Checkbox.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TwoStepConfirm from '@/Components/TwoStepConfirm.vue';

defineProps({
    perguntas: { type: Array, default: () => [] },
});

/*
 * Site › Perguntas (bloco 4d, 08/11/2026). Mesmo desenho de Serviços: um
 * formulário só, no topo, para adicionar e para editar; a lista embaixo, na
 * ordem da página.
 */
const vazio = () => ({ pergunta: '', explicacao: '', ativo: true });

const form = useForm(vazio());
const editando = ref(null);
const caixa = ref(null);

const titulo = computed(() => (editando.value ? 'Editar pergunta' : 'Nova pergunta'));
const numero = (i) => String(i + 1).padStart(2, '0');

async function editar(item) {
    editando.value = item.id;
    form.clearErrors();
    Object.assign(form, { pergunta: item.pergunta, explicacao: item.explicacao ?? '', ativo: item.ativo });
    await nextTick();
    caixa.value?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    caixa.value?.querySelector('textarea')?.focus();
}

function cancelar() {
    editando.value = null;
    form.clearErrors();
    Object.assign(form, vazio());
}

function salvar() {
    const opcoes = { preserveScroll: true, onSuccess: cancelar };
    editando.value
        ? form.put(route('painel.perguntas.update', editando.value), opcoes)
        : form.post(route('painel.perguntas.store'), opcoes);
}

const mover = (item, direcao) => router.post(route('painel.perguntas.mover', item.id), { direcao }, { preserveScroll: true });
const alternar = (item) => router.put(route('painel.perguntas.update', item.id), { ...item, ativo: ! item.ativo }, { preserveScroll: true });
const remover = (item) => router.delete(route('painel.perguntas.destroy', item.id), { preserveScroll: true });

const campoClasse = 'w-full rounded-md border-line-input bg-surface text-sm text-fg focus:border-line-strong focus:ring-2 focus:ring-accent-ring';
</script>

<template>
    <Head title="Perguntas" />

    <AuthenticatedLayout>
        <template #cabecalho><p class="font-label text-label uppercase text-fg-subtle">Site</p></template>

        <PageHeader
            titulo="Perguntas"
            descricao="As perguntas para fazer a qualquer fornecedor, na página Como trabalhamos. O título conta sozinho (“Sete perguntas…”). Salvou, já está no ar."
        />

        <form ref="caixa" class="mb-8 max-w-3xl scroll-mt-24 rounded-lg border border-line bg-surface p-5" @submit.prevent="salvar">
            <h2 class="mb-4 font-display text-xl font-bold uppercase text-fg">{{ titulo }}</h2>

            <div>
                <InputLabel for="p-pergunta" value="Pergunta" obrigatorio />
                <textarea id="p-pergunta" v-model="form.pergunta" rows="2" maxlength="200" :class="campoClasse" :aria-invalid="!! form.errors.pergunta" />
                <p class="mt-1 text-right text-sm tabular-nums text-fg-subtle" aria-hidden="true">{{ form.pergunta.length }}/200</p>
                <InputError :message="form.errors.pergunta" />
            </div>

            <div class="mt-4">
                <InputLabel for="p-explicacao" value="Por que perguntar (opcional)" />
                <textarea id="p-explicacao" v-model="form.explicacao" rows="2" maxlength="300" :class="campoClasse" :aria-invalid="!! form.errors.explicacao" />
                <p class="mt-1 flex justify-between gap-3 text-sm text-fg-subtle">
                    <span>A linha menor, embaixo da pergunta.</span>
                    <span class="shrink-0 tabular-nums" aria-hidden="true">{{ form.explicacao.length }}/300</span>
                </p>
                <InputError :message="form.errors.explicacao" />
            </div>

            <div class="mt-5 flex flex-wrap items-center gap-4">
                <PrimaryButton :disabled="form.processing">
                    <Plus v-if="! editando" class="h-4 w-4" aria-hidden="true" />
                    {{ editando ? 'Salvar pergunta' : 'Adicionar' }}
                </PrimaryButton>
                <SecondaryButton v-if="editando" type="button" @click="cancelar">Cancelar</SecondaryButton>
                <label class="flex min-h-[44px] items-center gap-2 text-sm text-fg"><Checkbox v-model:checked="form.ativo" />Aparece no site</label>
            </div>
        </form>

        <ol v-if="perguntas.length" class="max-w-3xl space-y-3" aria-label="Perguntas, na ordem da página">
            <li
                v-for="(item, i) in perguntas"
                :key="item.id"
                class="rounded-lg border bg-surface p-5"
                :class="editando === item.id ? 'border-line-strong' : 'border-line'"
            >
                <div class="flex items-start gap-3">
                    <span class="mt-0.5 shrink-0 font-mono text-sm tabular-nums text-fg-subtle" aria-hidden="true">{{ numero(i) }}</span>
                    <div class="min-w-0 flex-1">
                        <h2 class="font-display text-lg font-bold text-fg">{{ item.pergunta }}</h2>
                        <p v-if="item.explicacao" class="mt-1 text-sm text-fg-muted">{{ item.explicacao }}</p>
                    </div>
                    <span v-if="! item.ativo" class="inline-flex shrink-0 items-center gap-1 text-sm text-fg-subtle">
                        <EyeOff class="h-4 w-4" aria-hidden="true" />Oculta
                    </span>
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    <SecondaryButton type="button" :disabled="i === 0" :aria-label="`Subir: ${item.pergunta}`" @click="mover(item, 'subir')">
                        <ArrowUp class="h-4 w-4" aria-hidden="true" />Subir
                    </SecondaryButton>
                    <SecondaryButton type="button" :disabled="i === perguntas.length - 1" :aria-label="`Descer: ${item.pergunta}`" @click="mover(item, 'descer')">
                        <ArrowDown class="h-4 w-4" aria-hidden="true" />Descer
                    </SecondaryButton>
                    <SecondaryButton type="button" @click="editar(item)">Editar</SecondaryButton>
                    <SecondaryButton type="button" @click="alternar(item)">{{ item.ativo ? 'Ocultar do site' : 'Mostrar no site' }}</SecondaryButton>
                    <TwoStepConfirm :nome="item.pergunta" @confirmar="remover(item)" />
                </div>
            </li>
        </ol>

        <EstadoVazio
            v-else
            icone="inbox"
            titulo="Nenhuma pergunta"
            descricao="Sem pergunta, a seção some de Como trabalhamos. Adicione uma no formulário acima."
        />
    </AuthenticatedLayout>
</template>
