---
titulo: O captcha que tirou do ar o formulário da Imóveis Jacob
descricao: Por que saímos do Cloudflare Turnstile para um captcha próprio, e o que perdemos na troca.
data: 2026-11-17
acao_texto: Conversar sobre o meu sistema
imagem: /img/guias/captcha.jpg
imagem_alt: Mãos digitando no teclado de um notebook
imagem_credito: Foto: Thomas Lefebvre, Wikimedia Commons (CC0)
tags: captcha, segurança, Cloudflare, imobiliárias, desenvolvimento web
---

O formulário "Procuro imóvel" do site da Imóveis Jacob, imobiliária que usa o nosso sistema, ficou cerca de duas semanas fora do ar. O problema não estava no código do formulário.

## O que aconteceu

Usávamos o Cloudflare Turnstile, um bom captcha. A chave dele só aceita os domínios cadastrados antes no painel da Cloudflare. Em sistema com domínio próprio por cliente, isso vira um cadastro manual antes de cada site entrar no ar.

O domínio da Jacob ficou de fora. O widget mostrava `Error: 110200`, o formulário parou de aceitar envios, e tiramos ele da página até resolver.

## O que fizemos

Trocamos pelo Altcha, hospedado por nós, em todos os sistemas. Ele não depende de cadastro por domínio. Aproveitamos para mudar duas regras:

- **Sem a chave configurada, o sistema nem sobe em produção.** Antes, a verificação podia ficar desligada sem ninguém notar.
- **Cada desafio resolvido só vale uma vez.** Sem isso, um robô resolveria um e o reusaria mil vezes.

## O que perdemos

"Tão seguro quanto, sem depender de ninguém" não existe. O Turnstile é forte porque a Cloudflare vê o tráfego de milhões de sites e reconhece um robô antes do desafio. Isso não se hospeda em casa.

Contra script e robô em massa, com limite de tentativas e campo-isca, fica equivalente. Contra um atacante determinado, fica abaixo. Para o nosso caso, a troca valeu; só não foi um empate.

## O que levar daqui

Um captcha que não carrega no domínio do cliente não protege nada: só bloqueia quem queria falar com ele. Depois de publicar um site, envie o formulário de verdade, no endereço real.
