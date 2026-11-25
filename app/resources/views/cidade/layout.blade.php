<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="referrer" content="strict-origin-when-cross-origin">
    <title>@yield('titulo') · Cidade Alfa 156+</title>
    <link rel="stylesheet" href="{{ asset('cidade.css') }}">
    @stack('head')
</head>
<body>
    <a class="atalho" href="#conteudo">Ir para o conteúdo</a>
    <header><div class="largura cabecalho">
        <a class="marca" href="{{ route('inicio') }}"><span>156+</span> Cidade Alfa</a>
        <nav aria-label="Navegação principal"><a href="{{ route('inicio') }}#consulta">Acompanhar chamado</a>
        @if(\App\Support\AcessoSecretaria::visivel(request()))
            <a href="{{ route('secretaria') }}">Secretaria</a>
        @endif</nav>
    </div></header>
    <main id="conteudo" class="largura">@yield('conteudo')</main>
    <footer class="largura">Protótipo acadêmico · Data Drivens · Use apenas dados fictícios</footer>
</body>
</html>
