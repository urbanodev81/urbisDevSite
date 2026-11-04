{{--
    Prévia de texto agendado (BlogPreviaController). Reaproveita a página do
    pacote `urbanodev/blog` inteira — o texto aparece exatamente como vai sair —
    e só acrescenta a faixa de aviso e o `noindex`.
--}}
@push('head')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('flutuantes')
    <div role="status" style="position:fixed;inset:auto 0 0 0;z-index:60;padding:.75rem 1rem;background:#7a4a00;color:#fff;font:600 .95rem/1.4 system-ui,sans-serif;text-align:center;box-shadow:0 -2px 8px rgba(0,0,0,.25)">
        Prévia — este texto sai no site em {{ $post->data->format('d/m/Y') }}. Só quem entra no painel vê esta página.
        <a href="{{ route('painel.inicio') }}" style="color:#fff;text-decoration:underline;margin-left:.75rem">Voltar ao painel</a>
    </div>
@endsection

@include('blog::show')
