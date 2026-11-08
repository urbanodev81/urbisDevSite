---
titulo: Avaliamos sete ferramentas de IA para programar. Ficamos com uma, e a IA errou no caminho
descricao: Como decidimos o que entra no nosso fluxo de desenvolvimento, por que descartamos quatro ferramentas da moda e o falso alarme que provou a regra.
data: 2026-11-05
autor: Ricardo Urbano
secao: Experiência da Equipe
acao_texto: Conversar sobre o meu sistema
tags: Claude Code, IA, segurança, desenvolvimento de software, Laravel, multi-tenant
---

*Parte 2 de 2 da série Experiência da Equipe. A parte 1 é [Onde a IA entra no meu trabalho de dev, e onde ela não entra](/blog/onde-a-ia-entra-e-onde-nao-entra).*

Toda semana aparece uma ferramenta nova prometendo que a IA vai resolver a sua programação. Para separar o que funciona de verdade do que é só marketing, pegamos sete das mais comentadas e colocamos na mesa uma única pergunta: **ela resolve um problema real do nosso código?** No fim, ficamos com apenas uma para testes. E o mais interessante é que, no meio do caminho, a própria IA deu um alarme falso que acabou sendo a lição mais útil do processo.

## O problema real, antes da ferramenta

Antes de sair testando qualquer coisa, fomos olhar para dentro. Vários dos nossos sistemas atendem múltiplos clientes compartilhando a mesma base de dados: cada clínica, sindicato ou imobiliária só pode enxergar o que é dela. Esse isolamento é o ponto mais crítico e sensível de tudo o que fazemos.

Nossa varredura mostrou que as telas e a API já estavam bem protegidas e cobertas por testes. O verdadeiro risco morava em outro lugar: nas tarefas que rodam sozinhas de madrugada, nos comandos de manutenção e naqueles trechos em que o código desliga o filtro de cliente de propósito e precisa tratar isso manualmente. É exatamente ali que um pequeno descuido pode vazar dados de um cliente para o outro.

Com esse diagnóstico em mãos, a barra de exigência para qualquer ferramenta subiu bastante.

## As quatro que descartamos

**Um gerador de grafo de código:** É bonito visualmente, mas a navegação que ele entrega nós já conseguíamos com o que usamos no dia a dia. Para ler o banco, ele exigia credenciais diretas do Postgres — o que vai contra a nossa regra de ouro, já que o filtro de cliente deve viver na aplicação, e não no SQL. Para piorar, ele ainda sobrescrevia o arquivo de instruções do projeto ao ser instalado.

**Um plugin para escrever menos código:** Código inchado nunca foi um problema por aqui. O nosso desafio é justamente o oposto: a busca pelo "mínimo que funciona" costuma cortar a checagem que parece redundante, mas que é justamente a barreira que garante o isolamento entre os clientes.

**Um pacote com dezenas de habilidades genéricas (arquitetura, testes, DevOps):** Nada que o nosso próprio documento de padrões internos não explique de forma muito mais clara e com exemplos reais. Além disso, cada habilidade instalada consome espaço na memória de trabalho da IA a cada sessão, custando caro em atenção e tokens.

**Um fluxo de desenvolvimento em fases, baseado em agentes próprios:** Nós já temos um método validado, escrito e seguido à risca em todos os repositórios. Colocar um segundo método competindo com ele é receita garantida para confusão.

Nenhuma dessas ferramentas é ruim em si. Elas simplesmente não resolvem o problema específico que temos.

## A que ficou, em teste

A escolhida foi um revisor de segurança que analisa cada alteração enquanto a IA trabalha e faz uma nova checagem no momento do commit, rastreando o caminho do dado entre os arquivos.

O que pesou na decisão foi um detalhe prático: ela consegue ler um arquivo do nosso próprio projeto com as **nossas** regras. Com isso, conseguimos ensinar à ferramenta que, se o cliente não estiver explicitamente definido, a consulta vai enxergar tudo — o que aciona o alerta.

Colocamos a solução em piloto por uma semana em apenas um sistema. Como ela faz uma chamada extra ao modelo a cada resposta e a cada commit, queremos medir o custo real antes de levar para os outros sistemas.

## O alarme falso

Durante a varredura, a IA cravou com total convicção que havíamos deixado passar uma falha gravíssima: segundo ela, um administrador conseguiria alterar o papel de um usuário pertencente a outro cliente. Ela indicou o arquivo exato, a linha e nos deu uma explicação detalhada. Parecia 100% real.

Antes de sair correndo para alterar o código, fomos conferir. A rota em questão ficava no painel central, uma área restrita que apenas a nossa própria equipe acessa, protegida por uma barreira de segurança dedicada e por testes automatizados que provam que usuários comuns não chegam lá. O código "suspeito" estava absolutamente correto e fazia exatamente o que devia.

A falha nunca existiu, e por pouco não gastamos tempo corrigindo algo que estava certo. É por isso que a regra que mencionamos na parte anterior desta série não é enfeite: **a IA aponta, mas quem confere é a gente.** A mesma inteligência artificial que encontrou um problema real naquela mesma tarde inventou um erro fantasioso com a mesma segurança na voz. No fim das contas, quem decide é quem lê o código.

Esse alarme falso nos deixou uma excelente lição prática: o arquivo de regras do revisor agora traz explicitamente que o painel central é uma exceção legítima. Sem esse ajuste, a ferramenta continuaria errando da mesma forma.

## O que levar daqui

1. **Comece pelo seu código, não pela ferramenta:** Descubra onde mora o seu risco real antes de abrir a página de vendas de qualquer produto.
2. **Toda ferramenta tem custo:** Pese o preço em tokens, dependências, dados enviados para fora e eventuais atritos com a forma como o seu time já trabalha.
3. **Teste pequeno e meça:** Escolha um único sistema, rode por uma semana e olhe para os números no final.
4. **Sempre confira o que a IA afirma:** Especialmente naquelas situações em que ela parece ter certeza absoluta.

E vale reforçar a regra que rege tudo o que fazemos: o nosso processo precisa continuar funcionando perfeitamente caso a IA deixe de existir amanhã. É por isso que as nossas diretrizes de segurança estão documentadas para qualquer pessoa ler, e não presas apenas a arquivos que a ferramenta consome.
