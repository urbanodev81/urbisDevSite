<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { LogOut, Moon, Sun, Monitor, User } from 'lucide-vue-next';
import TransitionRightCard from '../TransitionRightCard.vue';
import { useTheme } from '@/Composables/useTheme';

defineProps({ show: Boolean });
const emit = defineEmits(['close']);

const pagina = usePage();
const usuario = computed(() => pagina.props.auth?.user);
const perfil = computed(() => pagina.props.auth?.perfil);

const { tema, definirTema } = useTheme();

const opcoesDeTema = [
    { valor: 'light', rotulo: 'Claro', icone: Sun },
    { valor: 'dark', rotulo: 'Escuro', icone: Moon },
    { valor: 'system', rotulo: 'Do sistema', icone: Monitor },
];
</script>

<template>
    <!--
        Menu de conta é GAVETA, não dropdown — padrão da casa desde 09/08/2026
        (CORE §10). A caixa de 224px ancorada no canto não comporta identidade,
        papel, tema e saída com hierarquia, e no celular ela cobre a tela.
    -->
    <TransitionRightCard :show="show" variante="narrow" rotulo="Sua conta" @close="emit('close')">
        <div class="flex h-full flex-col">
            <div class="border-b border-line px-5 py-4">
                <p class="font-display text-xl font-bold uppercase leading-tight text-fg">
                    {{ usuario?.name }}
                </p>
                <p class="mt-1 truncate text-sm text-fg-muted">{{ usuario?.email }}</p>
                <p v-if="perfil" class="mt-2 inline-block rounded-full bg-accent-subtle px-2.5 py-1 font-label text-label uppercase text-accent">
                    {{ perfil }}
                </p>
            </div>

            <div class="flex-1 space-y-1 overflow-y-auto p-3">
                <Link
                    :href="route('profile.edit')"
                    class="flex min-h-[44px] items-center gap-3 rounded-md px-3 py-2 text-sm text-fg-muted transition hover:bg-surface-sunken hover:text-fg"
                    @click="emit('close')"
                >
                    <User class="h-5 w-5" aria-hidden="true" />
                    Meus dados e senha
                </Link>

                <div class="pt-4">
                    <p class="mb-2 px-3 font-label text-label uppercase text-fg-subtle">Tema</p>
                    <div class="grid grid-cols-3 gap-1 px-1">
                        <button
                            v-for="opcao in opcoesDeTema"
                            :key="opcao.valor"
                            type="button"
                            class="flex min-h-[44px] flex-col items-center justify-center gap-1 rounded-md border px-2 py-2 text-xs transition"
                            :class="tema === opcao.valor
                                ? 'border-line-strong bg-accent-subtle text-accent'
                                : 'border-line text-fg-muted hover:bg-surface-sunken'"
                            :aria-pressed="tema === opcao.valor"
                            @click="definirTema(opcao.valor)"
                        >
                            <component :is="opcao.icone" class="h-4 w-4" aria-hidden="true" />
                            {{ opcao.rotulo }}
                        </button>
                    </div>
                </div>
            </div>

            <div class="border-t border-line p-3">
                <button
                    type="button"
                    class="flex min-h-[44px] w-full items-center gap-3 rounded-md px-3 py-2 text-sm font-semibold text-danger transition hover:bg-danger-subtle"
                    @click="router.post(route('logout'))"
                >
                    <LogOut class="h-5 w-5" aria-hidden="true" />
                    Sair
                </button>
            </div>
        </div>
    </TransitionRightCard>
</template>
