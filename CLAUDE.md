# urbisDev Site — Contexto para IA

> Lido automaticamente no início de toda sessão neste repo. É curto **de
> propósito**: aqui é um site institucional, não um sistema. Se a resposta
> pra "onde isso vive?" for mais de uma frase, provavelmente você está no
> repo errado.

## O que é (e o que NÃO é)

Landing page da Urbano Dev — <https://urbisdev.tech>. Laravel 12 mínimo
dentro de `src/`, servido por Nginx + PHP-FPM em Docker. Duas rotas de
página, um `ContactController`, Altcha auto-hospedado no formulário (era Turnstile até 29/09/2026).

**As views seguem um layout (desde 23/08/2026).** `layouts/site.blade.php`
carrega o casco — head, header, rodapé, widget de acessibilidade, pilha
social, voltar-ao-topo, rolagem suave e o script de tema — e cada página
traz só o que é dela:

```blade
@extends('layouts.site')
@section('titulo', '…')  @section('descricao', '…')
@push('estilos') <style>…</style> @endpush
@section('conteudo') … @endsection
@push('scripts') <script>…</script> @endpush
```

Antes disso cada página carregava a sua CÓPIA do casco, e a duplicação já
tinha cobrado: o widget de acessibilidade existia duas vezes, as correções
de alvo de toque de 44px tinham sido feitas só na página nova, e o rodapé
de uma tinha as redes sociais e o da outra não. **Página nova estende o
layout — não copia a irmã.**

**O site tem tema claro e escuro** (`config` nenhuma: classe `.claro` no
`<html>`, gravada em `localStorage` sob `urbis-tema`, com o seletor no
painel de acessibilidade). A marca continua nascendo escura — "cidade à
noite" é identidade, não modo. Cor literal existe em **um** arquivo:
`partials/tokens.blade.php`. Mudou token? Rode
`python3 ../socrates/docs/urbis-design/contraste.py` — e leia o CORE antes
(`../socrates/docs/urbis-design/CORE.md`).

**Demonstrações (desde 11/10/2026).** `/demonstracoes` lista páginas de
exemplo com dado INVENTADO, para o visitante mexer. A lista mora em
`src/config/demonstracoes.php` (o slug do endereço só escolhe uma entrada de
lá, nunca um arquivo), cada página é uma view em `views/demonstracoes/`, e os
gráficos são SVG desenhado por `src/public/demos/graficos.js`, sem biblioteca
e sem build. Cor de gráfico é token (`--serie-1..4`), validada para daltonismo
nos dois temas; todo gráfico tem dica no mouse e tabela com os mesmos números.
Continua sem banco: o dado é gerado no navegador, com semente fixa.
Desde a segunda leva (11/10) há três que não são painel: `sites` (o visitante
troca nome, cor e logo; o logo abre por `blob:` e não sai do aparelho),
`agendamento` (a agenda parte de amanhã e nada é enviado) e `relatorio` (o PDF é
a própria página impressa, sempre com os tokens do tema claro). O ano de vendas
da oficina mora em `public/demos/dados-oficina.js`, lido pelo painel comercial e
pelo relatório: mexer ali muda os dois.

**Ficha de lançamento (desde 12/10/2026)** é a única demonstração que não é
view com script: `public/demos/lancamento/*.html` são CÓPIA ESTÁTICA das cinco
fichas da base `imobiliaria`, com o CSS e o JS do build de lá, `noindex`, e uma
faixa no topo que troca de desenho e volta ao site. Não se edita à mão: refaz-se
pela receita de `src/resources/demos-fonte/lancamento/LEIA.md` (fora de
`public/` porque tem um `seed.php`). O nginx serve esses arquivos direto, então
o `SecurityHeaders` do Laravel não passa por eles.

**Demonstração atrás de formulário (desde 11/10/2026, noite).** Entrada com
`pede_contato => true` no config só entrega a ferramenta depois do formulário
de lead com Altcha (`DemoLeadController`): o lead é um e-mail para a casa e a
liberação é uma marca na sessão (`demos_liberadas`), sem banco. A primeira é a
`planilha`: CSV e .xlsx lidos no navegador por `public/demos/planilha.js`, sem
biblioteca (o .xlsx é aberto com `DecompressionStream`) e sem requisição
nenhuma. Tudo o que vem do arquivo entra na página por `textContent`. O estilo
de formulário saiu da home para `partials/formulario-estilos.blade.php`.

**Pergunte aos dados (desde 12/10/2026)** é a única demonstração que fala com
servidor: `POST /demonstracoes/pergunte/perguntar` (`DemoIaController`) leva a
pergunta ao Sócrates por `App\Support\PergunteAosDados`, com o token de projeto
do site (`DEMO_IA_URL` e `DEMO_IA_TOKEN`; sem os dois ela some da lista e dá
404). A chave de IA, o lead e os tetos (por pessoa e por dia) moram LÁ. A tabela
que a IA lê é fixa, `resources/demos/oficina-resumo.json`, e é a mesma que a
página mostra: mudou `public/demos/dados-oficina.js`, rode
`node resources/demos-fonte/pergunte/resumir.cjs`. A resposta entra na página
por `textContent`. O formulário de contato das duas demonstrações com
`pede_contato` é um só: `partials/demo-porta.blade.php`.

