import { computed, ref } from 'vue';

/*
 * Tema do painel = tema do site (03/11/2026, pendência 246).
 *
 * A MESMA chave e a MESMA regra do script do `<head>` do site
 * (`layouts/site.blade.php`) e do `app.blade.php` do painel: `urbis-tema`
 * guarda 'claro' ou 'escuro'; sem nada salvo, segue o sistema. A marca nasce
 * escura, então a classe que muda é `.claro` no `<html>`; `.dark` vai junto
 * só para as variantes `dark:` do Tailwind que vieram do molde.
 * Mexeu aqui, mexa nos dois scripts no mesmo commit (CORE §2 item 3).
 */
const CHAVE = 'urbis-tema';

function lerSalvo() {
    try {
        const valor = localStorage.getItem(CHAVE);

        if (valor === 'claro') return 'light';
        if (valor === 'escuro') return 'dark';
    } catch {
        // storage bloqueado: segue o sistema
    }

    return 'system';
}

function ficaEscuro(tema) {
    return tema === 'dark'
        || (tema === 'system'
            && typeof matchMedia !== 'undefined'
            && !matchMedia('(prefers-color-scheme: light)').matches);
}

function aplicar(tema) {
    const escuro = ficaEscuro(tema);
    document.documentElement.classList.toggle('claro', !escuro);
    document.documentElement.classList.toggle('dark', escuro);
}

const tema = ref(typeof document === 'undefined' ? 'system' : lerSalvo());

if (typeof matchMedia !== 'undefined') {
    matchMedia('(prefers-color-scheme: light)').addEventListener('change', () => {
        if (tema.value === 'system') aplicar('system');
    });
}

export function useTheme() {
    function definirTema(valor) {
        tema.value = valor;

        try {
            if (valor === 'system') localStorage.removeItem(CHAVE);
            else localStorage.setItem(CHAVE, valor === 'light' ? 'claro' : 'escuro');
        } catch {
            // storage bloqueado: vale só nesta página
        }

        aplicar(valor);
    }

    return {
        tema: computed(() => tema.value),
        escuro: computed(() => ficaEscuro(tema.value)),
        definirTema,
        alternar: () => definirTema(ficaEscuro(tema.value) ? 'light' : 'dark'),
    };
}
