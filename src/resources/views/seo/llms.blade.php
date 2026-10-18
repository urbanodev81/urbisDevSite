# UrbisDev

> Sites com painel próprio e sistemas de gestão para imobiliárias, clínicas, sindicatos, oficinas e artistas, do primeiro esboço ao app no celular. Empresa brasileira, atende todo o Brasil, em português.

Fazemos o código, o servidor e o acompanhamento depois de publicado. Todo projeto passa por testes automatizados, revisão de segurança, acessibilidade desde o desenho e cuidado com a LGPD.

## O que fazemos

- Imobiliárias (no ar): site com busca de imóveis, fichas de lançamento e blog, e o sistema de gestão do acervo e dos contatos dos interessados, com a marca da imobiliária.
- Sites com painel próprio (no ar): para bandas, artistas, oficinas e profissionais; o cliente troca agenda, fotos, vídeos e textos pelo painel, sem depender de ninguém.
- Clínicas de terapia (em construção): gestão da clínica com agenda, evolução de cada paciente e cuidado com o dado de saúde.
- Sindicatos e associações (em construção): cadastro de associados, mensalidades, comunicação com a base e votações.

## Contato

- E-mail: contato@urbisdev.tech
- WhatsApp: +55 11 97686-2551
- Formulário: {{ url('/#contato') }}

## Páginas

@foreach ($paginas as $caminho => $nome)
- [{{ \Illuminate\Support\Str::before($nome, ' — ') }}]({{ url($caminho) }}){{ str_contains($nome, ' — ') ? ': '.\Illuminate\Support\Str::after($nome, ' — ') : '' }}
@endforeach
