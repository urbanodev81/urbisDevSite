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

### Segurança
- **O site passa a mandar os cabeçalhos de segurança** que os produtos da casa
  já mandavam (HTTPS obrigatório, proteção contra enquadramento e contra troca
  de tipo de arquivo), e **erro interno passa a avisar a equipe** por e-mail.

### Adicionado

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
