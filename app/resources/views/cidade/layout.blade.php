<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="referrer" content="strict-origin-when-cross-origin">
    <meta name="theme-color" content="#1D4ED8">
    <title>@yield('titulo') · Cidade Alfa 156+</title>
    <link rel="preload" href="{{ asset('fonts/atkinson-hyperlegible-latin-400-normal.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="{{ asset('fonts/hubot-sans-latin-700-normal.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="{{ asset('cidade.css') }}?v=12">
    @stack('head')
</head>
<body>
    <a class="atalho" href="#conteudo">Ir para o conteúdo</a>
    @php
        $menu = [
            ['rota' => 'inicio', 'ativo' => ['inicio'], 'rotulo' => 'Início', 'icone' => 'predio'],
            ['rota' => 'novo', 'ativo' => ['novo'], 'rotulo' => 'Abrir chamado', 'icone' => 'pin'],
            ['rota' => 'consulta', 'ativo' => ['consulta', 'acompanhar'], 'rotulo' => 'Acompanhar', 'icone' => 'lupa'],
            ['rota' => 'indicadores', 'ativo' => ['indicadores'], 'rotulo' => 'Indicadores', 'icone' => 'grafico'],
            ['rota' => 'analise', 'ativo' => ['analise'], 'rotulo' => 'Análise', 'icone' => 'camadas'],
            ['rota' => 'modelos', 'ativo' => ['modelos'], 'rotulo' => 'Modelos', 'icone' => 'codigo'],
        ];
        if (\App\Support\AcessoSecretaria::visivel(request())) {
            $menu[] = ['rota' => 'secretaria', 'ativo' => ['secretaria'], 'rotulo' => 'Secretaria', 'icone' => 'lista'];
        }
    @endphp
    <header class="topo">
        <div class="largura topo-conteudo">
            <a class="marca" href="{{ route('inicio') }}" @if(request()->routeIs('inicio')) aria-current="page" @endif>
                <span class="marca-selo" aria-hidden="true">156+</span>
                <span>Cidade Alfa</span>
            </a>
            <nav class="menu" aria-label="Navegação principal">
                <ul>
                    @foreach($menu as $item)
                        <li><a href="{{ route($item['rota']) }}" @if(request()->routeIs(...$item['ativo'])) aria-current="page" @endif>
                            @include('cidade.icone', ['nome' => $item['icone'], 'tamanho' => 20])<span>{{ $item['rotulo'] }}</span>
                        </a></li>
                    @endforeach
                </ul>
            </nav>
        </div>
    </header>
    <main id="conteudo" class="largura" tabindex="-1">@yield('conteudo')</main>
    <footer class="rodape">
        <div class="largura">Protótipo acadêmico · Data Drivens · Ao abrir um chamado, use apenas informações fictícias.</div>
    </footer>
</body>
</html>
