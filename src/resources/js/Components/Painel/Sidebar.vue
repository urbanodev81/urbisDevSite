<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    ChartColumn, Inbox, LayoutDashboard, MessagesSquare, Newspaper, UserCog, ScrollText, X,
} from 'lucide-vue-next';

defineProps({ aberta: Boolean });
defineEmits(['fechar']);

const pagina = usePage();
const permissoes = computed(() => pagina.props.auth?.permissoes ?? {});

/**
 * O menu.
 *
 * A ordem é a de USO, não a de importância conceitual: quem abre este painel
 * abre para ver quem escreveu. Contatos primeiro, administração por último.
 *
 * `permissao` esconde o item de quem não pode abrir — conforto de navegação.
 * **Quem protege é a rota** (`routes/web.php`), e esconder sem proteger é o
 * erro clássico. Proteger e ainda assim mostrar o link é só deixar alguém
 * bater num 403 todo dia.
 */
// Por blocos da 246: Contatos desde o bloco 2 (04/11/2026); Blog e
// Configuração do site entram nos blocos 3 e 4, cada um com a sua permissão
// (as permissões já nascem no PerfisESeguranca).
const grupos = [
    {
        titulo: null,
        itens: [
            { rotulo: 'Início', rota: 'painel.inicio', icone: LayoutDashboard, permissao: null },
            { rotulo: 'Contatos', rota: 'painel.contatos.index', icone: Inbox, permissao: 'leads' },
            { rotulo: 'Blog', rota: 'painel.blog.textos.index', icone: Newspaper, permissao: 'blog' },
            { rotulo: 'Comentários', rota: 'painel.blog.comentarios.index', icone: MessagesSquare, permissao: 'blog' },
            { rotulo: 'Leitores do blog', rota: 'painel.blog.leitores.index', icone: ChartColumn, permissao: 'blog' },
        ],
    },
    {
        titulo: 'Administração',
        itens: [
            { rotulo: 'Contas de acesso', rota: 'painel.usuarios.index', icone: UserCog, permissao: 'usuarios' },
            { rotulo: 'Trilha de alterações', rota: 'painel.auditoria.index', icone: ScrollText, permissao: 'auditoria' },
        ],
    },
];

const visiveis = computed(() =>
    grupos
        .map((grupo) => ({
            ...grupo,
            itens: grupo.itens.filter((item) => item.permissao === null || permissoes.value[item.permissao]),
        }))
        .filter((grupo) => grupo.itens.length > 0),
);

// `route().current()` com curinga: "painel.shows.*" mantém o item aceso na
// tela de detalhe e na de edição, não só na listagem.
// Só a rota de lista (`*.index`) vira curinga: antes, `painel.inicio` virava
// `painel.*` e o Início ficava aceso em toda tela (achado no smoke do 3b).
const ativo = (rota) => route().current(rota) || (rota.endsWith('.index') && route().current(rota.replace(/\.index$/, '.*')));
</script>

<template>
    <!-- Véu do celular. Opaco de verdade e clicável (CORE §10.2): backdrop
         transparente engole o primeiro toque sem sinalizar nada. -->
    <div
        v-if="aberta"
        class="fixed inset-0 z-40 bg-overlay/60 backdrop-blur-sm lg:hidden"
        @click="$emit('fechar')"
    />

    <aside
        class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col border-r border-line bg-surface
               transition-transform lg:translate-x-0"
        :class="aberta ? 'translate-x-0' : '-translate-x-full'"
        aria-label="Menu do painel"
    >
        <div class="flex items-center justify-between gap-2 border-b border-line px-5 py-4">
            <Link :href="route('painel.inicio')" class="min-w-0">
                <p class="truncate font-display text-lg font-black uppercase leading-none text-fg">
                    {{ $page.props.marca.nome }}
                </p>
                <p class="mt-1 font-label text-label uppercase text-accent">Painel</p>
            </Link>

            <button
                type="button"
                class="grid h-11 w-11 place-items-center rounded-md text-fg-muted lg:hidden"
                aria-label="Fechar menu"
                @click="$emit('fechar')"
            >
                <X class="h-5 w-5" aria-hidden="true" />
            </button>
        </div>

        <nav class="flex-1 overflow-y-auto px-3 py-4" data-tour="menu">
            <div v-for="grupo in visiveis" :key="grupo.titulo ?? 'raiz'" class="mb-5">
                <p v-if="grupo.titulo" class="mb-2 px-2 font-label text-label uppercase text-fg-subtle">
                    {{ grupo.titulo }}
                </p>

                <ul class="space-y-1">
                    <li v-for="item in grupo.itens" :key="item.rota">
                        <Link
                            :href="route(item.rota)"
                            class="flex min-h-[44px] items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition"
                            :class="ativo(item.rota)
                                ? 'bg-accent-subtle text-accent'
                                : 'text-fg-muted hover:bg-surface-sunken hover:text-fg'"
                            :aria-current="ativo(item.rota) ? 'page' : undefined"
                            @click="$emit('fechar')"
                        >
                            <component :is="item.icone" class="h-5 w-5 shrink-0" aria-hidden="true" />
                            {{ item.rotulo }}
                        </Link>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- `pl-20`: o botão de acessibilidade flutua no canto de baixo à
             esquerda e cobria o link. -->
        <div class="border-t border-line py-3 pl-20 pr-5">
            <a
                href="/"
                target="_blank"
                rel="noopener"
                class="font-label text-label uppercase text-fg-subtle transition hover:text-accent"
            >
                Ver o site ↗
            </a>
        </div>
    </aside>
</template>
