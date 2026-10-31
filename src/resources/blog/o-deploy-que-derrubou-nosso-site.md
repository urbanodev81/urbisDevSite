---
titulo: O deploy automático que derrubou o nosso próprio site
descricao: Uma opção de sincronização apagou arquivos que só existiam no servidor. O que mudou depois disso.
data: 2026-11-01
rascunho: sim
acao_texto: Conversar sobre o meu sistema
---

Em 31 de julho de 2026, o nosso deploy automático tirou o nosso próprio site do ar. Ele fez exatamente o que mandamos.

## O que aconteceu

O deploy copiava os arquivos com `rsync --delete`, que apaga no servidor tudo o que não existe no repositório. Só que o servidor guardava coisas que nunca entram em repositório: as configurações de ambiente e cópias dos certificados.

Foram todas embora. O HTTPS só não caiu porque quem cuida dele é outro serviço, fora daquela pasta.

## O que mais achamos

Investigar um incidente costuma revelar outros. Um arquivo de configuração de desenvolvimento tinha ido para o servidor, e a produção rodou em modo de depuração por quase um mês. Nada quebrou, por isso ninguém viu.

## O que mudou

- **Deploy sem queda.** Antes o site saía do ar durante toda a construção, cerca de 100 segundos. Hoje ele constrói primeiro e só depois troca. Medimos com uma requisição a cada 250 ms: zero falha em 172 amostras.
- **Publicar virou `git pull`.** O servidor é um clone do repositório, sem sincronização que apaga arquivo.
- **O arquivo de desenvolvimento não chega mais ao servidor.**

## O que levar daqui

Pergunte a quem cuida do seu sistema: o que existe só no servidor? Se ninguém souber responder, o próximo deploy pode descobrir por você.
