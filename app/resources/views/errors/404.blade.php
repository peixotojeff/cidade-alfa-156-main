@extends('cidade.layout')
@section('titulo', 'Página não encontrada')
@section('conteudo')
<div class="cabecalho-pagina">
    <p class="rotulo">Erro 404</p>
    <h1>Não encontramos esta página</h1>
    <p class="texto-grande">Se você estava consultando um chamado, confira o protocolo: ele começa com ALFA- e tem mais 16 letras e números.</p>
</div>
<div class="caminhos">
    <a class="caminho caminho-primario" href="{{ route('consulta') }}">
        <span class="caminho-icone">@include('cidade.icone', ['nome' => 'lupa', 'tamanho' => 32])</span>
        <span class="caminho-texto"><strong>Consultar um protocolo</strong><span>Digite o protocolo de novo.</span></span>
        @include('cidade.icone', ['nome' => 'seta', 'classe' => 'caminho-seta'])
    </a>
    <a class="caminho" href="{{ route('inicio') }}">
        <span class="caminho-icone">@include('cidade.icone', ['nome' => 'predio', 'tamanho' => 32])</span>
        <span class="caminho-texto"><strong>Ir para o início</strong><span>Abrir um chamado ou ver os prazos.</span></span>
        @include('cidade.icone', ['nome' => 'seta', 'classe' => 'caminho-seta'])
    </a>
</div>
@endsection
