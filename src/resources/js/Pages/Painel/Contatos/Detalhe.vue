<script setup>
/*
 * Um contato (04/11/2026, bloco 2 da 246). Responder é fora daqui (e-mail ou
 * WhatsApp, pelos botões); aqui se anota em que pé está.
 */
import { Head, router, useForm } from '@inertiajs/vue3';
import { Download, Mail, MessageCircle, Phone } from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TwoStepConfirm from '@/Components/TwoStepConfirm.vue';
import Chip from '@/Components/Chip.vue';

const props = defineProps({
    contato: { type: Object, required: true },
    status: { type: Array, default: () => [] },
    podeGerenciar: { type: Boolean, default: false },
});

const form = useForm({
    status: props.contato.status.valor,
    nota: props.contato.nota ?? '',
});

const salvar = () => form.put(route('painel.contatos.update', props.contato.uuid), { preserveScroll: true });

const espera = (dias) => (dias === 0 ? 'Chegou hoje' : dias === 1 ? 'Chegou há 1 dia' : `Chegou há ${dias} dias`);

const campo = 'min-h-[44px] w-full rounded-md border-line-input bg-surface text-sm text-fg focus:border-line-strong focus:ring-2 focus:ring-accent-ring';
</script>

<template>
    <Head :title="contato.nome" />

    <AuthenticatedLayout>
        <template #cabecalho><p class="font-label text-label uppercase text-fg-subtle">Contatos</p></template>

        <PageHeader :titulo="contato.nome" :descricao="contato.origem" :voltar-para="route('painel.contatos.index')" voltar-rotulo="Contatos" />

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
            <section class="rounded-lg border border-line bg-surface p-5" aria-labelledby="titulo-recado">
                <div class="mb-4 flex flex-wrap items-center gap-2">
                    <Chip :rotulo="contato.status.rotulo" :token="contato.status.token" :icone="contato.status.icone" />
                    <Chip v-if="contato.arquivado" rotulo="Arquivado" token="fg-subtle" icone="archive" />
                    <span class="font-mono text-xs text-fg-subtle">{{ espera(contato.diasEsperando) }} · {{ contato.recebidoEm }}</span>
                </div>

                <h2 id="titulo-recado" class="font-display text-lg font-bold text-fg">Recado</h2>
                <p v-if="contato.mensagem" class="mt-2 whitespace-pre-line break-words text-fg">{{ contato.mensagem }}</p>
                <p v-else class="mt-2 text-sm text-fg-muted">Sem recado: deixou o contato para abrir a demonstração.</p>

                <dl class="mt-6 grid gap-3 text-sm sm:grid-cols-2">
                    <div><dt class="text-fg-subtle">E-mail</dt><dd class="break-all text-fg">{{ contato.email }}</dd></div>
                    <div><dt class="text-fg-subtle">Telefone</dt><dd class="text-fg">{{ contato.telefone }}</dd></div>
                    <div>
                        <dt class="text-fg-subtle">Consentimento</dt>
                        <dd class="text-fg">{{ contato.consentimento ? `Aceitou em ${contato.consentimento}` : 'Não registrado' }}</dd>
                    </div>
                    <div>
                        <dt class="text-fg-subtle">E-mail de aviso</dt>
                        <dd :class="contato.emailFalhou ? 'text-danger' : 'text-fg'">
                            {{ contato.emailEnviadoEm ? `Saiu em ${contato.emailEnviadoEm}` : contato.emailFalhou ? 'Não saiu: o envio falhou' : 'Sem registro' }}
                        </dd>
                    </div>
                </dl>

                <div class="mt-6 flex flex-wrap gap-2">
                    <a :href="`mailto:${contato.email}`"><SecondaryButton type="button"><Mail class="h-4 w-4" aria-hidden="true" />Responder por e-mail</SecondaryButton></a>
                    <a v-if="contato.whatsapp" :href="`https://wa.me/${contato.whatsapp}`" target="_blank" rel="noopener">
                        <SecondaryButton type="button"><MessageCircle class="h-4 w-4" aria-hidden="true" />WhatsApp</SecondaryButton>
                    </a>
                    <a :href="`tel:${contato.telefone}`"><SecondaryButton type="button"><Phone class="h-4 w-4" aria-hidden="true" />Ligar</SecondaryButton></a>
                </div>
            </section>

            <aside class="space-y-6">
                <form
                    v-if="podeGerenciar && !contato.arquivado"
                    class="rounded-lg border border-line bg-surface p-5"
                    aria-labelledby="titulo-situacao"
                    @submit.prevent="salvar"
                >
                    <h2 id="titulo-situacao" class="mb-4 font-display text-lg font-bold text-fg">Em que pé está</h2>

                    <InputLabel for="status" value="Situação" />
                    <select id="status" v-model="form.status" :class="campo">
                        <option v-for="s in status" :key="s.valor" :value="s.valor">{{ s.rotulo }}</option>
                    </select>
                    <InputError :message="form.errors.status" />

                    <InputLabel for="nota" value="Nota interna" class="mt-4" />
                    <textarea id="nota" v-model="form.nota" rows="5" maxlength="5000" :class="campo" placeholder="Quem respondeu, o que foi combinado…" />
                    <p class="mt-1 text-xs text-fg-subtle">Só a equipe vê. Fica na trilha de alterações.</p>
                    <InputError :message="form.errors.nota" />

                    <PrimaryButton class="mt-4" :disabled="form.processing">Salvar</PrimaryButton>
                </form>

                <section v-else-if="contato.nota" class="rounded-lg border border-line bg-surface p-5">
                    <h2 class="font-display text-lg font-bold text-fg">Nota interna</h2>
                    <p class="mt-2 whitespace-pre-line text-sm text-fg">{{ contato.nota }}</p>
                </section>

                <section v-if="podeGerenciar" class="rounded-lg border border-line bg-surface p-5" aria-labelledby="titulo-dados">
                    <h2 id="titulo-dados" class="font-display text-lg font-bold text-fg">Dados desta pessoa</h2>
                    <p class="mt-1 text-sm text-fg-muted">
                        Se ela pedir os dados que temos (LGPD), baixe e mande. Sai tudo o que existe com este e-mail.
                        Contato é apagado sozinho 24 meses depois de chegar.
                    </p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <a :href="route('painel.contatos.exportar', contato.uuid)">
                            <SecondaryButton type="button"><Download class="h-4 w-4" aria-hidden="true" />Baixar os dados</SecondaryButton>
                        </a>
                        <TwoStepConfirm
                            v-if="!contato.arquivado"
                            rotulo="Arquivar"
                            verbo="Arquivar"
                            :nome="contato.nome"
                            @confirmar="router.delete(route('painel.contatos.destroy', contato.uuid))"
                        />
                        <SecondaryButton v-else type="button" @click="router.patch(route('painel.contatos.restaurar', contato.uuid))">Restaurar</SecondaryButton>
                    </div>
                </section>
            </aside>
        </div>
    </AuthenticatedLayout>
</template>