**SEO e GEO (desde 18/10/2026)** moram em `partials/seo.blade.php`, incluído
pelo layout: canônico, Open Graph (imagem única `public/og-urbisdev.png`) e o
JSON-LD da casa (o que ela faz, contato). Página nova não mexe nisso: só define
`titulo` e `descricao`, e `@section('robots', 'noindex, follow')` quando não deve
ir ao buscador. `/sitemap.xml` e `/llms.txt` saem de `App\Support\PaginasPublicas`,
que lê `config/demonstracoes.php` e o portfólio; `public/robots.txt` deixa os
robôs de IA entrarem de propósito. **Nenhum produto (USI, Synapse, SSB) nem
cliente pelo nome** em texto da home ou nesses dados — decisão dele. Mudou uma
frente da home? Mude junto o `OfferCatalog` do partial e o `seo/llms.blade.php`.
Escrever `@context` dentro de `{!! !!}` vira a diretiva do Laravel 12: monte o
JSON no `@php`. A 404 é `errors/404.blade.php`, que estende o layout.

**Não tem** banco, migration, autenticação, painel, tenancy nem suíte de
testes — e isso é decisão, não pendência. Não sugira módulo, model ou
`php artisan make:*` aqui sem o Ricardo pedir: o valor deste repo é ele
continuar pequeno o bastante pra o deploy nunca ser assustador.

Este é o único repo do ecossistema que **não** é satélite do Sócrates —
não manda presença, não aparece no kanban. (Desde 12/10/2026 tem um token de
projeto, usado só pela demonstração de IA.)

**Mas desde 12/10/2026 ele LÊ o portfólio de lá** (pendência 203, decisão do
Ricardo). A seção "Trabalhos" da home, a página `/trabalhos` e o link do menu
saem de `GET <Sócrates>/api/portfolio`, endereço público e sem token: é no
Sócrates, na guia Portfólio de cada projeto, que se marca o que aparece, em que
categoria e se o nome do cliente pode sair. Aqui mora só `App\Support\Portfolio`:
lê com teto de 4 s, guarda no cache de arquivo (uma cópia que vence em 30 min e
outra que não vence) e copia as capturas para `storage/app/private/portfolio`.
Com o Sócrates fora, o site mostra a última cópia; sem cópia nenhuma, ou sem
`PORTFOLIO_URL` no `.env`, a seção, a página e o link simplesmente não existem.
Continua sem banco. Texto de trabalho não se edita aqui: edita-se lá.

**CSP em report-only (desde 18/10/2026, pendência 136).** O
`SecurityHeaders` manda `Content-Security-Policy-Report-Only` com a política que
o site QUER ter; o navegador relata ao `POST /csp-relato`
(`CspRelatoController`) e a lista fica em `storage/logs/csp-*.log`, uma linha
por violação distinta, com teto diário. A medição de 18/10 deu seis itens, todos
nossos: cinco `<script>` embutidos (tema, acessibilidade, menu, barra de
progresso, grade de janelas da home) e o `onclick` do menu. Tirados esses para
arquivo, a política troca para `Content-Security-Policy`. O `limit_req` do site
mora no nginx do HOST (cópia em `infra/nginx-host-urbisdev.conf`): é o único
andar que vê o IP de quem visita. O Laravel confia no `X-Forwarded-For` e no
`X-Forwarded-Proto` que ele manda (`trustProxies` em `bootstrap/app.php`, só
esses dois); até 18/10 via todo mundo como o gateway do Docker, e cada
`throttle` era um balde só para a internet inteira.

## Rodar

```bash
docker compose up -d --build     # http://localhost:8100
```

Mailpit local em <http://localhost:8027> (é **do repo**, não a Mailpit
compartilhada da 8040 — este projeto não usa a infra `urbanodev`).

## Deploy — leia ANTES de tocar em `.github/workflows/deploy.yml`

`push` na branch **`pre-prod`** dispara: rsync (`easingthemes/ssh-deploy`)
pra `/home/urbisdev/site/` na VPS, depois `docker compose build`,
`up -d` e `nginx -s reload` por SSH. **`pre-prod` é a branch de produção —
a `main` não é.**

**Desde 23/08/2026 o deploy não derruba mais o site.** A receita antiga era
`down` && `up -d --build`, que deixava o site fora do ar durante a construção
inteira (~100 s no deploy de 20/08). Agora constrói primeiro, com o site no ar,
e só então recria o que mudou. Medido com `curl` a cada 250 ms: **zero falha em
172 amostras**. O `nginx -s reload` no fim existe porque `nginx.conf` é
montado, não copiado — mudança nele não faz o compose recriar o container, e
era o `down` que a aplicava sem querer.

### Qual branch está onde (corrigido em 15/08/2026)

