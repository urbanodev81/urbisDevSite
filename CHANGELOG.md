# Changelog

Todas as mudanças relevantes deste projeto.

O formato segue [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/).
**Estamos pré-v1**: tudo vive em `## [Não lançado]` até o Ricardo dizer que

### Alterado
- **Blog mais legível para o Google e para assistentes de IA** (auditoria SEO/GEO
  de 06/11/2026). Todo texto sai assinado (o do painel sem autor leva
  "Ricardo Urbano", trocável em `BLOG_AUTOR_PADRAO`), é anunciado como artigo no
  cartão do LinkedIn/WhatsApp e mostra a trilha Início › Blog › texto. A aba do
  navegador ganha "— UrbisDev" no blog e nos textos. As páginas de tag, seção e
  mês saem do índice e do sitemap (são só listas de títulos); o sitemap passa a
  dizer a data da última mudança de cada texto.
lançou. Não crie número de versão nem date release por conta própria.

> Este arquivo nasceu em 21/09/2026, reconstruído a partir do histórico —
> o repositório tinha dezessete commits de `feat`/`fix` e nenhum changelog,
> enquanto o `.githooks/commit-msg` daqui (idêntico ao dos irmãos) já exigia
> um no commit que fecha um grupo. Daqui para a frente ele cresce pelo fluxo
> normal: marque `[changelog]` no assunto do commit que FECHA o grupo.

## [Não lançado]

- **Mover a foto no texto e ampliar ao clicar** (06/11/2026). A imagem
  selecionada ganhou Subir e Descer (na barra e no menu do botão direito): passa
  para antes ou depois do parágrafo vizinho, inclusive no celular, onde não há
  arrastar. Com a posição à esquerda ou à direita, o texto continua em volta.
  Nova opção "Ampliar ao clicar", marcada foto por foto: no site, a foto marcada
  abre grande sobre fundo escuro, com a descrição embaixo; fecha com Esc, com
  clique ou no X, e também abre pelo teclado.

- **Galeria de imagens no editor do blog** (05/11/2026). O botão de imagem abre
  um modal com TODA imagem do sistema — as enviadas pelo painel, as capas, as de
  dentro dos textos e as ilustrações dos guias —, com busca, onde cada uma já foi
  usada e "Subir nova" no topo. Escolhida, pede a descrição (vem a última usada),
  o tamanho (pequena, média ou inteira) e a posição (à esquerda ou à direita com
  o texto ao lado, ou no centro). Clique com o botão direito numa imagem do texto
  abre o menu rápido (tamanho, posição, descrição, trocar, remover); a mesma
  barra aparece quando a imagem está selecionada. No site, no celular, a imagem
  sempre fica sozinha na linha.

- **Foto do blog até 8 MB, de verdade** (05/11/2026). O editor prometia 8 MB,
  mas qualquer foto acima de 1 MB morria com "A imagem não subiu": o nginx do
  servidor, o do contêiner e o PHP cortavam antes. Os três agora aceitam 10 MB.

- **Política de privacidade** em `/privacidade` (05/11/2026). Diz, em linguagem
  direta, o que o site guarda (contato, demonstrações, comentários, o "ajudou?"
  e a contagem de leituras), para quê, por quanto tempo (24 meses, com descarte
  automático), com quem os dados passam (Hostinger, Zoho, LinkedIn, WhatsApp e
  as bases públicas das demonstrações) e como pedir para ver, corrigir ou
  apagar. O link fica no rodapé, no sitemap e nas três caixas de aceite. No
  celular, a tabela vira uma ficha por linha.

- **Leitores do blog** (pendência 246, bloco 3b; por enquanto só no dev). No fim
  de cada texto, o leitor responde "Este texto ajudou?" (no "Não", pode contar o
  que faltou) e comenta. Todo comentário passa pela moderação em Painel →
  Comentários antes de aparecer, e a equipe pode responder como "urbisDev". O
  e-mail de quem comenta nunca vai para a página. Quem preferir comenta com o
  LinkedIn e ganha o selo "verificado pelo LinkedIn"; o botão só aparece depois
  que o app do LinkedIn existir. A página do blog ganhou "Mais lidos do mês", e
  toda página do blog tem o botão "Fale com a gente", que abre o WhatsApp
  citando o texto. As leituras são contadas pelo próprio site, sem script de
  terceiro, sem guardar IP e ignorando robôs, e aparecem em Painel → Leitores
  do blog junto com o "ajudou?" e os contatos que vieram de cada texto.
- No painel, o item "Início" do menu deixou de ficar aceso em todas as telas.
- **Blog escrito no painel** (pendência 246, bloco 3a; por enquanto só no dev).
  Em Painel → Blog dá para escrever, agendar e publicar sem deploy. O editor tem
  negrito, itálico, títulos, listas, citação, link, código, imagem e vídeo. A
  imagem é enviada pelo painel, e o site a reduz e tira a localização da foto
  antes de publicar. O vídeo entra colando o link do YouTube ou do Vimeo e vai ao
  ar no player sem cookie. A capa é recortada em 1200×630 para a prévia nas
  redes. Há prévia de rascunho e de agendado. O endereço trava depois que o
  texto vai ao ar, e arquivar tira do site sem apagar. Os `.md` continuam
  valendo como reserva: o `blog:importar` traz os que existem para o painel, e
  com o banco fora o blog segue com os arquivos.

