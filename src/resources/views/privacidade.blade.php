{{--
    Política de privacidade (05/11/2026).

    Nasceu porque o app do LinkedIn pede a URL, mas valia por si: o site já
    guardava nome, e-mail e telefone pelo contato, pelas demonstrações e pelos
    comentários. Cada frase aqui descreve o que o código faz HOJE. Mudou a
    coleta (campo novo, serviço de fora novo, prazo novo)? Mude esta página no
    mesmo commit e a data de "Atualizada em".
--}}
@extends('layouts.site')

@section('titulo', 'Política de privacidade — UrbisDev')
@section('descricao', 'Que dados o site da UrbisDev guarda, para quê, por quanto tempo, com quem eles passam e como pedir para ver, corrigir ou apagar os seus.')

@push('estilos')
<style>
        .privacidade { padding-block: clamp(56px, 8vw, 96px); }
        .privacidade .texto { max-width: 70ch; }
        .privacidade h1 { font-size: clamp(2.2rem, 5vw, 3.2rem); font-weight: 800; margin: 18px 0 14px; }
        .privacidade h2 { font-size: clamp(1.35rem, 2.6vw, 1.7rem); font-weight: 700; margin: 48px 0 14px; scroll-margin-top: 72px; }
        .privacidade p, .privacidade li { color: var(--text-mid); line-height: 1.7; }
        .privacidade p + p { margin-top: 12px; }
        .privacidade ul { padding-left: 1.2em; margin: 12px 0; }
        .privacidade li + li { margin-top: 8px; }
        .privacidade strong { color: var(--text-hi); font-weight: 600; }
        .privacidade a { color: var(--amber-500); text-underline-offset: 3px; }
        .privacidade .data { color: var(--text-low); font-size: .95rem; }
        .privacidade table { width: 100%; border-collapse: collapse; margin: 14px 0; font-size: .95rem; }
        .privacidade th, .privacidade td { text-align: left; vertical-align: top; padding: 10px 12px; border-bottom: 1px solid var(--line); color: var(--text-mid); }
        .privacidade th { color: var(--text-hi); font-weight: 600; }
        /* No celular a tabela vira uma ficha por linha: três colunas não cabem em 390 px. */
        @media (max-width: 640px) {
            .privacidade thead { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); }
            .privacidade table, .privacidade tbody, .privacidade tr, .privacidade td { display: block; }
            .privacidade tr { padding: 12px 0; border-bottom: 1px solid var(--line); }
            .privacidade td { border: 0; padding: 4px 0; }
            .privacidade td:first-child { color: var(--text-hi); font-weight: 600; }
            .privacidade td:nth-child(2)::before { content: "Fica: "; color: var(--text-low); }
            .privacidade td:nth-child(3)::before { content: "Para quê: "; color: var(--text-low); }
        }
</style>
@endpush

