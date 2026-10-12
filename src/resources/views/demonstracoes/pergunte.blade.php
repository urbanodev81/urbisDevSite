{{--
    Pergunte aos dados: IA de verdade lendo a tabela de vendas da oficina de
    demonstração. A IA, o lead e os tetos moram no Sócrates
    (`App\Support\PergunteAosDados`); a tabela desta página é a mesma que vai
    no pedido, lida de `resources/demos/oficina-resumo.json`.
--}}
@extends('layouts.site')

@section('titulo', 'Pergunte aos dados — demonstração UrbisDev')
@section('descricao', 'Escreva a pergunta em português e uma IA responde lendo a tabela de vendas de uma oficina de exemplo. Dados inventados.')

@if (! $liberada)
@push('head')
    <script type="module" src="/vendor/altcha/pt-br-3.2.2.js"></script>
    <script type="module" src="/vendor/altcha/altcha-3.2.2.min.js"></script>
@endpush
@else
@push('head')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush
@endif

@push('estilos')
@include('partials.demo-estilos')
<style>
    @include('partials.formulario-estilos')
    .pg-caixa { display: grid; gap: 14px; }
    .pg-caixa textarea { width: 100%; min-height: 96px; resize: vertical; }
    .pg-linha { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px 18px; }
    .pg-saldo { font-size: .88rem; color: var(--fg-subtle); }
    .pg-sugestoes { display: flex; flex-wrap: wrap; gap: 8px; list-style: none; }
    .pg-sugestoes button { min-height: 44px; padding: 0 14px; background: var(--bg); color: var(--fg-muted); border: 1px solid var(--border-input); border-radius: 999px; font: inherit; font-size: .9rem; cursor: pointer; text-align: left; }
    .pg-sugestoes button:hover { border-color: var(--accent); color: var(--fg); }
    .pg-conversa { display: grid; gap: 14px; margin-top: 6px; }
    .pg-troca { padding: 16px 18px; background: var(--bg); border: 1px solid var(--border); border-radius: var(--r-card); }
    .pg-troca dt { font-weight: 600; color: var(--fg); }
    .pg-troca dd { margin-top: 8px; line-height: 1.65; color: var(--fg-muted); white-space: pre-line; overflow-wrap: anywhere; }
    .pg-troca.aviso { border-color: var(--error); }
    .pg-troca.aviso dd { color: var(--fg); }
    .pg-nota { font-size: .85rem; line-height: 1.55; color: var(--fg-subtle); max-width: 70ch; }
    .pg-tabelas { display: grid; gap: 20px; margin-top: 20px; }
</style>
@endpush

@section('conteudo')
    @include('partials.demo-topo', ['introducao' => 'Em vez de montar filtro e gráfico, escreva o que quer saber. Uma IA lê a tabela de vendas da oficina e responde em português: o melhor mês, quem vendeu mais, o que ficou abaixo da meta.'])

    <div class="container demo-corpo">
        @if (! $liberada)
            @include('partials.demo-porta', [
                'portaTitulo' => 'Antes de abrir, diga quem é você',
                'portaTexto' => 'Aqui a IA é de verdade e cada pergunta tem custo, então pedimos o contato primeiro e há um limite de perguntas por dia. Os dados da demonstração são inventados; os seus não entram nela.',
            ])
        @else
            <section id="ferramenta" class="demo-cartao pg-caixa" aria-labelledby="pg-h">
                <h2 id="pg-h">O que você quer saber sobre as vendas?</h2>

                <ul class="pg-sugestoes" aria-label="Perguntas prontas">
                    <li><button type="button" data-pergunta="Qual foi o melhor mês e quanto ele passou da meta?">Qual foi o melhor mês?</button></li>
                    <li><button type="button" data-pergunta="Em quais meses a oficina ficou abaixo da meta, e por quanto?">Quando ficou abaixo da meta?</button></li>
                    <li><button type="button" data-pergunta="Quem vendeu mais no ano e qual a diferença para o segundo?">Quem vendeu mais?</button></li>
                    <li><button type="button" data-pergunta="Qual serviço tem o maior ticket médio no ano?">Qual serviço tem o maior ticket?</button></li>
                </ul>

                <form id="pg-form" novalidate>
                    <div class="field">
                        <label for="pg-pergunta">Sua pergunta</label>
                        <textarea id="pg-pergunta" name="pergunta" maxlength="280" required aria-describedby="pg-nota"></textarea>
                    </div>
                    <div class="pg-linha">
                        <button type="submit" class="btn btn-primary" id="pg-enviar">Perguntar</button>
                        <span class="pg-saldo" id="pg-saldo" role="status"></span>
                    </div>
                </form>

                <p class="pg-nota" id="pg-nota">A resposta é escrita por uma IA a partir da tabela abaixo e pode errar uma conta: os números estão aí para conferir. A pergunta fica registrada, sem o seu nome junto dela.</p>

                <dl class="pg-conversa" id="pg-conversa" aria-live="polite"></dl>
            </section>

            <div class="pg-tabelas">
                <section class="demo-cartao" aria-labelledby="pg-t1">
                    <h2 id="pg-t1">O que a IA lê: mês a mês</h2>
                    <p class="sub">Oficina Modelo, últimos doze meses. Valores em reais.</p>
                    <div class="demo-tabela-rolagem" tabindex="0" role="region" aria-labelledby="pg-t1">
                        <table class="demo-tabela">
                            <thead><tr><th scope="col">Mês</th><th scope="col">Faturamento</th><th scope="col">Meta</th><th scope="col">Vendas</th></tr></thead>
                            <tbody>
                                @foreach ($dados['mensal'] as $m)
                                    <tr><th scope="row">{{ $m['mes'] }}</th><td>{{ number_format($m['faturamento'], 0, ',', '.') }}</td><td>{{ number_format($m['meta'], 0, ',', '.') }}</td><td>{{ $m['vendas'] }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>

                @foreach (['servicos' => 'por serviço', 'vendedores' => 'por vendedor'] as $chave => $nome)
                    <section class="demo-cartao" aria-labelledby="pg-t-{{ $chave }}">
                        <h2 id="pg-t-{{ $chave }}">Faturamento {{ $nome }}</h2>
                        <p class="sub">Em reais, mês a mês, com o total e o número de vendas do ano.</p>
                        <div class="demo-tabela-rolagem" tabindex="0" role="region" aria-labelledby="pg-t-{{ $chave }}">
                            <table class="demo-tabela">
                                <thead><tr><th scope="col">{{ $chave === 'servicos' ? 'Serviço' : 'Vendedor' }}</th>@foreach ($dados['meses'] as $mes)<th scope="col">{{ $mes }}</th>@endforeach<th scope="col">Total</th><th scope="col">Vendas</th></tr></thead>
                                <tbody>
                                    @foreach ($dados[$chave] as $item)
                                        <tr><th scope="row">{{ $item['nome'] }}</th>@foreach ($item['faturamento'] as $v)<td>{{ number_format($v, 0, ',', '.') }}</td>@endforeach<td>{{ number_format(array_sum($item['faturamento']), 0, ',', '.') }}</td><td>{{ array_sum($item['vendas']) }}</td></tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </section>
                @endforeach
            </div>

            @include('partials.demo-chamada', ['chamada' => 'Ligamos a IA ao seu sistema ou à sua planilha, com as regras de quem pode perguntar o quê, e ela responde sobre o seu negócio de verdade.'])
        @endif
    </div>
@endsection

@if ($liberada)
@push('scripts')
<script src="/demos/pergunte.js?v={{ filemtime(public_path('demos/pergunte.js')) }}"></script>
@endpush
@endif
