@extends('layouts.site')

@section('titulo', 'Agendamento online — demonstração UrbisDev')
@section('descricao', 'Página de exemplo: o cliente escolhe o serviço, o dia e o horário livre, e a agenda da oficina se atualiza na hora. Agenda inventada.')

@push('estilos')
@include('partials.demo-estilos')
<style>
    .ag-grade { display: grid; gap: 20px; align-items: start; }
    @media (min-width: 980px) { .ag-grade { grid-template-columns: minmax(0, 1.15fr) minmax(0, .85fr); } }
    .ag-passo { margin-top: 22px; }
    .ag-passo:first-of-type { margin-top: 0; }
    .ag-passo > .rotulo { display: block; margin-bottom: 8px; font-size: .82rem; font-weight: 600; color: var(--fg-subtle); }
    .ag-opcoes { display: grid; gap: 8px; }
    @media (min-width: 560px) { .ag-opcoes { grid-template-columns: repeat(2, 1fr); } }
    .ag-opcao, .ag-dia, .ag-hora { min-height: 44px; background: var(--bg); color: var(--fg); border: 1px solid var(--border-input); border-radius: var(--r-btn); font: inherit; cursor: pointer; }
    .ag-opcao { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 10px 14px; text-align: left; font-size: .95rem; }
    .ag-opcao small { flex: none; font-size: .8rem; color: var(--fg-muted); }
    .ag-dias { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
    @media (min-width: 560px) { .ag-dias { grid-template-columns: repeat(6, 1fr); } }
    .ag-dia { display: grid; justify-items: center; gap: 2px; padding: 8px 4px; }
    .ag-dia b { font-family: var(--font-display); font-size: 1.2rem; line-height: 1.1; }
    .ag-dia span, .ag-dia small { font-size: .74rem; color: var(--fg-muted); }
    .ag-horas { display: grid; grid-template-columns: repeat(auto-fill, minmax(84px, 1fr)); gap: 8px; }
    .ag-hora { font-size: .95rem; font-variant-numeric: tabular-nums; }
    .ag-hora:disabled { cursor: not-allowed; color: var(--fg-subtle); border-style: dashed; text-decoration: line-through; }
    .ag-opcao[aria-pressed="true"], .ag-dia[aria-pressed="true"], .ag-hora[aria-pressed="true"] { background: var(--accent); border-color: var(--accent); color: var(--fg-on-accent); font-weight: 600; }
    .ag-opcao[aria-pressed="true"] small, .ag-dia[aria-pressed="true"] span, .ag-dia[aria-pressed="true"] small { color: var(--fg-on-accent); }
    .ag-campos { display: grid; gap: 12px; }
    @media (min-width: 560px) { .ag-campos { grid-template-columns: repeat(2, 1fr); } }
    .ag-campos label { display: grid; gap: 4px; font-size: .82rem; font-weight: 600; color: var(--fg-subtle); }
    .ag-endereco { align-self: end; min-height: 44px; display: flex; align-items: center; font-size: .88rem; color: var(--fg-muted); }
    .ag-dia:disabled { cursor: not-allowed; border-style: dashed; }
    .ag-dia:disabled b { color: var(--fg-subtle); text-decoration: line-through; }
    .ag-campos input { min-height: 44px; padding: 0 12px; background: var(--bg); color: var(--fg); border: 1px solid var(--border-input); border-radius: var(--r-btn); font: inherit; font-size: 1rem; font-weight: 400; }
    .ag-fim { display: flex; flex-wrap: wrap; align-items: center; gap: 12px 16px; margin-top: 20px; }
    .ag-fim p { font-size: .88rem; }
    .ag-confirmado { margin-top: 20px; padding: 16px 18px; border: 1px solid var(--ok); border-radius: var(--r-card); background: color-mix(in srgb, var(--ok) 10%, var(--surface)); }
    .ag-confirmado[hidden] { display: none; }
    .ag-confirmado strong { display: block; color: var(--fg); font-size: 1.05rem; }
    .ag-confirmado .acoes { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 12px; }

    .ag-agenda { list-style: none; display: grid; gap: 6px; }
    .ag-agenda li { display: grid; grid-template-columns: 56px 1fr; align-items: center; gap: 10px; min-height: 40px; padding: 6px 10px; border: 1px solid var(--border); border-radius: 8px; font-size: .9rem; }
    .ag-agenda time { font-variant-numeric: tabular-nums; color: var(--fg-subtle); }
    .ag-agenda .livre { color: var(--fg-subtle); }
    .ag-agenda li.ocupado { background: var(--bg); color: var(--fg); }
    .ag-agenda li.novo { border-color: var(--accent); background: color-mix(in srgb, var(--accent) 14%, var(--surface)); color: var(--fg); font-weight: 600; }
    .ag-agenda small { display: block; font-weight: 400; color: var(--fg-muted); }
    .ag-resumo { margin-bottom: 12px; font-size: .88rem; color: var(--fg-muted); }
</style>
@endpush

@section('conteudo')
    @include('partials.demo-topo', ['introducao' => 'De um lado, o que o cliente vê no seu site: serviço, dia e horário livre. Do outro, a agenda de quem atende, que recebe a marcação na hora. Marque um horário e veja.'])

    <div class="container demo-corpo">
        <div class="ag-grade">
            <section class="demo-cartao" aria-labelledby="ag-cliente-h">
                <h2 id="ag-cliente-h">O que o cliente vê</h2>
                <p class="sub">Só aparece horário que cabe o serviço escolhido.</p>

                <div class="ag-passo"><span class="rotulo" id="ag-servico-r">1. Serviço</span><div class="ag-opcoes" id="ag-servicos" role="group" aria-labelledby="ag-servico-r"></div></div>
                <div class="ag-passo"><span class="rotulo" id="ag-dia-r">2. Dia</span><div class="ag-dias" id="ag-dias" role="group" aria-labelledby="ag-dia-r"></div></div>
                <div class="ag-passo"><span class="rotulo" id="ag-hora-r">3. Horário</span><div class="ag-horas" id="ag-horas" role="group" aria-labelledby="ag-hora-r"></div></div>
                <div class="ag-passo">
                    <span class="rotulo">4. Seus dados</span>
                    <div class="ag-campos">
                        <label>Nome<input type="text" id="ag-nome" autocomplete="off" maxlength="40" placeholder="Como chamamos você"></label>
                        <label>Carro<input type="text" id="ag-carro" autocomplete="off" maxlength="40" placeholder="Modelo e ano"></label>
                        <label class="ag-cep">CEP para o leva e traz (opcional)<input type="text" id="ag-cep" inputmode="numeric" autocomplete="off" maxlength="9" placeholder="00000-000" aria-describedby="ag-endereco"></label>
                        <p class="ag-endereco" id="ag-endereco" aria-live="polite"></p>
                    </div>
                </div>

                <div class="ag-fim">
                    <button type="button" class="btn btn-primary" id="ag-marcar" disabled>Escolha um horário</button>
                    <p>Demonstração: nada é guardado. O CEP só é consultado para achar o endereço.</p>
                </div>

                <div class="ag-confirmado" id="ag-confirmado" role="status" hidden>
                    <strong id="ag-confirmado-t"></strong>
                    <p id="ag-confirmado-d"></p>
                    <div class="acoes">
                        <a class="btn btn-ghost" id="ag-ics" download="agendamento.ics" href="#">Pôr no meu calendário</a>
                        <button type="button" class="btn btn-ghost" id="ag-outro">Marcar outro</button>
                    </div>
                </div>
            </section>

            <section class="demo-cartao" aria-labelledby="ag-oficina-h">
                <h2 id="ag-oficina-h">A agenda de quem atende</h2>
                <p class="sub" id="ag-oficina-sub"></p>
                <p class="ag-resumo" id="ag-resumo" aria-live="polite"></p>
                <ol class="ag-agenda" id="ag-agenda"></ol>
            </section>
        </div>

        @include('partials.demo-chamada', ['chamada' => 'Montamos a marcação online no seu site, com os seus serviços, os seus horários e o aviso para quem atende.'])
    </div>
@endsection

@push('scripts')
<script src="/demos/graficos.js?v={{ filemtime(public_path('demos/graficos.js')) }}"></script>
<script src="/demos/agendamento.js?v={{ filemtime(public_path('demos/agendamento.js')) }}"></script>
@endpush
