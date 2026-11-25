@extends('cidade.layout')
@section('titulo', 'Acompanhar chamado')
@section('conteudo')
<div class="cabecalho-pagina">
    <p class="rotulo">Acompanhar</p>
    <h1>Como está o seu chamado?</h1>
    <p class="texto-grande">Digite o protocolo que apareceu quando você registrou o chamado.</p>
</div>

<section class="cartao cartao-estreito">
    <form class="pilha" method="post" action="{{ route('consultar') }}">
        @csrf
        <div class="campo">
            <label for="protocolo">Protocolo</label>
            <input id="protocolo" name="protocolo" class="mono" required maxlength="21" autocomplete="off" autocapitalize="characters" spellcheck="false"
                value="{{ old('protocolo') }}" placeholder="ALFA-0000000000000000" aria-describedby="protocolo-ajuda @error('protocolo') erro-protocolo @enderror" @error('protocolo') aria-invalid="true" @enderror>
            <p class="muted" id="protocolo-ajuda">Começa com ALFA- e tem mais 16 letras e números.</p>
            @error('protocolo')<p class="erro" id="erro-protocolo" role="alert">@include('cidade.icone', ['nome' => 'alerta', 'tamanho' => 20]) {{ $message }}</p>@enderror
        </div>
        <button type="submit" class="botao botao-primario">@include('cidade.icone', ['nome' => 'lupa', 'tamanho' => 20]) Consultar</button>
    </form>
</section>

<p class="nota">Ainda não tem um protocolo? <a href="{{ route('novo') }}">Abra um chamado</a>.</p>

<a class="caminho cartao-estreito exemplo-link" href="{{ route('acompanhar', \App\Support\ChamadoExemplo::PROTOCOLO) }}">
    <span class="caminho-icone">@include('cidade.icone', ['nome' => 'check', 'tamanho' => 32])</span>
    <span class="caminho-texto">
        <strong>Ver um chamado de exemplo</strong>
        <span>Buraco na via resolvido em 4 dias, com aviso em cada etapa. Protocolo <span class="mono">{{ \App\Support\ChamadoExemplo::PROTOCOLO }}</span>.</span>
    </span>
    @include('cidade.icone', ['nome' => 'seta', 'classe' => 'caminho-seta'])
</a>
@endsection
