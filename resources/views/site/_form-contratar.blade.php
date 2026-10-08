@php $tituloDoForm ??= 'Ou mande os detalhes'; @endphp
<div class="form-contratar">

    @if ($tituloDoForm)
        <h3>{{ $tituloDoForm }}</h3>
    @endif

    @if (session('sucesso'))
        <p class="recado" role="status">{{ session('sucesso') }}</p>
    @endif

    <form method="POST" action="{{ route('site.contratar') }}">
        @csrf

        <label class="mel" aria-hidden="true">
            Não preencha
            <input type="text" name="site" tabindex="-1" autocomplete="off">
        </label>

        <input type="hidden" name="aberto_em" value="{{ time() }}">

        <label class="campo">
            <span>Seu nome *</span>
            <input type="text" name="nome" value="{{ old('nome') }}" required maxlength="255" autocomplete="name">
            @error('nome')<span class="erro">{{ $message }}</span>@enderror
        </label>

        <div class="campo--linha">
            <label class="campo">
                <span>WhatsApp</span>
                <input type="tel" name="telefone" value="{{ old('telefone') }}" maxlength="20" autocomplete="tel">
                @error('telefone')<span class="erro">{{ $message }}</span>@enderror
            </label>

            <label class="campo">
                <span>E-mail</span>
                <input type="email" name="email" value="{{ old('email') }}" maxlength="255" autocomplete="email">
                @error('email')<span class="erro">{{ $message }}</span>@enderror
            </label>
        </div>

        <div class="campo--linha">
            <label class="campo">
                <span>Tipo de evento *</span>
                <select name="tipo_evento" required>
                    @foreach ($tiposEvento as $tipo)
                        <option value="{{ $tipo['valor'] }}" @selected(old('tipo_evento') === $tipo['valor'])>{{ $tipo['rotulo'] }}</option>
                    @endforeach
                </select>
            </label>

            <label class="campo">
                <span>Data pretendida</span>
                <input type="date" name="data_pretendida" value="{{ old('data_pretendida') }}">
                @error('data_pretendida')<span class="erro">{{ $message }}</span>@enderror
            </label>
        </div>

        <label class="campo">
            <span>Onde vai ser</span>
            <input type="text" name="cidade" value="{{ old('cidade') }}" maxlength="255" placeholder="Cidade ou bairro">
        </label>

        <label class="campo">
            <span>Conte um pouco</span>
            <textarea name="mensagem" rows="3" maxlength="2000">{{ old('mensagem') }}</textarea>
        </label>

        <label class="campo campo--aceite">
            <input type="checkbox" name="consentimento" value="1" required>
            <span>
                Autorizo o uso do meu contato para responder a este pedido.
                Ver a <a href="{{ route('site.privacidade') }}">política de privacidade</a>.
            </span>
        </label>
        @error('consentimento')<p class="erro">{{ $message }}</p>@enderror

        <altcha-widget
            challenge="{{ route('captcha.desafio', absolute: false) }}"
            name="captcha_token"
            auto="onload"
            language="pt-br"
            hidefooter
            hidelogo
        ></altcha-widget>
        @error('captcha_token')<p class="erro">{{ $message }}</p>@enderror

        <button class="btn btn--escuro" type="submit">Enviar pedido</button>
    </form>
</div>
