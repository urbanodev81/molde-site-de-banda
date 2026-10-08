@extends(\App\Support\SitePublicado::bloqueia(request()) ? 'site.layout-fechado' : 'site.layout', [
    'tituloDaPagina' => 'Privacidade · '.($config['banda.nome'] ?? 'A melhor banda'),
    'descricaoDaPagina' => 'O que este site guarda, por quanto tempo e como pedir para apagar.',
])

@section('conteudo')
    <section class="secao">
        <div class="wrap prosa">
            <p class="olho">LGPD</p>
            <h1>Política de privacidade</h1>

            <p>
                Este site é da banda {{ $config['banda.nome'] ?? 'A melhor banda' }} e é operado pela
                Exemplo. Ele guarda pouca coisa, e esta página diz exatamente o quê.
            </p>

            <h2>O que é guardado, e por quê</h2>
            <p>
                Se você preencher o formulário de contratação, guardamos <strong>o que você
                digitou</strong> — nome, telefone e/ou e-mail, tipo de evento, data pretendida,
                cidade, local e a mensagem — mais a <strong>data e o endereço IP do aceite</strong>.
                A finalidade é uma só: responder ao seu pedido de show.
            </p>
            <p>
                Não usamos esse contato para lista de divulgação, não vendemos e não passamos
                para ninguém. Se você chamar pelo WhatsApp, a conversa acontece lá — este site
                não guarda cópia dela.
            </p>

            <h2>Por quanto tempo</h2>

            <ul>
                @foreach ($retencao as $politica)
                    <li>
                        <strong>{{ ['auditoria' => 'Registro de alterações no sistema', 'contratacao' => 'Pedidos de contratação', 'contratacao_interacao' => 'Histórico de conversas sobre um pedido'][$politica->recurso] ?? $politica->recurso }}</strong>:
                        {{ $politica->nunca_expurgar ? 'guardado enquanto for necessário como prova' : $politica->meses.' meses' }}.
                        @if ($politica->justificativa) {{ $politica->justificativa }} @endif
                    </li>
                @endforeach
            </ul>
            <p>Passado o prazo, uma rotina automática apaga — não depende de alguém lembrar.</p>

            <h2>Cookies e terceiros</h2>
            <p>
                O site usa apenas o cookie de sessão que o próprio servidor precisa para
                proteger o formulário contra envio forjado. Não há analytics de terceiro, não
                há pixel de rede social e não há publicidade.
            </p>

            <h2>Contagem de páginas</h2>
            <p>
                Contamos <strong>quantas vezes cada página foi aberta, por dia</strong> — é o que
                nos diz qual show as pessoas mais procuraram. Essa contagem
                <strong>não identifica você</strong>: não guardamos endereço IP, não gravamos
                cookie de rastreio, não registramos seu navegador nem ligamos duas visitas à
                mesma pessoa.
            </p>
            <p>
                O que fica registrado é uma linha como <em>"a agenda foi aberta 47 vezes em
                14/09"</em>, e nada além disso. É por isso que não sabemos — e não temos como
                saber — quantas pessoas diferentes visitaram o site, de onde elas vieram ou
                quanto tempo ficaram.
            </p>
            <p>
                As fontes vêm do <a href="https://fonts.bunny.net" rel="noopener" target="_blank">bunny.net</a>,
                que é um espelho do Google Fonts que <strong>não registra o IP de quem visita</strong> —
                foi essa a razão da escolha. Vídeo do YouTube, quando houver, só carrega
                <strong>depois</strong> de você clicar para tocar, e no domínio sem cookie de rastreio.
            </p>

            <h2>Seus direitos</h2>
            <p>
                Você pode pedir para ver, corrigir ou apagar o que guardamos sobre você.
                @if ($config['contato.email'] ?? null)
                    Escreva para <a href="mailto:{{ $config['contato.email'] }}">{{ $config['contato.email'] }}</a>.
                @else
                    Peça pelo WhatsApp da banda.
                @endif
                A resposta sai em até 15 dias.
            </p>

            <p style="margin-top:2rem;font-size:.9rem">Última revisão: {{ now()->translatedFormat('F \d\e Y') }}.</p>
        </div>
    </section>
@endsection