- **Painel em `/painel`, com a caixa de Contatos** (pendência 246, blocos 1 e 2;
  por enquanto só no dev). Login com senha, verificação de segurança e 2º fator
  obrigatório; contas de acesso com perfis Administrador e Redação; trilha de
  alterações. Todo contato do formulário da home e da porta das demonstrações
  agora é **gravado antes do e-mail**: se o e-mail não sai, o contato continua
  guardado e o painel avisa. A caixa mostra quem espera resposta (do mais antigo
  para o mais novo), de onde a pessoa veio (qual demonstração, qual texto do
  blog), a situação (novo, respondido, virou proposta, descartado) e uma nota
  interna; arquivar não apaga. Dá para baixar os dados de uma pessoa que os
  pedir (LGPD), e todo contato é apagado sozinho 24 meses depois de chegar. O
  site público continua de pé com o banco fora: o formulário volta a ir só por
  e-mail.

- **Fontes servidas pelo próprio site** (pendência 248): Bricolage Grotesque,
  Instrument Sans e JetBrains Mono em `public/fonts` (latin e latin-ext, licença
  OFL junto). O bunny.net passou a mandar buscar a Bricolage no
  `cdn.jsdelivr.net`, a CSP barrava e os títulos apareciam na fonte reserva. A
  CSP agora só aceita fonte e estilo de `'self'`.
- "Guias" vira **Blog** (pendência 247): o endereço passa a ser `/blog`, e
  `/guias/...` responde 301 para o mesmo caminho em `/blog`, com a query junto.
  Menu, submenu, rodapé, sitemap e `llms.txt` acompanham. O botão do fim do
  texto leva `?texto=<slug>` ao contato (o `?guia=` antigo segue aceito), e o
  recado começa com "Li o texto … no blog".
- Pacote `urbanodev/blog` v0.3: tags clicáveis com página própria, arquivo por
  ano e mês (`/blog/2026/11`), busca e filtro por seção, tag e mês no topo da
  lista. O resultado da busca sai `noindex` pela seção `robots` do layout.

### Adicionado

- Três textos agendados em `/guias`, um por semana a partir de 03/11: backup na mesma máquina, o deploy que derrubou o site e o captcha que tirou do ar o formulário da Imóveis Jacob. Cada um tem foto de capa (domínio público, guardada no site), hashtags, botões de compartilhar (LinkedIn, WhatsApp, Facebook, X e e-mail) e "Copiar para o LinkedIn". A capa aparece na prévia do link nas redes.
- Calculadora "Quanto a sua planilha custa por mês" em `/demonstracoes/calculadora`: pessoas, horas por semana, salário com encargos e erros viram o custo do mês, do ano e quanto dá para recuperar, com a conta por extenso. Tudo no navegador do visitante; nada é enviado.
- Guias em `/guias`, com o pacote `urbanodev/blog`: textos em Markdown em `src/resources/blog`, publicados pelo github.com, sem banco nem painel. Entram no sitemap e no `llms.txt`, e o link do menu só aparece com o primeiro texto.

### Adicionado
- **Página de erro 500 com a cara do site.** Falha no servidor mostra "Algo
  deu errado do nosso lado", com caminho para o início e o e-mail de contato,
  em vez da tela "Server Error" crua. Não depende do layout, para não quebrar
  junto com ele.
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
- **Instagram e LinkedIn** saíram do rodapé e dos botões flutuantes: os dois
  não levavam a lugar nenhum. Voltam quando houver perfil.

### Corrigido
- **Demos de lançamento assinam "Urbis Dev"** no rodapé, no lugar de "Urbano Dev".
- **Contato não dá mais 500 quando o e-mail não sai.** Com o SMTP recusando
  a senha, a pessoa preenchia tudo e caía numa tela de erro. Agora o recado
  fica guardado no log, o formulário volta com o que ela escreveu e um aviso
  para tentar de novo ou escrever direto para contato@urbisdev.tech.
- **Formulário das demonstrações também não dá mais 500 sem e-mail.** O lead
  fica no log e a demonstração abre mesmo assim: a pessoa fez a parte dela.
- **O site ganha ícone na aba do navegador**, o mesmo selo "UD" do cabeçalho.
  Até aqui `/favicon.ico` não existia e era o único erro no console.
- **Endereço que não existe mostra a página do site, não a tela crua do
  servidor.** Arquivo protegido (`/.env`) e `.php` que não existe
  (`/wp-login.php`) mostravam o "403 Forbidden" ou o "File not found." cru do
  servidor, em branco e sem marca. Agora mostram a mesma página 404 do site que
  as outras rotas já mostravam.

### Segurança
- **A política de conteúdo (CSP) passa a barrar.** Depois de duas semanas só
  medindo, o navegador de quem visita recusa qualquer script que o site não
  tenha marcado como seu. Os scripts escritos dentro da página ganharam essa
  marca, e os cliques do menu e do modal saíram do HTML para o script.
- **O site começa a medir a política de conteúdo (CSP) antes de ligá-la.** O
  navegador de quem visita passa a contar ao site o que a política barraria —
  sem barrar nada ainda — e o site guarda uma lista curta, sem endereço de
  campanha nem dado de visitante. A primeira medição, em todas as páginas, achou
  só os scripts do próprio site escritos dentro da página; nenhum serviço de
  fora faltando.
- **O site deixa de responder direto pela porta do contêiner.** Ele só é
  alcançado pelo endereço oficial, que passa pelo servidor da frente; antes, as
  portas 8100 e 8443 abriam para a internet por fora do firewall.
- **Um robô no formulário de contato não trava mais o formulário de todo
  mundo.** O site via todos os visitantes como se fossem um só, e o limite de
  envios por minuto valia para a internet inteira junta. Agora cada visitante
  tem o seu.

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
