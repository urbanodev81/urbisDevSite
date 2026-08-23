{{--
    "Como trabalhamos" — o método vira argumento de venda.

    O casco saiu daqui em 23/08/2026 para `layouts/site.blade.php`; ficou o
    que é desta página. Ela já tinha as correções de alvo de toque de 44px
    que a home não tinha — foram elas que subiram para o layout, e é por isso
    que a extração fechou a dívida da home sem uma linha de correção nova.
--}}
@extends('layouts.site')

@section('titulo', 'Como trabalhamos — UrbisDev')
@section('descricao', 'O método que usamos em todo projeto: ciclo obrigatório por mudança, testes automatizados, LGPD desde o desenho, acessibilidade de origem e monitoramento com alerta testado.')

@push('estilos')
<style>
        /* Régua própria: o texto desta página é longo, e o título da home
           (4,5rem) empurraria o conteúdo para baixo da dobra. Sobrescreve a
           escala padrão do layout, de propósito. */
        section { padding-block: clamp(64px, 8vw, 104px); scroll-margin-top: 72px; }
        h1 { font-size: clamp(2.3rem, 5vw, 3.6rem); font-weight: 800; }
        h2 { font-size: clamp(1.7rem, 3.2vw, 2.3rem); font-weight: 700; }
        h3 { font-family: var(--font-body); font-weight: 600; font-size: 1.1rem; color: var(--fg); }

        /* ---------- capa ---------- */
        .capa { padding-block: clamp(56px, 8vw, 96px) clamp(40px, 5vw, 64px); border-bottom: 1px solid var(--line); }
        .capa h1 { margin: 20px 0 22px; max-width: 17ch; }
        .capa h1 em { font-style: normal; color: var(--amber-500); }
        .lede { max-width: 62ch; font-size: 1.15rem; }
        .lede strong { color: var(--text-hi); font-weight: 600; }
        .chips { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 32px; }
        .chip { font-size: .85rem; font-weight: 500; padding: 7px 14px; border: 1px solid var(--line); border-radius: 999px; color: var(--text-mid); background: var(--ink-800); }

        /* ---------- cabeçalho de seção ---------- */
        .section-head { max-width: 680px; margin-bottom: clamp(32px, 5vw, 52px); }
        .section-head h2 { margin: 16px 0 12px; }
        .section-head p { color: var(--text-low); }

        /* ---------- grade de cards ---------- */
        .cards { display: grid; gap: 20px; }
        @media (min-width: 700px) { .cards { grid-template-columns: repeat(2, 1fr); } }
        @media (min-width: 1020px) { .cards.tres { grid-template-columns: repeat(3, 1fr); } }
        .card { background: var(--ink-800); border: 1px solid var(--line); border-radius: var(--r-card); padding: 26px 24px; transition: border-color .2s ease-out; }
        .card:hover { border-color: var(--amber-600); }
        .card h3 { margin-bottom: 10px; }
        .card p { font-size: .98rem; line-height: 1.62; }

        /* ---------- ciclo numerado ---------- */
        .ciclo { border-top: 1px solid var(--line); }
        .etapa { display: grid; grid-template-columns: 56px 1fr; gap: 20px; padding: 22px 0; border-bottom: 1px solid var(--ink-700); align-items: start; }
        .etapa-n { font-family: var(--font-mono); font-size: .82rem; font-weight: 500; color: var(--amber-500); padding-top: 4px; font-variant-numeric: tabular-nums; }
        .etapa h3 { margin-bottom: 5px; }
        .etapa p { font-size: .98rem; line-height: 1.62; }
        @media (max-width: 560px) { .etapa { grid-template-columns: 40px 1fr; gap: 14px; } }

        /* ---------- bloco de destaque ---------- */
        .destaque { background: var(--ink-800); border-left: 3px solid var(--amber-500); border-radius: 0 var(--r-btn) var(--r-btn) 0; padding: 28px 30px; }
        .destaque p { color: var(--text-hi); font-size: 1.1rem; line-height: 1.6; max-width: 68ch; }
        .destaque p + p { margin-top: 14px; color: var(--text-mid); font-size: 1rem; }

        /* ---------- perguntas ---------- */
        .perguntas { display: grid; gap: 2px; }
        .pergunta { display: grid; grid-template-columns: 40px 1fr; gap: 16px; padding: 18px 20px; background: var(--ink-800); border: 1px solid var(--line); align-items: baseline; }
        .pergunta:first-child { border-radius: var(--r-card) var(--r-card) 0 0; }
        .pergunta:last-child { border-radius: 0 0 var(--r-card) var(--r-card); }
        .pergunta + .pergunta { border-top: none; }
        .pergunta-n { font-family: var(--font-mono); font-size: .78rem; color: var(--text-low); font-variant-numeric: tabular-nums; }
        .pergunta p { color: var(--text-hi); font-size: 1.02rem; line-height: 1.5; }
        .pergunta span { display: block; margin-top: 5px; font-size: .92rem; color: var(--text-low); line-height: 1.55; }
        @media (max-width: 560px) { .pergunta { grid-template-columns: 30px 1fr; gap: 10px; padding: 16px 14px; } }

        /* ---------- chamada final ---------- */
        .fecho { border-top: 1px solid var(--line); }
        .fecho .container { display: flex; flex-wrap: wrap; gap: 24px; align-items: center; justify-content: space-between; }
        .fecho h2 { max-width: 22ch; }
