{{-- O cabeçalho de toda demonstração: volta, título, e o selo que diz que o dado é inventado. `$selo` troca a frase onde o dado é do próprio visitante. --}}
<section class="demo-topo" aria-labelledby="demo-titulo">
    <div class="container">
        <a class="volta" href="{{ url('/demonstracoes') }}">&larr; Todas as demonstrações</a>
        <br>
        <span class="eyebrow">{{ $demo['tipo'] }}</span>
        <h1 id="demo-titulo">{{ $demo['titulo'] }}</h1>
        <p>{{ $introducao }}</p>
        <span class="demo-selo">{{ $selo ?? 'Dados de demonstração, inventados' }}</span>
    </div>
</section>
