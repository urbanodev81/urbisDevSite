# Changelog

Todas as mudanças relevantes deste projeto.

O formato segue [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/).
**Estamos pré-v1**: tudo vive em `## [Não lançado]` até o Ricardo dizer que
lançou. Não crie número de versão nem date release por conta própria.

> Este arquivo nasceu em 21/09/2026, reconstruído a partir do histórico —
> o repositório tinha dezessete commits de `feat`/`fix` e nenhum changelog,
> enquanto o `.githooks/commit-msg` daqui (idêntico ao dos irmãos) já exigia
> um no commit que fecha um grupo. Daqui para a frente ele cresce pelo fluxo
> normal: marque `[changelog]` no assunto do commit que FECHA o grupo.

## [Não lançado]

### Adicionado
- **Página 404 com a cara do site.** Endereço que não existe mostra "Esta
  página não existe" com caminho para o início, as demonstrações e o contato,
  em vez da tela "Not Found" em branco.
- **O site se apresenta direito a buscador, rede social e assistente de IA.**
  Cada página diz o seu endereço oficial e ganha o cartão de prévia que aparece
  ao colar o link no WhatsApp ou no LinkedIn. A home descreve a empresa, o que
  ela faz e como falar com ela num formato que o Google e as IAs leem sem
  adivinhar. Há também o mapa do site (`/sitemap.xml`), o `robots.txt` (aberto,
  inclusive para os robôs de IA) e um resumo em texto para assistentes de IA
  (`/llms.txt`). Nenhum produto nem cliente aparece pelo nome.

### Alterado
- **A home diz para quem trabalhamos.** O topo troca "SaaS, APIs, automações"
  por sites com painel e sistemas de gestão para imobiliárias, clínicas,
  sindicatos, oficinas e artistas. "O que fazemos" passa de dois para quatro
  cartões: imobiliárias e sites com painel (no ar), clínicas de terapia e
  sindicatos (em construção); "Blogs & Conteúdo" saiu.

### Removido
- **"Próximos passos"** saiu da home e do menu: anunciava como planejadas as
  plataformas com a marca do cliente, que já estão no ar.

### Corrigido
- **O site ganha ícone na aba do navegador**, o mesmo selo "UD" do cabeçalho.
  Até aqui `/favicon.ico` não existia e era o único erro no console.
- **Endereço que não existe mostra a página do site, não a tela crua do
  servidor.** Arquivo protegido (`/.env`) e `.php` que não existe
  (`/wp-login.php`) mostravam o "403 Forbidden" ou o "File not found." cru do
  servidor, em branco e sem marca. Agora mostram a mesma página 404 do site que
  as outras rotas já mostravam.

### Adicionado
- **Demonstração "Pergunte aos dados".** O visitante escreve a pergunta em
  português e uma IA responde lendo a tabela de vendas da oficina de exemplo,
  que fica à vista na página para conferir. Pede o contato antes de abrir e
  tem limite de perguntas por pessoa e por dia. Só aparece no site depois de
  ligada ao Sócrates.
- **Demonstração "Ficha de lançamento".** O mesmo empreendimento (inventado)
  em cinco desenhos de página, do mais simples ao livro de vendas com
  capítulos: são as fichas de lançamento do nosso site de imobiliária, para
  abrir, rolar e comparar. Nada é enviado e nenhum link sai da demonstração.
- **Trabalhos da casa, por tipo.** A home ganha a seção "Trabalhos", com um
  exemplo de cada tipo de site, e a página `/trabalhos` lista todos, com
  captura de tela e a marca "no ar" ou "em construção". A lista vem do
  Sócrates, onde se escolhe o que aparece; cliente que ainda não autorizou
  aparece sem nome e sem link. Com o Sócrates fora do ar o site segue
  mostrando a última lista que recebeu.

### Corrigido
- **Recado com mais de dois links é recusado**, com aviso claro, e código HTML
  colado no texto é descartado — menos spam na fila de quem atende.

### Segurança
- **O captcha do contato deixa a Cloudflare.** O formulário passa a usar o
  Altcha auto-hospedado, o mesmo dos sistemas da casa: o desafio sai do
  próprio site, sem serviço nem chave de terceiro, em português, e cada
  resposta vale uma vez só. O site era o último lugar do ecossistema ainda no
  Turnstile.
- **O site passa a mandar os cabeçalhos de segurança** que os produtos da casa
  já mandavam (HTTPS obrigatório, proteção contra enquadramento e contra troca
  de tipo de arquivo), e **erro interno passa a avisar a equipe** por e-mail.

### Adicionado

- **Demonstração "Suba a sua planilha".** O visitante deixa nome, e-mail e telefone (com a verificação de segurança do site) e escolhe um Excel ou CSV: o painel se monta sozinho, com totais, um gráfico por grupo, a evolução mês a mês quando há coluna de data e a prévia do que foi lido. O arquivo é aberto no próprio navegador e não é enviado. Há também uma planilha de exemplo para quem não tem um arquivo à mão.

