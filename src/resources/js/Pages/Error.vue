<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Compass, Lock, Moon, RefreshCw, ServerCrash, Sun, Timer, Wrench } from 'lucide-vue-next';
import { useTheme } from '@/Composables/useTheme';

// Tela de erro do site e do painel.
//
// ⚠️ Ela veio do BootHelpDesk junto com o resto do scaffold e ficou com o
// TEXTO dele: falava em "chamado removido" e assinava "BootHelpDesk" no topo,
// num site de banda. Corrigido em 20/09/2026, depois de o Ricardo abrir
// `duo-dev/logfin` e ver a marca errada na tela. Se outra tela parecer
// estranha aqui, desconfie da mesma origem — ver pendência 89.
//
// A tela é a MESMA para quem está no site e para quem está no painel: um erro
// acontece antes de o layout decidir quem está olhando, e uma tela de erro que
// depende de saber o papel é uma tela de erro a menos quando o papel é
// justamente o que falhou (403, 419).
const props = defineProps({
    status: { type: Number, required: true },
});

const { escuro, alternar } = useTheme();
const rotulo = computed(() => (escuro.value ? 'Mudar para o tema claro' : 'Mudar para o tema escuro'));

const CONTEUDO = {
    403: {
        icone: Lock,
        titulo: 'Acesso não autorizado',
        mensagem: 'Você não tem permissão para ver isto. Se precisa deste acesso, fale com quem administra o painel da banda.',
    },
    404: {
        icone: Compass,
        titulo: 'Página não encontrada',
        mensagem: 'O endereço não existe ou mudou de lugar. Confira o link e tente de novo.',
    },
    419: {
        icone: Timer,
        titulo: 'Sessão expirada',
        mensagem: 'Sua sessão expirou por segurança. Entre de novo e você volta de onde parou.',
    },
    429: {
        icone: Timer,
        titulo: 'Muitas tentativas',
        mensagem: 'Foram requisições demais em pouco tempo. Espere um instante antes de tentar outra vez.',
    },
    500: {
        icone: ServerCrash,
        titulo: 'Erro interno',
        mensagem: 'Algo quebrou do nosso lado, e não foi por sua causa. A falha já foi registrada.',
    },
    503: {
        icone: Wrench,
        titulo: 'Em manutenção',
        mensagem: 'O sistema está sendo atualizado agora. Deve levar poucos minutos.',
    },
};

const conteudo = computed(() => CONTEUDO[props.status] ?? {
    icone: ServerCrash,
    titulo: 'Algo não saiu como esperado',
    mensagem: 'Não foi possível concluir essa ação. Tente novamente em instantes.',
});

// Só oferece "Voltar" se houver para onde: numa aba aberta direto no link
// quebrado, `history.length` é 1 e o botão não levaria a lugar nenhum.
const temHistorico = typeof window !== 'undefined' && window.history.length > 1;

function voltar() {
    window.history.back();
}

function recarregar() {
    window.location.reload();
}
</script>

<template>
    <Head :title="`${conteudo.titulo} (${status})`" />

    <div class="flex min-h-screen flex-col bg-bg">
        <header class="flex items-center justify-between px-5 py-4 sm:px-8">
            <Link
                href="/"
                class="inline-flex items-center gap-2.5 rounded-md font-semibold tracking-tight text-fg focus:outline-none focus-visible:ring-2 focus-visible:ring-accent-ring"
            >
                <span class="grid h-9 w-9 place-items-center rounded-xl bg-accent text-base font-bold text-fg-on-accent">{{ $page.props.marca.inicial }}</span>
                {{ $page.props.marca.nome }}
            </Link>

            <button
                type="button"
                class="grid h-11 w-11 place-items-center rounded-xl border border-line bg-surface text-fg-subtle hover:text-fg focus:outline-none focus-visible:ring-2 focus-visible:ring-accent-ring"
                :aria-label="rotulo"
                @click="alternar"
            >
                <Sun v-if="escuro" class="h-5 w-5" aria-hidden="true" />
                <Moon v-else class="h-5 w-5" aria-hidden="true" />
            </button>
        </header>

        <main class="flex flex-1 items-center justify-center px-5 pb-16 sm:px-8">
            <div class="w-full max-w-lg rounded-card border border-line bg-surface p-6 sm:p-8">
                <!-- Ícone em tile, nunca solto sobre o fundo (CORE §8). -->
                <span class="grid h-12 w-12 place-items-center rounded-xl bg-accent-subtle text-accent">
                    <component :is="conteudo.icone" class="h-6 w-6" aria-hidden="true" />
                </span>

                <p class="mt-6 text-xs font-semibold uppercase tracking-[0.06em] text-fg-subtle">
                    Erro {{ status }}
                </p>

                <h1 class="mt-2 text-2xl font-semibold leading-tight text-fg">
                    {{ conteudo.titulo }}
                </h1>

                <p class="mt-3 text-sm leading-relaxed text-fg-muted">
                    {{ conteudo.mensagem }}
                </p>

                <!-- Um só elemento preenchido na tela (CORE §6). -->
                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <Link
                        href="/"
                        class="inline-flex min-h-[2.75rem] flex-1 items-center justify-center gap-2 rounded-xl bg-accent px-5 py-2.5 text-sm font-semibold text-fg-on-accent hover:bg-accent-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-accent-ring focus-visible:ring-offset-2 focus-visible:ring-offset-bg"
                    >
                        Ir para o início
                    </Link>

                    <button
                        v-if="temHistorico"
                        type="button"
                        class="inline-flex min-h-[2.75rem] items-center justify-center gap-2 rounded-xl border border-line px-5 py-2.5 text-sm font-semibold text-fg-muted hover:bg-bg focus:outline-none focus-visible:ring-2 focus-visible:ring-accent-ring dark:hover:bg-surface-sunken"
                        @click="voltar"
                    >
                        <ArrowLeft class="h-4 w-4" aria-hidden="true" />
                        Voltar
                    </button>

                    <!-- 419 e 429 são os dois casos em que tentar de novo é a
                         ação certa: a sessão se renova e o limite passa. -->
                    <button
                        v-if="status === 419 || status === 429"
                        type="button"
                        class="inline-flex min-h-[2.75rem] items-center justify-center gap-2 rounded-xl border border-line px-5 py-2.5 text-sm font-semibold text-fg-muted hover:bg-bg focus:outline-none focus-visible:ring-2 focus-visible:ring-accent-ring dark:hover:bg-surface-sunken"
                        @click="recarregar"
                    >
                        <RefreshCw class="h-4 w-4" aria-hidden="true" />
                        Tentar de novo
                    </button>
                </div>
            </div>
        </main>
    </div>
</template>
