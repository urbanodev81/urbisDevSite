{{--
    Política de privacidade (05/11/2026).

    Nasceu porque o app do LinkedIn pede a URL, mas valia por si: o site já
    guardava nome, e-mail e telefone pelo contato, pelas demonstrações e pelos
    comentários. O texto é o do Ricardo (05/11/2026), com cinco ajustes para não
    prometer o que o site não faz. Cada frase aqui descreve o que o código faz HOJE. Mudou a
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
        .privacidade dt { color: var(--text-hi); font-weight: 600; margin-top: 18px; }
        .privacidade dd { margin: 4px 0 0; color: var(--text-mid); line-height: 1.7; }
        .privacidade dd em { font-style: normal; color: var(--text-low); }
</style>
@endpush

@section('conteudo')
        <section class="privacidade">
            <div class="container">
                <div class="texto">
                    <span class="eyebrow">LGPD · Lei 13.709/2018</span>
                    <h1>Política de Privacidade</h1>
                    <p class="data">Atualizada em 5 de novembro de 2026.</p>

                    <p>
                        Esta política descreve de forma transparente quais dados pessoais são coletados pelo site
                        <strong>urbisdev.tech</strong>, com qual finalidade, por quanto tempo são armazenados, com quem são
                        compartilhados e quais são os direitos dos usuários em conformidade com a Lei Geral de Proteção de
                        Dados (LGPD, Lei nº 13.709/2018).
                    </p>
                    <p>
                        O controlador responsável pelo tratamento dos dados é a <strong>UrbisDev</strong>. Em caso de dúvidas
                        ou solicitações, o canal de contato oficial é: <a href="mailto:{{ $site('contato.email') }}">{{ $site('contato.email') }}</a>.
                    </p>

                    <h2 id="dados">1. Dados coletados e finalidades</h2>
                    <p>A coleta de dados restringe-se ao necessário para a execução das funcionalidades do site e o atendimento aos usuários:</p>
                    <dl>
                        <dt>Formulário de contato</dt>
                        <dd>Coleta nome, e-mail, telefone, mensagem e data do aceite. <em>Finalidade:</em> responder a solicitações e viabilizar o envio de propostas comerciais, quando aplicável.</dd>
                        <dt>Solicitação de demonstração</dt>
                        <dd>Coleta nome, e-mail, telefone e identificação da demonstração solicitada. <em>Finalidade:</em> liberar o acesso à demonstração e estabelecer contato comercial subsequente.</dd>
                        <dt>Comentários no blog</dt>
                        <dd>Coleta nome, e-mail, conteúdo do comentário e data do aceite. <em>Finalidade:</em> moderação de comentários e notificação por e-mail sobre respostas. Apenas o nome é exibido publicamente no site; o e-mail permanece estritamente confidencial.</dd>
                        <dt>Comentários via LinkedIn</dt>
                        <dd>Coleta nome, e-mail e identificador de conta fornecidos mediante autorização prévia do usuário na plataforma LinkedIn. <em>Finalidade:</em> exibição com o selo de verificação. A UrbisDev não realiza publicações automáticas nem acessa dados adicionais da rede social do usuário.</dd>
                        <dt>Feedback ("Este texto ajudou?")</dt>
                        <dd>Coleta resposta binária (sim/não) e, opcionalmente, sugestões de melhoria, sem vincular nome ou e-mail. <em>Finalidade:</em> aprimoramento contínuo dos conteúdos publicados.</dd>
                        <dt>Métricas de leitura do blog</dt>
                        <dd>Contabiliza exclusivamente o número diário de acessos por artigo, sem registro de endereço IP ou identificação individual dos leitores. <em>Finalidade:</em> análise de relevância e tráfego de conteúdo.</dd>
                    </dl>
                    <p>
                        <strong>Base legal (LGPD):</strong> o tratamento fundamenta-se no consentimento do titular (manifestado
                        mediante aceite nos formulários) e, nos casos de propostas comerciais, nos procedimentos preliminares a
                        contrato solicitados pelo próprio titular.
                    </p>

                    <h2 id="nao">2. O que a UrbisDev não faz</h2>
                    <p>Em respeito à privacidade dos usuários, declaramos expressamente que:</p>
                    <ul>
                        <li>Não comercializamos nem alugamos dados pessoais a terceiros.</li>
                        <li>Não utilizamos ferramentas de rastreamento publicitário, pixels de redes sociais ou Google Analytics.</li>
                        <li>Não armazenamos endereços IP vinculados aos envios de formulários e não criamos perfis comportamentais de navegação.</li>
                        <li>Não realizamos o envio de e-mails em massa, newsletters ou campanhas promocionais para contatos obtidos exclusivamente por meio de mensagens ou comentários.</li>
                    </ul>

                    <h2 id="cookies">3. Cookies e armazenamento local</h2>
                    <p>O site não utiliza cookies de rastreamento de terceiros, dispensando a exibição de banners de consentimento de cookies. Os únicos recursos de armazenamento utilizados são:</p>
                    <ul>
                        <li><strong>Cookies técnicos de sessão:</strong> utilizados estritamente para o funcionamento dos formulários e para protegê-los contra envios forjados a partir de outros sites, com validade de 2 horas de inatividade.</li>
                        <li><strong>Preferência de tema (claro/escuro):</strong> armazenada localmente no navegador do usuário (<code>localStorage</code>), sem transmissão de dados para os servidores da UrbisDev.</li>
                    </ul>

                    <h2 id="terceiros">4. Compartilhamento de dados com terceiros</h2>
                    <p>Os dados podem ser processados por prestadores de serviços essenciais à infraestrutura do site:</p>
                    <ul>
                        <li><strong>Hostinger:</strong> hospedagem do servidor web e do banco de dados.</li>
                        <li><strong>Zoho:</strong> serviço de correio eletrônico para recepção e envio de comunicações de suporte e comentários.</li>
                        <li><strong>LinkedIn:</strong> aplicável apenas caso o usuário opte por autenticar o comentário por meio da plataforma.</li>
                        <li><strong>WhatsApp:</strong> acionado voluntariamente pelo usuário através de link externo de atendimento.</li>
                        <li><strong>Demonstrações técnicas:</strong> as demonstrações de CEP e CNPJ consultam bases públicas (BrasilAPI e ViaCEP) apenas com os dados informados pelo usuário. A demonstração "Pergunte aos dados" encaminha consultas a um modelo de inteligência artificial utilizando exclusivamente tabelas fictícias (recomenda-se não inserir dados pessoais nestes campos).</li>
                    </ul>
                    <p>
                        <strong>Nota técnica:</strong> os mecanismos de proteção contra robôs nos formulários (captcha) são
                        executados integralmente no servidor próprio da UrbisDev, sem envio de dados a serviços externos como o
                        Google reCAPTCHA.
                    </p>

                    <h2 id="retencao">5. Retenção e armazenamento de dados</h2>
                    <p>Os dados pessoais são mantidos pelo período necessário para cumprir as finalidades descritas:</p>
                    <ul>
                        <li><strong>Contatos e demonstrações:</strong> 24 meses, após os quais são eliminados automaticamente.</li>
                        <li><strong>Comentários:</strong> mantidos enquanto o respectivo artigo estiver publicado. Comentários rejeitados ou arquivados são eliminados automaticamente depois de 24 meses.</li>
                        <li><strong>Respostas de feedback:</strong> 24 meses.</li>
                        <li><strong>Cópias de segurança (backups):</strong> o banco de dados passa por rotinas diárias de backup. Os dados excluídos são removidos definitivamente das cópias conforme o ciclo de renovação dos arquivos de segurança.</li>
                    </ul>

                    <h2 id="direitos">6. Direitos do titular dos dados</h2>
                    <p>Nos termos da LGPD, o titular pode, a qualquer momento, exercer os seguintes direitos mediante solicitação ao e-mail <a href="mailto:{{ $site('contato.email') }}">{{ $site('contato.email') }}</a>:</p>
                    <ul>
                        <li>Confirmação da existência de tratamento de dados.</li>
                        <li>Acesso aos dados pessoais armazenados.</li>
                        <li>Correção de dados incompletos, inexatos ou desatualizados.</li>
                        <li>Portabilidade dos dados a outro fornecedor de serviço ou produto.</li>
                        <li>Eliminação dos dados pessoais tratados com consentimento ou revogação do mesmo (o que implica a remoção de comentários associados).</li>
                    </ul>
                    <p>
                        As solicitações serão respondidas no prazo máximo de 15 dias. Persistindo dúvidas ou discordâncias, o
                        titular possui o direito de peticionar perante a Autoridade Nacional de Proteção de Dados (ANPD) através
                        do portal oficial <a href="https://www.gov.br/anpd">gov.br/anpd</a>.
                    </p>

                    <h2 id="seguranca">7. Segurança da informação</h2>
                    <p>
                        O site opera exclusivamente sob protocolo criptografado (HTTPS). O painel administrativo de gestão de
                        contatos exige autenticação por senha e verificação em duas etapas (2FA), com registro de logs de acesso
                        e alteração.
                    </p>

                    <h2 id="alteracoes">8. Alterações nesta política</h2>
                    <p>
                        Esta Política de Privacidade poderá ser atualizada periodicamente para refletir melhorias operacionais ou
                        adequações normativas. A versão vigente estará sempre disponível nesta página, com a data de atualização
                        revisada no topo do documento.
                    </p>
                </div>
            </div>
        </section>
@endsection
