<?php

/*
 * As demonstrações do site — fonte única.
 *
 * Cada uma é uma página em `resources/views/demonstracoes/<slug>.blade.php`,
 * com dado INVENTADO e gerado no navegador do visitante (semente fixa: todo
 * mundo vê os mesmos números). Nada aqui tem banco nem login.
 *
 * `pronta => false` aparece na lista como "em construção", sem link.
 * `assunto` é a frase que o botão "quero com os meus dados" deixa pronta no
 * formulário de contato: sai DAQUI, pelo slug, nunca do endereço.
 * `pede_contato => true` põe um formulário de lead (com captcha) na frente:
 * decisão do Ricardo de 11/10/2026 para a planilha e para a IA.
 */

return [

    'futebol' => [
        'titulo' => 'Campeonato de futebol',
        'tipo' => 'Painel de dados',
        'resumo' => 'Tabela que se reordena rodada a rodada, artilharia, comparador de times e mapa de finalizações.',
        'assunto' => 'o painel do campeonato de futebol',
        'pronta' => true,
    ],

    'comercial' => [
        'titulo' => 'Comercial de uma oficina',
        'tipo' => 'Painel de dados',
        'resumo' => 'Faturamento contra a meta, ticket médio, serviços que mais vendem e os dias mais fortes.',
        'assunto' => 'o painel comercial',
        'pronta' => true,
    ],

    'imobiliaria' => [
        'titulo' => 'Funil de uma imobiliária',
        'tipo' => 'Painel de dados',
        'resumo' => 'De onde vêm os leads, quanto tempo esperam pela resposta e quais anúncios são vistos e não convertem.',
        'assunto' => 'o painel de funil da imobiliária',
        'pronta' => true,
    ],

    'atendimento' => [
        'titulo' => 'Atendimento e prazos',
        'tipo' => 'Painel de dados',
        'resumo' => 'Chamados por fila, quanto fica dentro do prazo combinado e em que hora do dia a fila aperta.',
        'assunto' => 'o painel de atendimento e prazos',
        'pronta' => true,
    ],

    'sites' => [
        'titulo' => 'Galeria de sites',
        'tipo' => 'Página de exemplo',
        'resumo' => 'Oficina, banda, clínica ou imobiliária: ponha o seu nome, a sua cor e o seu logo e veja o site se vestir na hora.',
        'assunto' => 'a galeria de sites',
        'pronta' => true,
    ],

    'agendamento' => [
        'titulo' => 'Agendamento online',
        'tipo' => 'Página de exemplo',
        'resumo' => 'O cliente escolhe serviço, dia e horário livre; a agenda de quem atende recebe a marcação na hora.',
        'assunto' => 'o agendamento online',
        'pronta' => true,
    ],

    'relatorio' => [
        'titulo' => 'Relatório automático',
        'tipo' => 'Relatório',
        'resumo' => 'O resultado do mês escrito sozinho a partir das vendas, com gráfico, tabelas e o arquivo em PDF.',
        'assunto' => 'o relatório automático',
        'pronta' => true,
    ],

    /*
     * Não é painel nem ferramenta: são cinco páginas prontas, cópia estática
     * das fichas de lançamento da base dos sites de imobiliária, em
     * `public/demos/lancamento/` (como refazer:
     * `resources/demos-fonte/lancamento/LEIA.md`).
     */
    'lancamento' => [
        'titulo' => 'Ficha de lançamento',
        'tipo' => 'Página de exemplo',
        'resumo' => 'O mesmo empreendimento em cinco desenhos de página, da galeria simples ao livro de vendas com capítulos.',
        'assunto' => 'a ficha de lançamento imobiliário',
        'pronta' => true,
    ],

    /*
     * `pede_contato => true`: a página só entrega a ferramenta depois do
     * formulário com captcha (`DemoLeadController`), que vira lead. O arquivo
     * do visitante é lido no navegador e nunca chega ao servidor.
     */
    'planilha' => [
        'titulo' => 'Suba a sua planilha',
        'tipo' => 'Ferramenta',
        'resumo' => 'Escolha um Excel ou CSV seu e veja o painel se montar sozinho. O arquivo é lido no seu aparelho e não é enviado.',
        'assunto' => 'o painel montado a partir da minha planilha',
        'pronta' => true,
        'pede_contato' => true,
    ],

    /*
     * IA de verdade, com teto por pessoa e por dia. A IA, o lead e a contagem
     * moram no Sócrates (`App\Support\PergunteAosDados`); sem `DEMO_IA_URL` e
     * `DEMO_IA_TOKEN` no `.env` ela não existe: some da lista e responde 404.
     */
    'pergunte' => [
        'titulo' => 'Pergunte aos dados',
        'tipo' => 'Ferramenta com IA',
        'resumo' => 'Escreva a pergunta em português e a IA responde lendo a tabela de vendas: qual o melhor mês, quem vendeu mais, o que caiu.',
        'assunto' => 'perguntar aos dados com IA',
        'pronta' => filled(env('DEMO_IA_URL')) && filled(env('DEMO_IA_TOKEN')),
        'pede_contato' => true,
        'oculta_se_nao_pronta' => true,
    ],

];