Isto está escrito porque a topologia daqui gerou **três alarmes falsos
seguidos** nos checkpoints do Sócrates ("urbisdev parado fora da main
desde 31/07"), e nenhum deles existia: o site está no ar, com o tema
"cidade à noite", desde 31/07.

- **`pre-prod`** = o que está publicado em <https://urbisdev.tech>. É a
  única que deploya.
- ⚠️ **Medido em 11/10/2026: a `main` está 29 commits ATRÁS da `pre-prod`**, ao
  contrário do que as linhas abaixo dizem (ela nem tem `layouts/site.blade.php`).
  Até alguém alinhar as duas, branch de trabalho sai da `pre-prod`.
- **`main`** = `pre-prod` + commits que **não mudam o site** (documentação,
  `.gitignore`). Ela ficou 12 commits atrás entre 04/07 e 15/08, e era
  essa defasagem que a auditoria cross-repo lia como "trabalho perdido".
- Branch de trabalho (`feat/*`) sai da `main` e volta pra ela.

**Commit que não muda o site continua pegando carona no próximo deploy de
conteúdo** — mas o motivo mudou. Antes de 23/08/2026 era caro de verdade
(~2 min de site fora do ar para entregar zero mudança visível); hoje o deploy
não derruba mais nada, então é só higiene: um deploy por assunto, e a `main`
podendo estar legitimamente à frente da `pre-prod` aqui.

Corolário pra quem audita: **comparar com a `main` não diz se o site está
atualizado.** A pergunta certa é `git log origin/pre-prod..<branch>` — e,
melhor ainda, `curl` no endereço real.

### Armadilhas do CD (todas custaram um incidente real em 31/07/2026)

1. **`--delete` do rsync apaga o que só existe no servidor.** Foi assim
   que o deploy derrubou o site: levou junto o `.env` da raiz, o
   `src/.env.production` e os `certbot/conf/*.pem`. O input `EXCLUDE` hoje
   protege `.env*`, `storage`, `certbot` e `src/bootstrap/cache` —
   **nunca remova uma dessas entradas** sem entender o que só existe lá.
2. **`--exclude` dentro de `ARGS` não funciona** no `ssh-deploy@v5`: as
   aspas viram argumento posicional e ele morre com `Unexpected remote
   arg` antes mesmo de conectar. Exclusão vai no input `EXCLUDE`, não em
   `ARGS`.
3. **`src/bootstrap/cache` volta com dono errado a cada deploy.** O `-a`
   do rsync reaplica as permissões do checkout (`urbisdev:755`), o
   www-data perde escrita e o site responde 500. Por isso a pasta está no
   `EXCLUDE`.
4. **`docker-compose.override.yml` é de desenvolvimento e não pode subir.**
   Ele já vazou pra VPS e deixou a produção rodando `APP_ENV=local` +
   `APP_DEBUG=true` por quase um mês, com o Mailpit de dev junto. Está no
   `EXCLUDE` — mantenha lá.
5. **Quem termina TLS é o nginx do HOST, não o container.** Os certs reais
   vivem em `/etc/letsencrypt` do host; o `certbot/` do repo é cópia
   montada pelo container. Foi por isso que o HTTPS não caiu durante o
   incidente — não conclua daí que o `certbot/` do repo é descartável.

## Fluxo de trabalho — AFTSCD

Mesmo fluxo dos outros repos (**A**nalise → **F**aça → **T**este →
**S**egurança → **C**ommit → **D**ocumente → **+**), com duas ressalvas
honestas pro tamanho daqui:

- **Não há suíte de testes.** A etapa **T** é smoke real: subir o
  container e conferir a página (Playwright ou `curl`). Não invente
  `tests/` só pra cumprir a letra do fluxo.
- **Não há `CHANGELOG.md` nem `docs/`.** O `.githooks/commit-msg` está
  ativo e, por não achar `docs/SESSAO_ATUAL.md`, só **avisa** sobre o
  checkpoint em vez de bloquear. O checkpoint continua valendo: ele é um
  commit no **Sócrates** (`../socrates/docs/SESSAO_ATUAL.md`), que é o
  "onde paramos" do ecossistema inteiro. Se for marcar `[changelog]` num
  commit aqui, crie o `CHANGELOG.md` antes — senão o hook recusa.

Repo clonado do zero precisa de `git config core.hooksPath .githooks`.

## Servidores MCP

Este repo não tem `.mcp.json`. É consciente: `laravel-boost` precisaria do
container de pé (aqui o fluxo normal é subir só pra checar o site), e
`playwright`/`context7` fazem pouca diferença numa página só. Se um dia a
sessão precisar de navegação semântica pesada, o padrão dos 5 repos
Laravel está em `../socrates/docs/PADROES.md` § "Servidores MCP".

## Referências

- `README.md` — stack e estrutura de pastas.
- `../socrates/docs/SESSAO_ATUAL.md` — "onde paramos" do ecossistema; o
  incidente do CD está no checkpoint 18 (31/07/2026), com a cadeia de
  causas run a run.
- `../socrates/docs/PADROES.md` — o que é padrão comum aos sistemas. A
  pilha social flutuante e o scroll suave desta landing seguem o padrão da
  Welcome do SSB descrito lá.
