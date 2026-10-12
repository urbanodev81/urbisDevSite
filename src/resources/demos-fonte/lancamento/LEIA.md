# Ficha de lançamento: de onde vem a cópia

`public/demos/lancamento/` é uma **cópia estática** das cinco fichas de
lançamento da base dos sites de imobiliária (`sistemas/servicos/imobiliaria`),
tirada de uma prévia local com dado inventado. Não há sistema atrás: links
para outras páginas e o formulário ficam parados e avisam (`demo.js`).
Tirada em 12/10/2026, da base em `826580a`.

Esta pasta fica **fora de `public/`** de propósito: tem um `seed.php`.

Para refazer (a base mudou de desenho, ou o texto do empreendimento):

1. `python3 gerar.py` nesta pasta: redesenha as ilustrações em `img/`.
2. Subir a prévia da base em `127.0.0.1:18998`, em contêiner, com SQLite em
   `/tmp`, `IMOB_MODO=conectado`, `CAPTCHA_DRIVER=null`, e rodar o `seed.php`
   (ele chama o `Sincronizador`). Usar `artisan serve --no-reload`: sem isso
   as variáveis do `docker run` não chegam ao servidor e ele lê o `.env`.
3. `python3 copiar.py ../../../public/demos/lancamento`: baixa as cinco
   páginas, troca os endereços, tira `canonical`, Open Graph, JSON-LD e
   manifesto, marca `noindex`, põe a faixa de volta ao site e copia o CSS e
   o JS do build da base (sem o registro de service worker).
4. Refazer as miniaturas de `mini/` (720×450, WebP) e conferir em navegador.

Os nomes de arquivo (`modern`, `classic`, `minimal`, `gallery`, `brochure`)
são os identificadores de layout da base; os nomes que o visitante lê
(Essencial, Capa cheia, Na planta, Galerias, Livro) estão na view.
