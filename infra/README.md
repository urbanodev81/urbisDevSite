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

## Subir / atualizar

```bash
cd ~/site-dev
git fetch && git checkout <branch> && git pull
docker compose -f docker-compose.yml -f docker-compose.vps-dev.yml up -d --build
```

**Sempre com os dois `-f`.** O `docker-compose.override.yml` do repositório é
para a máquina do desenvolvedor e liga `APP_DEBUG` — num endereço alcançável
pela internet isso entrega stack trace com caminho de arquivo e configuração.

## Três armadilhas que já custaram tempo aqui

1. **`ports` do Compose SOMA, não substitui.** Sem o `!override` no arquivo de
   VPS, as portas do site de produção (8100/8443) entram junto e o container
   nem sobe: *"port is already allocated"*.
2. **Porta escolhida no chute colide.** A 8120 parecia livre e era do
   `monitoramento-gatus`. Confira com `ss -tln | grep :<porta>` antes.
3. **O Basic Auth não é frescura.** Um site institucional duplicado e
   indexável divide a autoridade do domínio verdadeiro com uma cópia, e o
   buscador escolhe sozinho qual mostrar. O `.htpasswd-dev` é o mesmo dos
   tiers dev dos seis sistemas.

## Certificado

Emitido por `certbot --nginx -d site-dev.urbisdev.tech`, com renovação
automática pelo timer do certbot, como os demais subdomínios. O DNS de
`*.urbisdev.tech` é curinga — subdomínio novo não precisa de mexida no painel.
