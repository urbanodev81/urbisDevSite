---
titulo: Avaliamos sete ferramentas de IA para programar. Ficamos com uma, e a IA errou no caminho
descricao: Como decidimos o que entra no nosso fluxo de desenvolvimento, por que descartamos quatro ferramentas da moda e o falso alarme que provou a regra.
data: 2026-11-05
autor: Ricardo Urbano
secao: Experiência da Equipe
acao_texto: Conversar sobre o meu sistema
tags: Claude Code, IA, segurança, desenvolvimento de software, Laravel, multi-tenant
---

*Parte 2 de 2 da série Experiência da Equipe. A parte 1 é [Onde a IA entra no meu trabalho de dev, e onde ela não entra](/guias/onde-a-ia-entra-e-onde-nao-entra).*

Toda semana aparece uma ferramenta nova prometendo que a IA vai programar melhor. Pegamos sete das mais citadas e fizemos uma pergunta só: **ela resolve um problema que nós temos, de verdade, no nosso código?** Ficamos com uma, em teste. E no meio da avaliação a própria IA deu um alarme falso, que acabou sendo a parte mais útil do exercício.

## O problema real, antes da ferramenta

Antes de avaliar qualquer coisa, olhamos o código. Vários dos nossos sistemas atendem muitos clientes no mesmo banco de dados: cada clínica, sindicato ou imobiliária só pode ver o que é dela. Esse isolamento é o ponto mais sensível de tudo o que fazemos.

A varredura mostrou um padrão que se repetia: as telas e a API estavam bem protegidas e cobertas por teste. O risco morava **fora delas**, nas tarefas que rodam sozinhas de madrugada, nos comandos de manutenção e nos trechos em que o código desliga o filtro de cliente de propósito e precisa filtrar à mão. É ali que um esquecimento vaza dado de um cliente para outro.

Com isso na mão, a pergunta para cada ferramenta ficou concreta.

## As quatro que descartamos

**Um gerador de grafo do código.** Bonito, e a navegação que ele oferece nós já tínhamos com outra ferramenta. Para ler o banco, ele pede credencial do Postgres, e a nossa regra é que nenhuma ferramenta de IA fala direto com o banco: o filtro de cliente existe na aplicação, não no SQL. Ele ainda reescreve o arquivo de instruções do projeto ao ser instalado.

**Um plugin para a IA escrever menos código.** Código inchado não é o nosso problema. O nosso é o contrário: o "mínimo que funciona" tende a cortar justamente a checagem que parece redundante, e é ela que segura o isolamento entre clientes.

**Um pacote com dezenas de habilidades genéricas** (arquitetura, testes, DevOps). Nada que o nosso documento de padrões não diga melhor, com exemplo real. E cada habilidade instalada ocupa espaço na memória de trabalho da IA em toda sessão, o que custa dinheiro e atenção.

**Um fluxo de desenvolvimento em fases, com agentes próprios.** Nós já temos um método, escrito e seguido em todos os repositórios. Um segundo método competindo com o primeiro é receita de confusão.

Nenhuma delas é ruim. Só não resolvem o problema que nós temos.

## A que ficou, em teste

Um revisor de segurança que lê cada alteração enquanto a IA trabalha e de novo no momento do commit, seguindo o caminho do dado entre os arquivos. O que decidiu foi um detalhe: ele lê um arquivo do próprio projeto com as **nossas** regras. Dá para ensinar a ele que, sem o cliente definido, a consulta enxerga todo mundo.

Entrou em piloto, num sistema só, por uma semana. Ele faz uma chamada extra ao modelo a cada resposta e a cada commit, e queremos medir o custo antes de espalhar.

## O alarme falso

Durante a varredura, a IA apontou com convicção uma falha grave: um administrador conseguiria mudar o papel de um usuário de outro cliente. Arquivo, linha, explicação. Parecia real.

Antes de corrigir, fomos conferir. A rota fica no painel central, que só a nossa equipe acessa e que já tem uma guarda própria e um teste provando que usuário de cliente nem chega lá. O código "suspeito" fazia exatamente o que devia.

A falha não existia, e quase gastamos uma correção nela. É por isso que a regra do texto anterior desta seção não é enfeite: **a IA encontra, a gente confere.** A mesma IA que descobriu um problema verdadeiro na mesma tarde inventou um falso com a mesma segurança na voz. Quem decide é quem lê o código.

E o alarme falso ainda deixou uma lição prática: o arquivo de regras do revisor agora diz, com todas as letras, que o painel central é uma exceção legítima. Sem isso, a ferramenta erraria do mesmo jeito.

## O que levar daqui

1. **Comece pelo seu código, não pela ferramenta.** Descubra onde mora o seu risco antes de ler a página de qualquer produto.
2. **Toda ferramenta tem custo**: tokens, dependências, dado enviado para fora, conflito com o jeito que o time já trabalha. Ponha isso na balança.
3. **Teste pequeno e meça.** Um sistema, uma semana, um número no fim.
4. **Confira o que a IA afirma**, principalmente quando ela parece ter certeza.

E uma regra que vale para tudo isso: o que fazemos com IA tem que continuar funcionando se amanhã ela não estiver aqui. Por isso as regras de segurança estão escritas num documento que qualquer pessoa lê, e não só no arquivo que a ferramenta consome.
