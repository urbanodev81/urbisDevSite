# Infra do ambiente de teste (`site-dev.urbisdev.tech`)

> Criado em 19/08/2026. Estes arquivos são a **cópia versionada** do que está
> rodando na VPS — o original vive lá, e até aqui a infra da VPS não tinha
> controle de versão nenhum. Se você mudar um dos dois lados, mude o outro.

## Por que este ambiente existe

Publicar no `urbisdev.tech` é `git push` na branch `pre-prod`, e aquilo dispara
o CD. Não havia como **ver** uma página nova antes de ela ser o site público —
e a página `/como-trabalhamos` era justamente conteúdo institucional, que se
lê antes de decidir se vai ao ar.

Aqui a branch de trabalho roda num endereço próprio, atrás de senha.

## As peças

| Arquivo | Onde vive na VPS |
|---|---|
| `../docker-compose.vps-dev.yml` | `~/site-dev/docker-compose.vps-dev.yml` |
| `nginx-site-dev.conf` | `/etc/nginx/sites-available/site-dev.conf` (com link em `sites-enabled/`) |

O diretório na VPS é `~/site-dev`, um clone do repositório na branch que se
quer ver. Trocar de branch é `git fetch && git checkout <branch>` lá dentro.

## Primeira instalação — os três passos que o `up -d` NÃO faz

Descobertos do jeito difícil em 19/08/2026, com o site respondendo erro de PHP
na cara dele. **O bind mount `./src:/var/www/html` cobre o que a imagem tinha
lá dentro** — então `vendor/` construído no build simplesmente desaparece.

```bash
cd ~/site-dev
docker compose -f docker-compose.yml -f docker-compose.vps-dev.yml up -d --build

# 1. vendor: o build instalou na imagem, o mount escondeu. Instale no volume.
docker compose -f docker-compose.yml -f docker-compose.vps-dev.yml \
    exec -T app composer install --no-interaction --no-dev --optimize-autoloader

# 2. o .env do Laravel vive em src/, não na raiz (a raiz é do compose).
cp ~/site/src/.env src/.env
sed -i 's|^APP_URL=.*|APP_URL=https://site-dev.urbisdev.tech|; \
        s|^APP_ENV=.*|APP_ENV=staging|; \
        s|^APP_DEBUG=.*|APP_DEBUG=false|' src/.env

# 3. o PHP roda como www-data (uid 82) e o clone é do urbisdev (uid 1000).
chmod -R a+rwX src/storage src/bootstrap/cache

docker compose -f docker-compose.yml -f docker-compose.vps-dev.yml restart app
```

## Subir / atualizar

```bash
cd ~/site-dev
git fetch && git checkout <branch> && git pull
docker compose -f docker-compose.yml -f docker-compose.vps-dev.yml up -d --build
```

Se a branch nova mexeu em dependência, repita o passo 1 acima.

**Sempre com os dois `-f`.** O `docker-compose.override.yml` do repositório é
para a máquina do desenvolvedor e liga `APP_DEBUG` — num endereço alcançável
pela internet isso entrega stack trace com caminho de arquivo e configuração.

## Três armadilhas que já custaram tempo aqui

1. **`ports` do Compose SOMA, não substitui.** Sem o `!override` no arquivo de
   VPS, as portas do site de produção (8100/8443) entram junto e o container
   nem sobe: *"port is already allocated"*.
2. **Porta escolhida no chute colide.** A 8120 parecia livre e era do
   `monitoramento-gatus`. Confira com `ss -tln | grep :<porta>` antes.
3. **Conferir o status HTTP não prova nada.** Foi o meu erro em 19/08: um
   fatal de PHP com `display_errors` ligado devolve **200** com o erro no
   corpo, e o `curl -w '%{http_code}'` disse que estava tudo bem enquanto a
   página mostrava `Failed opening required vendor/autoload.php`. Confira
   **tamanho e conteúdo**: `curl -s <url> | grep -o '<title>[^<]*'`.
4. **O Basic Auth não é frescura.** Um site institucional duplicado e
   indexável divide a autoridade do domínio verdadeiro com uma cópia, e o
   buscador escolhe sozinho qual mostrar. O `.htpasswd-dev` é o mesmo dos
   tiers dev dos seis sistemas.

## Certificado

Emitido por `certbot --nginx -d site-dev.urbisdev.tech`, com renovação
automática pelo timer do certbot, como os demais subdomínios. O DNS de
`*.urbisdev.tech` é curinga — subdomínio novo não precisa de mexida no painel.