- **Três demonstrações novas, que não são painel:** a galeria de sites (escolha
  oficina, banda, clínica ou imobiliária, ponha o nome, a cor e o logo do seu
  negócio e veja o site se vestir na hora; o logo não sai do seu aparelho), o
  agendamento online (serviço, dia e horário livre de um lado, a agenda de quem
  atende do outro, recebendo a marcação) e o relatório automático (o resultado
  do mês escrito sozinho a partir das vendas, com o arquivo em PDF).
- **Demonstrações no site** (`/demonstracoes`): quatro painéis de exemplo para o
  visitante mexer, com dados inventados — campeonato de futebol, comercial de
  uma oficina, funil de uma imobiliária e atendimento com prazos. Filtros que
  mudam números e gráficos na hora, tema claro e escuro, tabela com os mesmos
  números de cada gráfico e o botão "Quero com os meus dados", que leva ao
  contato com o recado já começado. A home ganhou a seção e o menu, o link.
- **O site tem tema claro, além do escuro.** A marca continua nascendo escura
  — "cidade à noite" é identidade, não modo —, mas quem prefere claro troca
  pelo painel de acessibilidade, e a escolha fica guardada no navegador para a
  próxima visita.

- **Página "Como trabalhamos".** O método de trabalho deixou de ser algo que
  só aparecia em conversa e virou argumento de venda no próprio site.

- **Barra de progresso entre as páginas, no lugar de um modal.** Antes a
  navegação abria uma caixa que tomava a tela; agora a espera se anuncia numa
  faixa fina no topo e não interrompe ninguém.

- **Acessibilidade em todas as páginas.** Painel próprio de ajustes, menu com
  animação que respeita "reduzir movimento" do sistema operacional, e todo
  alvo de toque com no mínimo 44 pixels — o tamanho abaixo do qual dedo erra
  botão em tela de celular.

- **Pilha de redes sociais flutuante à direita**, espelhando a barra do rodapé,
  com os ícones nas cores reais de cada rede.

- **O tema do design system aplicado ao site.** Cor literal passou a existir
  em um arquivo só (`partials/tokens.blade.php`) — antes estava espalhada
  pelas views, que é como um tom acaba divergindo entre duas páginas sem
  ninguém perceber.

- **Formulário de contato mais difícil de errar:** máscara no telefone,
  contador de caracteres na mensagem e retorno visível em modal depois do
  envio, no lugar do recarregamento mudo.

- **HTTPS com certificado Let's Encrypt**, com redirecionamento de HTTP para
  HTTPS no Nginx e renovação automática pelo certbot.

- **Ambiente local por HTTP** (`nginx.local.conf`), para rodar o site na
  máquina sem depender de certificado.

### Corrigido
- **E-mails no layout do sistema.** O contato e os alertas ao operador saíam em
  texto cru; agora usam o layout das notificações da casa (`EmailFormatado`).
- **O mesmo recado chegava duas vezes, e o deploy derrubava o site.** Dois
  problemas que apareciam juntos em toda publicação: o contato disparava o
  e-mail em duplicata, e a republicação deixava o site fora do ar por alguns
  segundos em vez de trocar o conteúdo de forma limpa.

- **A rolagem suave não funcionava no Chrome de quem liga "reduzir
  movimento".** O navegador desliga a rolagem nativa nesse caso e a página
  ficava dando saltos secos. Passou a ser feita quadro a quadro, mantendo o
  movimento suave sem ignorar a preferência de quem pediu menos animação.

- **A publicação automática falhava em todos os seis runs.** O fluxo apontava
  para uma versão `@v5` das actions de deploy que nunca existiu. As versões
  foram fixadas nas reais — e passaram a ser fixadas por princípio, não por
  conveniência.

- **A publicação apagava arquivos vivos do servidor.** O `rsync --delete`
  levava junto o runtime da VPS; e o modo de arquivamento re-aplicava as
  permissões do checkout sobre `src/bootstrap/cache`, tirando a escrita do
  `www-data` — o tipo de falha que não dá erro na hora e aparece depois.

- **O formulário aceitava contato sem como responder.** Telefone virou
  obrigatório e a mensagem passou a exigir 50 caracteres, o que também cortou
  os envios de uma palavra só.

### Alterado

- **O casco do site virou um layout Blade; cada página estende, não copia.**
  Antes toda página carregava a própria cópia do cabeçalho, do rodapé e dos
  widgets, e a duplicação já tinha cobrado: o painel de acessibilidade existia
  duas vezes, os alvos de toque de 44px tinham sido corrigidos só na página
  nova, e o rodapé de uma página tinha as redes sociais e o da outra não.
