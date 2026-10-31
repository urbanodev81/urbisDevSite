---
titulo: O deploy automático que derrubou o nosso próprio site
descricao: Uma opção de sincronização apagou arquivos que só existiam no servidor. O que mudou depois disso.
data: 2026-11-01
acao_texto: Conversar sobre o meu sistema
imagem: /img/guias/deploy.jpg
imagem_alt: Cabos de rede azuis ligados a um switch de servidor
imagem_credito: Foto: Raysonho, Wikimedia Commons (CC0)
tags: deploy, DevOps, desenvolvimento web, lições aprendidas
---

O nosso deploy automático já tirou o nosso próprio site do ar. Ele fez exatamente o que mandamos. O nosso sistema nos alertou na hora, e duas horas depois o site estava de volta.

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
