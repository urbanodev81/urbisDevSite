{{--
    A porta das demonstrações com `pede_contato`: nome, e-mail, telefone, aceite
    e o nosso captcha, antes de entregar a ferramenta (`DemoLeadController`).
    A página que inclui carrega o Altcha no `head` e o `formulario-estilos`.
--}}
<section class="demo-cartao demo-porta" aria-labelledby="porta-h">
    <h2 id="porta-h">{{ $portaTitulo }}</h2>
    <p>{{ $portaTexto }}</p>

    <form action="{{ url('/demonstracoes/'.$slug.'/liberar') }}" method="POST">
        @csrf
        <div class="field-row">
            <div class="field">
                <label for="porta-nome">Nome *</label>
                <input type="text" id="porta-nome" name="name" value="{{ old('name') }}" required maxlength="255" autocomplete="name">
                @error('name') <span class="field-error">{{ $message }}</span> @enderror
            </div>
            <div class="field">
                <label for="porta-email">E-mail *</label>
                <input type="email" id="porta-email" name="email" value="{{ old('email') }}" required maxlength="255" autocomplete="email">
                @error('email') <span class="field-error">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="field">
            <label for="porta-telefone">Telefone *</label>
            <input type="tel" id="porta-telefone" name="phone" value="{{ old('phone') }}" placeholder="(11) 99999-9999" required maxlength="15" autocomplete="tel">
            @error('phone') <span class="field-error">{{ $message }}</span> @enderror
        </div>
        <div class="field">
            <div class="consent">
                <input type="checkbox" id="porta-consent" name="consent" value="1" required>
                <label for="porta-consent">Autorizo o armazenamento dos meus dados para fins de contato, conforme a LGPD.</label>
            </div>
            @error('consent') <span class="field-error">{{ $message }}</span> @enderror
        </div>
        <div class="field captcha">
            <altcha-widget challenge="/captcha/desafio" name="captcha_token" language="pt-br" auto="onload" hidefooter hidelogo></altcha-widget>
            @error('captcha_token') <span class="field-error">{{ $message }}</span> @enderror
        </div>
        <div>
            <button type="submit" class="btn btn-primary">Abrir a demonstração</button>
        </div>
    </form>
</section>