@section('conteudo')
        <section class="privacidade">
            <div class="container">
                <div class="texto">
                    <span class="eyebrow">LGPD · Lei 13.709/2018</span>
                    <h1>Política de privacidade</h1>
                    <p class="data">Atualizada em 5 de novembro de 2026.</p>

                    <p>
                        Esta página explica, em linguagem direta, que dados o site
                        <strong>urbisdev.tech</strong> guarda, para quê, por quanto tempo, com quem
                        eles passam e como você pede para ver, corrigir ou apagar os seus. Quem
                        responde por eles é a <strong>UrbisDev</strong>. Qualquer pedido ou dúvida:
                        <a href="mailto:contato@urbisdev.tech">contato@urbisdev.tech</a>.
                    </p>

                    <h2 id="o-que">O que guardamos e para quê</h2>
                    <div class="tabela">
                        <table>
                            <thead>
                                <tr><th scope="col">Quando</th><th scope="col">O que fica</th><th scope="col">Para quê</th></tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Você usa o formulário de contato</td>
                                    <td>Nome, e-mail, telefone, a mensagem e a data do aceite</td>
                                    <td>Responder ao seu pedido e mandar a proposta, se for o caso</td>
                                </tr>
                                <tr>
                                    <td>Você abre uma demonstração que pede contato</td>
                                    <td>Nome, e-mail, telefone e qual demonstração foi aberta</td>
                                    <td>Liberar a demonstração e poder conversar com você depois sobre ela</td>
                                </tr>
                                <tr>
                                    <td>Você comenta no blog</td>
                                    <td>Nome, e-mail, o comentário e a data do aceite</td>
                                    <td>Moderar o comentário e avisar por e-mail quando respondermos. <strong>Só o nome aparece no site</strong>; o e-mail, nunca.</td>
                                </tr>
                                <tr>
                                    <td>Você comenta entrando pelo LinkedIn</td>
                                    <td>Nome, e-mail e o identificador da sua conta, que o próprio LinkedIn nos entrega depois que você autoriza</td>
                                    <td>O mesmo do comentário comum, com o selo "verificado pelo LinkedIn". Não publicamos nada no seu LinkedIn e não lemos a sua rede.</td>
                                </tr>
                                <tr>
                                    <td>Você responde "Este texto ajudou?"</td>
                                    <td>Sim ou não e, se quiser, o que faltou. Sem nome, sem e-mail.</td>
                                    <td>Melhorar os textos</td>
                                </tr>
                                <tr>
                                    <td>Você lê um texto do blog</td>
                                    <td>Só um número: quantas leituras aquele texto teve no dia. Sem IP, sem identificar quem leu.</td>
                                    <td>Saber quais textos são úteis e mostrar os mais lidos</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p>
                        O que permite guardar esses dados (a "base legal" da LGPD) é o
                        <strong>seu consentimento</strong>, dado na caixa de aceite de cada formulário,
                        e, no caso de quem pediu proposta, os <strong>procedimentos preliminares de um
                        contrato</strong> que você mesmo pediu.
                    </p>

                    <h2 id="nao">O que não fazemos</h2>
                    <ul>
                        <li>Não vendemos nem alugamos dados.</li>
                        <li>Não usamos Google Analytics, pixel de rede social nem qualquer rastreador de publicidade.</li>
                        <li>Não guardamos o seu IP junto com o que você envia, nem montamos perfil de navegação.</li>
                        <li>Não mandamos newsletter ou propaganda para quem só deixou um contato ou um comentário.</li>
                    </ul>

                    <h2 id="cookies">Cookies e armazenamento no navegador</h2>
                    <ul>
                        <li><strong>Sessão</strong> e <strong>proteção de formulário</strong>: dois cookies técnicos, sem os quais os formulários não funcionam. Valem por 2 horas sem uso.</li>
                        <li><strong>Tema claro ou escuro</strong>: a sua escolha fica guardada no próprio navegador (<code>localStorage</code>), não vem para nós.</li>
                    </ul>
                    <p>Não há cookie de terceiros. Por isso o site não mostra aviso de cookies: não há o que recusar.</p>

                    <h2 id="terceiros">Com quem os dados passam</h2>
                    <ul>
                        <li><strong>Hostinger</strong>: o servidor onde o site e o banco de dados ficam.</li>
                        <li><strong>Zoho</strong>: o serviço de e-mail. É por ele que chega a nós o aviso de cada contato e que sai a resposta para quem comentou.</li>
                        <li><strong>LinkedIn</strong>: só se você escolher comentar entrando por ele. Aí vale também a política do LinkedIn.</li>
                        <li><strong>WhatsApp</strong>: só se você clicar no botão. A conversa acontece no WhatsApp, fora deste site.</li>
                        <li>
                            <strong>Demonstrações</strong>: a de CEP e CNPJ consulta bases públicas (BrasilAPI e ViaCEP) com o número
                            que você digitar. A "Pergunte aos dados" manda a sua pergunta para um modelo de inteligência
                            artificial responder sobre uma tabela fictícia. Não escreva dados pessoais nessas caixas.
                        </li>
                    </ul>
                    <p>
                        A verificação contra robôs dos formulários roda no nosso próprio servidor; ela não
                        manda nada para Google ou para outro serviço.
                    </p>

                    <h2 id="prazo">Por quanto tempo</h2>
                    <ul>
                        <li><strong>Contatos e pedidos de demonstração</strong>: 24 meses. Depois disso são apagados automaticamente.</li>
                        <li><strong>Comentários</strong>: o aprovado fica enquanto estiver publicado. O recusado ou arquivado é apagado depois de 24 meses.</li>
                        <li><strong>Respostas do "Este texto ajudou?"</strong>: 24 meses.</li>
                        <li><strong>Cópias de segurança</strong>: o banco é copiado todo dia, e cada cópia é substituída com o tempo. Um dado apagado some das cópias quando elas vencem.</li>
                    </ul>

                    <h2 id="direitos">Os seus direitos</h2>
                    <p>A qualquer momento você pode pedir para:</p>
                    <ul>
                        <li>saber se temos dados seus e quais são;</li>
                        <li>corrigir o que estiver errado;</li>
                        <li>receber os seus dados num arquivo aberto, para levar para onde quiser;</li>
                        <li>apagar os seus dados ou retirar o consentimento, o que também tira o seu comentário do site.</li>
                    </ul>
                    <p>
                        Escreva para <a href="mailto:contato@urbisdev.tech">contato@urbisdev.tech</a> com o e-mail que
                        você usou no site. Respondemos em até 15 dias. Se achar que não fomos corretos, você
                        também pode reclamar à Autoridade Nacional de Proteção de Dados
                        (<a href="https://www.gov.br/anpd">gov.br/anpd</a>).
                    </p>

                    <h2 id="seguranca">Segurança</h2>
                    <p>
                        O site só funciona por conexão cifrada (HTTPS). O painel onde lemos os contatos exige
                        senha e um segundo fator de verificação, e cada acesso e alteração fica registrado.
                        O mesmo cuidado que descrevemos em <a href="{{ url('/como-trabalhamos') }}">como trabalhamos</a>
                        vale para os nossos próprios dados.
                    </p>

                    <h2 id="mudancas">Mudanças nesta política</h2>
                    <p>
                        Se passarmos a guardar algo novo ou a usar outro serviço, esta página muda antes e a data
                        lá em cima acompanha.
                    </p>
                </div>
            </div>
        </section>
@endsection
