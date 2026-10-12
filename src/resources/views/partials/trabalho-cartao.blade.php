{{--
    Um trabalho do portfólio. `$item` vem de App\Support\Portfolio, já
    conferido. Sem a autorização do cliente não há `nome` nem `url`: o cartão
    mostra só o que o trabalho é.
--}}
<article class="trabalho">
    <div class="trabalho-tela">
        @if ($item['captura'])
            <img src="{{ url('/trabalhos/capturas/'.$item['captura']) }}" alt="Tela de {{ $item['nome'] ?? $item['titulo'] }}" loading="lazy" decoding="async" width="800" height="500">
        @else
            <span aria-hidden="true">{{ $categoria['nome'] }}</span>
        @endif
    </div>
    <div class="trabalho-corpo">
        <span class="trabalho-estado" data-estado="{{ $item['estado'] }}">{{ $item['estado'] === 'no-ar' ? 'No ar' : 'Em construção' }}</span>
        <{{ $nivel ?? 'h3' }} class="trabalho-titulo">{{ $item['nome'] ?? $item['titulo'] }}</{{ $nivel ?? 'h3' }}>
        @if ($item['nome'])
            <p class="trabalho-tipo">{{ $item['titulo'] }}</p>
        @endif
        @if ($item['resumo'])
            <p>{{ $item['resumo'] }}</p>
        @endif
        @if ($item['url'])
            <a class="trabalho-visitar" href="{{ $item['url'] }}" target="_blank" rel="noopener">Visitar o site<span class="sr-only"> {{ $item['nome'] ?? $item['titulo'] }} (abre em outra aba)</span>&nbsp;&nearr;</a>
        @endif
    </div>
</article>
