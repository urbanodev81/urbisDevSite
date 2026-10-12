# Tira a cópia estática das cinco fichas de lançamento da base `imobiliaria`
# (prévia local com dado inventado) para dentro do site institucional.
# Uso: python3 copiar.py <pasta public/demos/lancamento do site>
import re, sys, shutil, pathlib, urllib.request
B = 'http://127.0.0.1:18998'
DEST = pathlib.Path(sys.argv[1]); BASE = pathlib.Path('/home/ricardourbano/dev/UrbanoDev/sistemas/servicos/imobiliaria')
LAYOUTS = [('modern', 'Essencial'), ('classic', 'Capa cheia'), ('minimal', 'Na planta'), ('gallery', 'Galerias'), ('brochure', 'Livro')]
(DEST / 'img').mkdir(parents=True, exist_ok=True); (DEST / 'assets').mkdir(exist_ok=True)
for f in (pathlib.Path(__file__).parent / 'img').glob('*.svg'): shutil.copy(f, DEST / 'img' / f.name)

FAIXA_CSS = '''<style>
.ud-faixa{position:relative;z-index:9999;display:flex;flex-wrap:wrap;align-items:center;gap:8px 16px;padding:10px 16px;background:#0f1424;color:#e8ecf5;font:500 14px/1.4 system-ui,-apple-system,"Segoe UI",sans-serif}
.ud-faixa a{color:#e8ecf5;text-decoration:none}
.ud-faixa__volta{display:inline-flex;align-items:center;min-height:44px;padding:0 6px;font-weight:600;text-decoration:underline!important;text-underline-offset:3px}
.ud-faixa__nota{color:#aab3c8}
.ud-faixa nav{display:flex;flex-wrap:wrap;gap:6px;margin-left:auto}
.ud-faixa nav a{display:inline-flex;align-items:center;min-height:44px;padding:0 14px;border:1px solid #3a4460;border-radius:999px}
.ud-faixa nav a:hover{border-color:#aab3c8}
.ud-faixa nav a[aria-current=page]{background:#e8ecf5;color:#0f1424;border-color:#e8ecf5;font-weight:600}
.ud-faixa a:focus-visible{outline:3px solid #ffd27a;outline-offset:2px}
.ud-aviso{position:fixed;left:50%;bottom:20px;transform:translateX(-50%);z-index:10000;max-width:min(92vw,460px);padding:12px 18px;border-radius:10px;background:#0f1424;color:#fff;font:500 14px/1.4 system-ui,sans-serif;box-shadow:0 8px 30px rgba(0,0,0,.35)}
.ud-aviso[hidden]{display:none}
@media (max-width:640px){.ud-faixa nav{margin-left:0;width:100%}.ud-faixa__nota{width:100%}}
@media print{.ud-faixa,.ud-aviso{display:none}}
</style>'''
def faixa(atual):
    nav = ''.join(f'<a href="{s}.html"{" aria-current=\"page\"" if s == atual else ""}>{n}</a>' for s, n in LAYOUTS)
    return (f'<div class="ud-faixa" role="region" aria-label="Sobre esta demonstração"><a class="ud-faixa__volta" href="/demonstracoes/lancamento">&larr; Demonstrações UrbisDev</a>'
            f'<span class="ud-faixa__nota">Empreendimento, imobiliária e números inventados. Nada aqui é enviado.</span><nav aria-label="Os cinco desenhos de ficha">{nav}</nav></div>'
            '<div class="ud-aviso" role="status" aria-live="polite" hidden></div>')

def pega(caminho): return urllib.request.urlopen(B + caminho).read().decode()
usados = set()
for slug, nome in LAYOUTS:
    h = pega(f'/lancamento/residencial-{slug}')
    # cabeça: sem canonical, og, ld+json, manifest nem nonce; nunca indexar
    h = re.sub(r'<link rel="canonical"[^>]*>\s*', '', h)
    h = re.sub(r'\s*<meta (property="og:|name="twitter:)[^>]*>', '', h)
    h = re.sub(r'<script type="application/ld\+json">.*?</script>\s*', '', h, flags=re.S)
    h = re.sub(r'<link rel="(manifest|icon|apple-touch-icon)"[^>]*>\s*', '', h)
    h = re.sub(r'<meta name="robots"[^>]*>', '<meta name="robots" content="noindex,nofollow">', h)
    h = re.sub(r' nonce="[^"]*"', '', h)
    h = re.sub(r'<meta name="csrf-token"[^>]*>\s*', '', h); h = re.sub(r'<input type="hidden" name="_token"[^>]*>', '', h)
    # endereços
    h = h.replace(B + '/previa/', 'img/').replace(B + '/build/assets/', 'assets/')
    usados |= set(re.findall(r'assets/([\w.-]+)', h))
    h = re.sub(r'href="' + re.escape(B) + r'/lancamento/residencial-(\w+)"', r'href="\1.html"', h)
    h = re.sub(r'<form([^>]*) action="[^"]*"', r'<form\1 action="#" data-demo-inerte', h)
    h = re.sub(r'href="(?:' + re.escape(B) + r'[^"]*|/[^"/][^"]*|https://wa\.me/[^"]*|https://urbanodev\.com\.br)"(\s+target="_blank")?(\s+rel="[^"]*")?', 'href="#" data-demo-inerte', h)
    h = h.replace('</head>', FAIXA_CSS + '</head>', 1)
    h = re.sub(r'(<body[^>]*>)', lambda m: m.group(1) + faixa(slug), h, count=1)
    h = h.replace('</body>', '<script src="demo.js"></script></body>', 1)
    sobra = re.findall(r'127\.0\.0\.1[^"\s<]*', h)
    (DEST / f'{slug}.html').write_text(h)
    print(slug, len(h) // 1024, 'KB', 'inertes', h.count('data-demo-inerte'), 'sobra de endereço local:', sorted(set(sobra)) or 'nenhuma')
# os pedaços que o app.js busca sob demanda (animação de entrada, vídeos) vão junto; o captcha não
usados |= {f.name for f in (BASE / 'public/build/assets').glob('*.js') if not f.name.startswith(('altcha', 'pt-br'))}
for a in sorted(usados):
    src = (BASE / 'public/build/assets' / a).read_text()
    if a.endswith('.js'): src = src.replace('"serviceWorker"in navigator', '!1')  # a cópia não registra service worker no site
    (DEST / 'assets' / a).write_text(src); print('asset', a)
(DEST / 'demo.js').write_text('''// Cópia estática da ficha de lançamento: o que levaria a outra página do
// site da imobiliária, ou enviaria um formulário, fica parado e avisa.
(function () {
    var aviso = document.querySelector('.ud-aviso'), relogio;
    function avisar(texto) {
        if (!aviso) return;
        aviso.textContent = texto; aviso.hidden = false;
        clearTimeout(relogio); relogio = setTimeout(function () { aviso.hidden = true; }, 3500);
    }
    document.addEventListener('click', function (e) {
        var a = e.target.closest && e.target.closest('a[data-demo-inerte]');
        if (a) { e.preventDefault(); e.stopPropagation(); avisar('Na demonstração este caminho fica parado: no site de verdade ele abre a página da imobiliária.'); }
    }, true);
    document.addEventListener('submit', function (e) {
        e.preventDefault(); e.stopPropagation(); avisar('Demonstração: nada foi enviado. No site de verdade o recado chega à imobiliária.');
    }, true);
})();
''')
