# urbisDev Site — Contexto para IA

> Lido automaticamente no início de toda sessão neste repo. É curto **de
> propósito**: aqui é um site institucional, não um sistema. Se a resposta
> pra "onde isso vive?" for mais de uma frase, provavelmente você está no
> repo errado.

## O que é (e o que NÃO é)

Landing page da Urbano Dev — <https://urbisdev.tech>. Laravel 12 mínimo
dentro de `src/`, servido por Nginx + PHP-FPM em Docker. Uma rota, um
`ContactController`, um `welcome.blade.php`, Turnstile no formulário.

**Não tem** banco, migration, autenticação, painel, tenancy nem suíte de
testes — e isso é decisão, não pendência. Não sugira módulo, model ou
`php artisan make:*` aqui sem o Ricardo pedir: o valor deste repo é ele
continuar pequeno o bastante pra o deploy nunca ser assustador.

Este é o único repo do ecossistema que **não** é satélite do Sócrates —
não manda presença, não tem token de projeto, não aparece no kanban.

## Rodar

```bash
docker compose up -d --build     # http://localhost:8100
```

Mailpit local em <http://localhost:8027> (é **do repo**, não a Mailpit
compartilhada da 8040 — este projeto não usa a infra `urbanodev`).

## Deploy — leia ANTES de tocar em `.github/workflows/deploy.yml`

`push` na branch **`pre-prod`** dispara: rsync (`easingthemes/ssh-deploy`)
pra `/home/urbisdev/site/` na VPS, depois `docker compose up -d --build`
por SSH. Note que **`pre-prod` está à frente da `main`** — a main não é a
branch de produção aqui.

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
