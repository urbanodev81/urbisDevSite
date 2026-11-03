<script setup>
import { computed, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { Menu, UserCircle2 } from 'lucide-vue-next';
import Sidebar from '@/Components/Painel/Sidebar.vue';
import MenuConta from '@/Components/Painel/MenuConta.vue';
import AppAlert from '@/Components/AppAlert.vue';

/**
 * O shell do painel.
 *
 * Sidebar fixa no desktop, gaveta no celular. O conteúdo tem largura máxima
 * porque tabela de 2000px de largura não se lê.
 */
const pagina = usePage();

const menuAberto = ref(false);
const contaAberta = ref(false);

const flash = computed(() => pagina.props.flash ?? {});
const aviso = ref(null);

/*
 * O flash vira um aviso visível e some sozinho.
 *
 * `deep` no watch porque `flash` é um objeto novo a cada resposta do Inertia,
 * mas o Vue compararia as referências — sem isto, salvar duas vezes seguidas
 * com a mesma mensagem não reabriria o aviso, e quem salvou ficaria sem
 * confirmação nenhuma na segunda vez.
 */
watch(
    flash,
    (valor) => {
        if (valor?.sucesso) {
            aviso.value = { message: valor.sucesso, type: 'success' };
        } else if (valor?.erro) {
            aviso.value = { message: valor.erro, type: 'danger' };
        }

        if (aviso.value) {
            setTimeout(() => (aviso.value = null), 5000);
        }
    },
    { immediate: true, deep: true },
);

/*
 * Tour guiado (PADROES.md §Tour guiado): entra com as telas de verdade
 * (blocos 2 a 4 da 246), pelo pacote `@urbanodev/tutorial` como no molde.
 * No bloco 1 não há tela que peça tour.
 */
</script>

<template>
    <div class="min-h-screen bg-bg">
        <Sidebar :aberta="menuAberto" @fechar="menuAberto = false" />

        <div class="lg:pl-72">
            <header
                class="sticky top-0 z-30 flex items-center justify-between gap-3 border-b border-line
                       bg-surface/95 px-4 py-3 backdrop-blur sm:px-6"
            >
                <button
                    type="button"
                    class="grid h-11 w-11 place-items-center rounded-md text-fg-muted transition hover:bg-surface-sunken lg:hidden"
                    aria-label="Abrir menu"
                    @click="menuAberto = true"
                >
                    <Menu class="h-5 w-5" aria-hidden="true" />
                </button>

                <div class="min-w-0 flex-1">
                    <slot name="cabecalho" />
                </div>

                <button
                    type="button"
                    class="flex min-h-[44px] items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-fg-muted transition hover:bg-surface-sunken"
                    aria-label="Abrir menu da conta"
                    @click="contaAberta = true"
                >
                    <UserCircle2 class="h-5 w-5" aria-hidden="true" />
                    <span class="hidden max-w-[10rem] truncate sm:inline">{{ pagina.props.auth?.user?.name }}</span>
                </button>
            </header>

            <main class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:py-8">
                <slot />
            </main>
        </div>

        <MenuConta :show="contaAberta" @close="contaAberta = false" />

        <AppAlert
            :show="aviso !== null"
            :message="aviso?.message"
            :type="aviso?.type"
            @close="aviso = null"
        />
    </div>
</template>
