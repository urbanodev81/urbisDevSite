<x-mail::message>
{{ $corpo }}

@if ($url)
<x-mail::button :url="$url">
Abrir no painel
</x-mail::button>
@endif

Este é um aviso automático do painel do site {{ config('marca.nome') }}.
</x-mail::message>
