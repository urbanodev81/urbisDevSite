{{--
    A 404 do site (18/10/2026). Antes saía a tela padrão do Laravel, "Not
    Found" em branco e sem marca; é também a página que o nginx entrega para
    `/.env` e `.php` perdido (`error_page 403 404 = @laravel`). Leva para os
    três lugares que respondem quase toda visita perdida.
--}}
@extends('layouts.site')

@section('titulo', 'Página não encontrada — UrbisDev')
@section('descricao', 'Este endereço não existe no site da UrbisDev.')
@section('robots', 'noindex, follow')

@push('estilos')
@include('partials.demo-estilos')
<style>
    .nao-achou { padding-block: clamp(88px, 12vw, 150px); }
    .nao-achou .codigo { font-family: var(--font-mono); font-size: .8rem; letter-spacing: .14em; color: var(--fg-subtle); }
    .nao-achou h1 { margin: 16px 0 18px; }
    .nao-achou p { max-width: 52ch; font-size: 1.1rem; }
    .nao-achou .saidas { display: flex; flex-wrap: wrap; gap: 14px; margin-top: 32px; }
</style>
@endpush

@section('conteudo')
    <section class="nao-achou" aria-labelledby="nao-achou-titulo">
        <div class="container">
            <span class="eyebrow">Erro 404</span>
            <h1 id="nao-achou-titulo">Esta página não existe.</h1>
            <p>O endereço pode ter mudado ou ter sido digitado com algum erro. Daqui você chega ao que importa:</p>
            <div class="saidas">
                <a class="btn btn-primary" href="{{ url('/') }}">Ir para o início</a>
                <a class="btn btn-ghost" href="{{ url('/demonstracoes') }}">Ver as demonstrações</a>
                <a class="btn btn-ghost" href="{{ url('/#contato') }}">Falar com a gente</a>
            </div>
        </div>
    </section>
@endsection