</style>
@endpush

@section('conteudo')

        {{-- Capa --}}
        <section class="capa" aria-label="Apresentação">
            <div class="container">
                <span class="eyebrow">Urbano Dev · padrão de desenvolvimento</span>
                <h1>Como um sistema nosso é <em>construído</em>.</h1>
                <p class="lede">
                    Software não falha só quando quebra. Falha quando ninguém percebe que
                    quebrou, quando a correção some junto com quem a fez, quando o sistema
                    não abre no celular de quem precisa dele.
                    <strong>Este documento descreve o método que usamos para que essas três
                    coisas não aconteçam</strong> — e é o mesmo método em todos os projetos,
                    do primeiro dia ao sistema em produção.
                </p>
                <div class="chips">
                    <span class="chip">Ciclo obrigatório por mudança</span>
                    <span class="chip">Testes automatizados</span>
                    <span class="chip">LGPD desde o desenho</span>
                    <span class="chip">Acessibilidade de origem</span>
                    <span class="chip">Monitoramento com alerta</span>
                </div>
            </div>
        </section>

        {{-- O que muda para quem contrata --}}
        <section id="compromissos">
            <div class="container">
                <div class="section-head">
                    <span class="eyebrow">O que isso muda para quem contrata</span>
                    <h2>Três compromissos, na ordem em que costumam importar.</h2>
                </div>
                <div class="cards tres">
                    <article class="card">
                        <h3>O defeito aparece antes do seu cliente</h3>
                        <p>
                            Cada mudança passa por testes automáticos que rodam sozinhos, e o
                            sistema no ar é vigiado do lado de fora, de minuto em minuto.
                            Quando algo cai, quem descobre somos nós — não a pessoa que estava
                            tentando usar.
                        </p>
                    </article>
                    <article class="card">
                        <h3>Nada depende de uma pessoa lembrar</h3>
                        <p>
                            As regras de trabalho não são combinado verbal: são verificadas
                            pela própria ferramenta a cada alteração. Quem entra no projeto
                            depois herda a regra funcionando, e não uma tradição oral.
                        </p>
                    </article>
                    <article class="card">
                        <h3>Você consegue auditar o que foi feito</h3>
                        <p>
                            Toda mudança fica registrada com data, motivo e efeito prático, em
                            linguagem de quem usa — não só em linguagem de programador. É o que
                            permite conferir o que você contratou e o que recebeu.
                        </p>
                    </article>
                </div>
            </div>
        </section>

        {{-- O ciclo --}}
        <section id="ciclo">
            <div class="container">
                <div class="section-head">
                    <span class="eyebrow">O ciclo que toda mudança percorre</span>
                    <h2>Sete etapas, sempre nesta ordem.</h2>
                    <p>
                        Da menor correção à funcionalidade nova. Não é sugestão de boas
                        práticas: pular etapa é bloqueado pela ferramenta.
                    </p>
                </div>
                <div class="ciclo">
                    <div class="etapa">
                        <div class="etapa-n">01</div>
                        <div>
                            <h3>Analisar</h3>
                            <p>Entender o que a mudança encosta antes de escrever a primeira linha. A maior parte dos defeitos caros nasce aqui, e não na digitação.</p>
                        </div>
                    </div>
                    <div class="etapa">
                        <div class="etapa-n">02</div>
                        <div>
                            <h3>Construir</h3>
                            <p>Fazer o que foi pedido, sem carona. Funcionalidade que ninguém pediu é custo de manutenção que ninguém aprovou.</p>
                        </div>
                    </div>
                    <div class="etapa">
                        <div class="etapa-n">03</div>
                        <div>
                            <h3>Testar</h3>
                            <p>Teste automático que roda a cada alteração, e teste no navegador de verdade — porque tela quebrada com teste verde é um modo de falha real, não hipótese.</p>
                        </div>
                    </div>
                    <div class="etapa">
                        <div class="etapa-n">04</div>
                        <div>
                            <h3>Conferir a segurança</h3>
                            <p>Quem vê o quê. Em sistema com vários clientes na mesma base, a separação é provada requisição a requisição, não confiada ao desenho.</p>
                        </div>
                    </div>
                    <div class="etapa">
                        <div class="etapa-n">05</div>
                        <div>
                            <h3>Registrar a alteração</h3>
                            <p>Uma mudança por registro, com o motivo escrito. Seis meses depois, o motivo é a única coisa que ninguém consegue reconstituir sozinho.</p>
                        </div>
                    </div>
                    <div class="etapa">
                        <div class="etapa-n">06</div>
                        <div>
                            <h3>Documentar</h3>
                            <p>A documentação acompanha a mudança no mesmo momento. Documentação que fica para depois descreve um sistema que já não existe.</p>
                        </div>
                    </div>
                    <div class="etapa">
                        <div class="etapa-n">07</div>
                        <div>
                            <h3>Deixar o ponto marcado</h3>
                            <p>Ao fim de cada bloco de trabalho fica escrito o que ficou pronto, o que está em andamento e o que falta. É o que permite retomar sem redescobrir.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- O que já vem de fábrica --}}
        <section id="fabrica">
            <div class="container">
                <div class="section-head">
                    <span class="eyebrow">O que já vem de fábrica</span>
                    <h2>Itens que em muitos orçamentos aparecem como opcional.</h2>
                    <p>Aqui fazem parte do sistema desde a primeira tela.</p>
                </div>
                <div class="cards">
                    <article class="card">
                        <h3>Privacidade tratada como requisito</h3>
                        <p>Termos com versão e aceite registrado, consentimento separado por finalidade e revogável, prazo de guarda escrito com descarte automático, e o direito de a pessoa levar os próprios dados embora em formato aberto.</p>
                    </article>
                    <article class="card">
                        <h3>Separação entre clientes, provada</h3>
                        <p>Quando o sistema atende várias organizações, nenhuma requisição roda sem saber a qual delas pertence — e isso é verificado por teste em cada rota, não apenas na camada de banco.</p>
                    </article>
                    <article class="card">
                        <h3>Segredos nascem no servidor</h3>
                        <p>Senha, chave e token de integração são gerados por nós, guardados cifrados e exibidos uma única vez. O sistema nunca aceita, de fora, um valor que vire credencial.</p>
                    </article>
                    <article class="card">
                        <h3>Acessibilidade de origem</h3>
                        <p>Ajuste de tamanho de texto, contraste em dois sentidos, espaçamento de leitura, guia de leitura, pausa de animação — mais tradução em Libras pelo widget oficial do governo brasileiro, sem custo para você.</p>
                    </article>
                    <article class="card">
                        <h3>Tema claro e escuro, sempre</h3>
                        <p>Toda tela nasce nos dois temas, com contraste conferido por verificação automática nos dois. Não é gosto: é o que mantém a tela legível para quem enxerga pouco e para quem trabalha à noite.</p>
                    </article>
                    <article class="card">
                        <h3>Vira aplicativo no celular</h3>
                        <p>O sistema se instala na tela inicial do telefone e do computador, com ícone e nome próprios, sem passar por loja de aplicativos e sem custo de publicação recorrente.</p>
                    </article>
                    <article class="card">
                        <h3>Cópia de segurança cifrada</h3>
                        <p>Backup automático diário, cifrado antes de sair da máquina — porque cópia de segurança carrega os mesmos dados pessoais que o sistema, e merece a mesma proteção.</p>
                    </article>
                    <article class="card">
                        <h3>Trilha de quem alterou o quê</h3>
                        <p>Alterações relevantes ficam registradas com autor, data e valor anterior. Campos sensíveis aparecem mascarados: fica provado que mudaram, sem expor o conteúdo.</p>
                    </article>
                </div>
            </div>
        </section>

        {{-- Monitoramento --}}
        <section id="monitoramento">
            <div class="container">
                <div class="section-head">
                    <span class="eyebrow">Monitoramento</span>
                    <h2>Vigiar não é olhar o site e ver se abre.</h2>
                    <p>Uma distinção que muda o resultado na prática.</p>
                </div>
                <div class="destaque">
                    <p>
                        Um endereço pode responder “tudo certo” com o sistema morto por trás.
                        Por isso a verificação não olha só a resposta: ela confere o conteúdo
                        que voltou.
                    </p>
                    <p>
                        O alerta também é testado de propósito, provocando uma falha real para
                        ver se o aviso chega. Monitor que nunca disparou é indistinguível de
                        monitor quebrado — e as duas situações só se revelam no pior dia.
                    </p>
                </div>
            </div>
        </section>

        {{-- O que você recebe --}}
        <section id="entrega">
            <div class="container">
                <div class="section-head">
                    <span class="eyebrow">O que você recebe além do sistema</span>
                    <h2>O sistema é uma parte da entrega.</h2>
                </div>
                <div class="cards tres">
                    <article class="card">
                        <h3>Registro de mudanças legível</h3>
                        <p>Uma lista do que mudou para quem usa, agrupada por tipo, escrita sem jargão. Serve para comunicar sua própria equipe.</p>
                    </article>
                    <article class="card">
                        <h3>Três ambientes separados</h3>
                        <p>Um para desenvolver, um para você aprovar antes de valer, e o de produção. Nada estreia direto no ambiente onde estão os dados reais.</p>
                    </article>
                    <article class="card">
                        <h3>Código e documentação versionados</h3>
                        <p>Todo o histórico fica versionado e é entregável. Você não fica preso ao fornecedor por falta de acesso ao que já pagou.</p>
                    </article>
                </div>
            </div>
        </section>

        {{-- Perguntas --}}
        <section id="perguntas">
            <div class="container">
                <div class="section-head">
                    <span class="eyebrow">Sete perguntas para fazer a qualquer fornecedor</span>
                    <h2>Inclusive a nós.</h2>
                    <p>
                        São as perguntas cujas respostas separam um sistema que envelhece bem
                        de um que só parece pronto na apresentação.
                    </p>
                </div>
                <div class="perguntas">
                    <div class="pergunta"><div class="pergunta-n">01</div><p>Como vocês descobrem que o sistema caiu?<span>Se a resposta for “o cliente avisa”, o custo da queda é seu.</span></p></div>
                    <div class="pergunta"><div class="pergunta-n">02</div><p>O que acontece se a pessoa que fez isto sair amanhã?<span>A resposta está na documentação, não na confiança.</span></p></div>
                    <div class="pergunta"><div class="pergunta-n">03</div><p>Onde está escrito por que essa decisão foi tomada?<span>O código conta o quê; quase nunca conta o porquê.</span></p></div>
                    <div class="pergunta"><div class="pergunta-n">04</div><p>Quais dados pessoais o sistema guarda, e por quanto tempo?<span>Dado sem prazo é dado guardado para sempre por omissão.</span></p></div>
                    <div class="pergunta"><div class="pergunta-n">05</div><p>Como se prova que um cliente não enxerga o dado do outro?<span>“O sistema foi feito assim” não é prova. Teste é.</span></p></div>
                    <div class="pergunta"><div class="pergunta-n">06</div><p>A tela funciona para quem enxerga pouco?<span>Acessibilidade depois da entrega custa a reescrita da interface.</span></p></div>
                    <div class="pergunta"><div class="pergunta-n">07</div><p>Se eu quiser trocar de fornecedor, o que sai comigo?<span>Código, dados e documentação — ou dependência.</span></p></div>
                </div>
            </div>
        </section>

        {{-- Chamada final --}}
        <section class="fecho">
            <div class="container">
                <h2>Tem um projeto em mente? Vamos conversar.</h2>
                <a class="btn btn-primary" href="{{ url('/') }}#contato">Fale Conosco
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                </a>
            </div>
        </section>
@endsection
