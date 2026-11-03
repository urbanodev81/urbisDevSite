<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import EstadoVazio from '@/Components/EstadoVazio.vue';
import Chip from '@/Components/Chip.vue';
import SelectComBusca from '@/Components/SelectComBusca.vue';

const props = defineProps({
    linhas: { type: Object, required: true },
    filtros: { type: Object, default: () => ({}) },
    tipos: { type: Array, default: () => [] },
    usuarios: { type: Array, default: () => [] },
});

function filtrar(campo, valor) {
    router.get(route('painel.auditoria.index'), { ...props.filtros, [campo]: valor }, { preserveState: true, replace: true });
}

const tokenDoEvento = { criado: 'success', alterado: 'info', removido: 'danger', restaurado: 'warning' };
</script>

<template>
    <Head title="Trilha de alterações" />

    <AuthenticatedLayout>
        <template #cabecalho><p class="font-label text-label uppercase text-fg-subtle">Administração</p></template>

        <PageHeader
            titulo="Trilha de alterações"
            descricao="Só de leitura, e é a definição da coisa: trilha que a aplicação sabe editar não serve de trilha."
        />

        <div class="mb-5 flex flex-wrap gap-3">
            <select class="min-h-[44px] rounded-md border-line-input bg-surface text-sm text-fg focus:border-line-strong focus:ring-2 focus:ring-accent-ring" :value="filtros.tipo ?? ''" aria-label="Filtrar por tipo de registro" @change="filtrar('tipo', $event.target.value)">
                <option value="">Qualquer registro</option>
                <option v-for="t in tipos" :key="t.valor" :value="t.valor">{{ t.rotulo }}</option>
            </select>
            <SelectComBusca class="min-w-[16rem]" :model-value="filtros.usuario ?? ''" :opcoes="usuarios" rotulo="Filtrar por quem alterou" opcao-vazia="Qualquer pessoa" @update:model-value="filtrar('usuario', $event)" />
        </div>

        <div v-if="linhas.data.length" class="overflow-x-auto rounded-lg border border-line bg-surface">
            <table class="w-full min-w-[46rem] text-sm">
                <thead class="border-b border-line">
                    <tr class="text-left font-label text-label uppercase text-fg-subtle">
                        <th class="px-4 py-3">Quando</th>
                        <th class="px-4 py-3">O quê</th>
                        <th class="px-4 py-3">Campo</th>
                        <th class="px-4 py-3">De → para</th>
                        <th class="px-4 py-3">Quem</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    <tr v-for="linha in linhas.data" :key="linha.id">
                        <td class="whitespace-nowrap px-4 py-3 text-fg-muted [font-variant-numeric:tabular-nums]">{{ linha.quando }}</td>
                        <td class="px-4 py-3">
                            <Chip :rotulo="linha.evento" :token="tokenDoEvento[linha.evento] ?? 'fg-subtle'" />
                            <span class="ml-2 text-fg">{{ linha.tipo }} #{{ linha.registro }}</span>
                        </td>
                        <td class="px-4 py-3 text-fg-muted">{{ linha.campo ?? '—' }}</td>
                        <td class="px-4 py-3 text-fg-muted">
                            <template v-if="linha.campo">
                                <span class="line-through opacity-70">{{ linha.de ?? 'vazio' }}</span>
                                <span class="mx-1">→</span>
                                <span class="text-fg">{{ linha.para ?? 'vazio' }}</span>
                            </template>
                            <template v-else>—</template>
                        </td>
                        <td class="px-4 py-3 text-fg-muted">{{ linha.autor }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <EstadoVazio v-else icone="scroll-text" titulo="Nenhuma alteração registrada" descricao="A trilha começa a encher assim que alguém mexer em show, integrante ou pedido." />

        <nav v-if="linhas.links.length > 3" class="mt-6 flex flex-wrap gap-1" aria-label="Paginação">
            <component
                :is="link.url ? Link : 'span'"
                v-for="link in linhas.links"
                :key="link.label"
                :href="link.url"
                class="min-h-[44px] rounded-md px-3 py-2 text-sm"
                :class="link.active ? 'bg-accent text-fg-on-accent' : link.url ? 'text-fg-muted hover:bg-surface-sunken' : 'text-fg-subtle opacity-50'"
                v-html="link.label"
            />
        </nav>
    </AuthenticatedLayout>
</template>
