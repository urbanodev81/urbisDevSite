#!/usr/bin/env python3
"""Gera `resources/css/tokens-do-painel.css` a partir dos tokens do SITE.

Por que existe (03/11/2026, pendência 246): a cor literal da casa mora em UM
arquivo, `resources/views/partials/tokens.blade.php` (CORE §0). O painel é
Tailwind, e o Tailwind só aplica opacidade (`bg-overlay/60`) em cor escrita
como trio RGB. Então o painel não tem paleta própria: este script LÊ o arquivo
do site e escreve os mesmos valores em trio RGB, mais os tons derivados que o
molde do painel usa (fundo afundado, tons "subtle" de aviso). Os derivados são
mistura dos tokens do site, nunca cor inventada.

Mudou um token no site? Rode, a partir de `src/`:

    python3 resources/css/tokens-do-painel.py

e faça commit do CSS junto. O teste `TokensDoPainelTest` falha se os dois
arquivos divergirem.
"""

import re
from pathlib import Path

RAIZ = Path(__file__).resolve().parents[2]
ORIGEM = RAIZ / "resources/views/partials/tokens.blade.php"
DESTINO = RAIZ / "resources/css/tokens-do-painel.css"

# token do painel -> token do site
DIRETOS = {
    "bg": "bg",
    "surface": "surface",
    "surface-raised": "surface-raised",
    "fg": "fg",
    "fg-muted": "fg-muted",
    "fg-subtle": "fg-subtle",
    "fg-on-accent": "fg-on-accent",
    "border": "border",
    "border-input": "border-input",
    "accent": "accent",
    "accent-hover": "accent-hover",
    "success": "ok",
    "warning": "warn",
    "danger": "error",
}


def blocos(texto: str) -> dict[str, dict[str, str]]:
    """Os dois temas do site: `:root` (escuro, a marca) e `html.claro`."""
    saida: dict[str, dict[str, str]] = {}
    for nome, corpo in re.findall(r"(:root|html\.claro)\s*\{(.*?)\n\}", texto, re.S):
        valores = dict(re.findall(r"(--[\w-]+):\s*(#[0-9A-Fa-f]{6})\s*;", corpo))
        if "--bg" in valores:  # o primeiro bloco de cada tema é o das cores
            saida.setdefault(nome, valores)
    return saida


def rgb(hexa: str) -> tuple[int, int, int]:
    hexa = hexa.lstrip("#")
    return tuple(int(hexa[i : i + 2], 16) for i in (0, 2, 4))  # type: ignore[return-value]


def mistura(a: str, b: str, peso_a: float) -> tuple[int, int, int]:
    ra, rb = rgb(a), rgb(b)
    return tuple(round(x * peso_a + y * (1 - peso_a)) for x, y in zip(ra, rb))  # type: ignore[return-value]


def trio(c: tuple[int, int, int]) -> str:
    return f"{c[0]} {c[1]} {c[2]}"


def tema(v: dict[str, str], escuro: bool) -> list[str]:
    t = {k: v["--" + s] for k, s in DIRETOS.items()}
    linhas = [f"    --{k}: {trio(rgb(h))};" for k, h in t.items()]
    sutil = 0.16 if escuro else 0.10
    derivados = {
        # Mais fundo que o fundo: entre o bg e o preto/branco do tema.
        "surface-sunken": mistura(t["bg"], "#000000" if escuro else "#FFFFFF", 0.75),
        "overlay": rgb(t["bg"]),
        "border-subtle": mistura(t["border"], t["surface"], 0.6),
        "border-strong": rgb(t["accent"]),
        "accent-subtle": mistura(t["accent"], t["surface"], sutil),
        "accent-ring": rgb(t["accent"]),
        "success-subtle": mistura(t["success"], t["surface"], sutil),
        "warning-subtle": mistura(t["warning"], t["surface"], sutil),
        "danger-subtle": mistura(t["danger"], t["surface"], sutil),
        # O site não tem "info": o painel usa o texto apagado como informação.
        "info": rgb(t["fg-muted"]),
        "info-subtle": mistura(t["fg-muted"], t["surface"], sutil),
    }
    linhas += [f"    --{k}: {trio(c)};" for k, c in derivados.items()]
    return linhas


def main() -> None:
    b = blocos(ORIGEM.read_text(encoding="utf-8"))
    escuro, claro = b[":root"], b["html.claro"]
    css = [
        "/*",
        " * GERADO por resources/css/tokens-do-painel.py a partir de",
        " * resources/views/partials/tokens.blade.php. NÃO edite à mão:",
        " * mude o token do site e rode o script (TokensDoPainelTest confere).",
        " *",
        " * Trio RGB porque o Tailwind só aplica opacidade (`bg-overlay/60`) assim.",
        " * A marca nasce escura: `:root` é o escuro e `html.claro` o claro, como",
        " * no site. `.dark` dentro do claro força o escuro (a porta do painel).",
        " */",
        ":root,",
        "html.claro .dark {",
        "    color-scheme: dark;",
        *tema(escuro, escuro=True),
        "}",
        "",
        "html.claro {",
        "    color-scheme: light;",
        *tema(claro, escuro=False),
        "}",
        "",
    ]
    DESTINO.write_text("\n".join(css), encoding="utf-8")
    print(f"escrito {DESTINO.relative_to(RAIZ)}")


if __name__ == "__main__":
    main()
