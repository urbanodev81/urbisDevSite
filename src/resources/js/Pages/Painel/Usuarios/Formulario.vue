<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DesligarSegundoFator from './Partials/DesligarSegundoFator.vue';

const props = defineProps({ usuario: { type: Object, default: null }, perfis: { type: Array, default: () => [] } });
const editando = computed(() => props.usuario !== null);

const form = useForm({
    name: props.usuario?.name ?? '',
    email: props.usuario?.email ?? '',
    telefone: props.usuario?.telefone ?? '',
    password: '',
    password_confirmation: '',
    perfis: props.usuario?.perfis ?? [],
    ativo: props.usuario?.ativo ?? true,
});
</script>

<template>
    <Head :title="editando ? 'Editar conta' : 'Nova conta'" />

    <AuthenticatedLayout>
        <template #cabecalho><p class="font-label text-label uppercase text-fg-subtle">Administração</p></template>

        <PageHeader :titulo="editando ? 'Editar conta' : 'Nova conta'" :voltar-para="route('painel.usuarios.index')" voltar-rotulo="Contas" />

        <form class="max-w-2xl space-y-6" @submit.prevent="editando ? form.put(route('painel.usuarios.update', usuario.uuid)) : form.post(route('painel.usuarios.store'))">
            <section class="rounded-lg border border-line bg-surface p-5">
                <h2 class="mb-4 font-display text-xl font-bold uppercase text-fg">Quem é</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2"><InputLabel value="Nome" obrigatorio /><TextInput v-model="form.name" /><InputError :message="form.errors.name" /></div>
                    <div><InputLabel value="E-mail" obrigatorio /><TextInput v-model="form.email" type="email" /><InputError :message="form.errors.email" /></div>
                    <div><InputLabel value="Telefone" /><TextInput v-model="form.telefone" /><InputError :message="form.errors.telefone" /></div>
                </div>
            </section>

            <section class="rounded-lg border border-line bg-surface p-5">
                <h2 class="mb-1 font-display text-xl font-bold uppercase text-fg">Perfil</h2>
                <p class="mb-4 text-sm text-fg-muted">
                    Pode ter mais de um — é assim que uma integrante enxerga valores sem que todo mundo enxergue.
                </p>

                <div class="space-y-3">
                    <label v-for="perfil in perfis" :key="perfil.valor" class="flex items-start gap-3 rounded-md border border-line p-3">
                        <Checkbox v-model:checked="form.perfis" :value="perfil.valor" />
                        <span>
                            <span class="block font-semibold text-fg">{{ perfil.rotulo }}</span>
                            <span v-if="perfil.descricao" class="block text-sm text-fg-muted">{{ perfil.descricao }}</span>
                        </span>
                    </label>
                </div>
                <InputError :message="form.errors.perfis" />
            </section>

            <section class="rounded-lg border border-line bg-surface p-5">
                <h2 class="mb-4 font-display text-xl font-bold uppercase text-fg">Senha</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel :value="editando ? 'Nova senha (deixe em branco para manter)' : 'Senha'" :obrigatorio="!editando" />
                        <TextInput v-model="form.password" type="password" autocomplete="new-password" />
                        <InputError :message="form.errors.password" />
                    </div>
                    <div>
                        <InputLabel value="Confirme a senha" />
                        <TextInput v-model="form.password_confirmation" type="password" autocomplete="new-password" />
                    </div>
                </div>

                <label class="mt-4 flex items-center gap-3">
                    <Checkbox v-model:checked="form.ativo" />
                    <span class="text-sm text-fg">Conta ativa</span>
                </label>
                <p v-if="editando && usuario?.uuid" class="mt-2 text-sm text-fg-subtle">
                    Ninguém consegue desativar a própria conta nem tirar o próprio perfil — sem essa trava, o último administrador se tranca do lado de fora.
                </p>
            </section>

            <div class="flex flex-wrap gap-2">
                <PrimaryButton :disabled="form.processing">{{ editando ? 'Salvar' : 'Criar conta' }}</PrimaryButton>
                <Link :href="route('painel.usuarios.index')"><SecondaryButton type="button">Cancelar</SecondaryButton></Link>
            </div>
        </form>

        <!-- Fora do formulário da conta: tem a própria senha e o próprio envio. -->
        <DesligarSegundoFator
            v-if="usuario?.doisFatoresAtivo && !usuario.euMesmo"
            :usuario="usuario"
            class="mt-6 max-w-2xl"
        />
    </AuthenticatedLayout>
</template>
