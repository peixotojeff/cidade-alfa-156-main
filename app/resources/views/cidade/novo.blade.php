@extends('cidade.layout')
@section('titulo', 'Abrir chamado')
@push('head')
<link rel="stylesheet" href="{{ asset('leaflet/leaflet.css') }}">
<script defer src="{{ asset('leaflet/leaflet.js') }}"></script>
<script defer src="{{ asset('cidade.js') }}?v=3"></script>
@endpush
@section('conteudo')
<div class="cabecalho-pagina">
    <p class="rotulo">Abrir chamado</p>
    <h1>O que aconteceu e onde?</h1>
    <p class="texto-grande">São três passos. No fim, você recebe um protocolo para acompanhar.</p>
</div>

@if($errors->any())
    <div class="alerta alerta-erro" role="alert">
        @include('cidade.icone', ['nome' => 'alerta'])
        <p>Falta pouco: confira {{ $errors->count() === 1 ? 'o campo destacado' : 'os campos destacados' }} abaixo.</p>
    </div>
@endif

<form class="formulario" method="post" action="{{ route('abrir') }}" novalidate>
    @csrf
    <fieldset class="cartao etapa" @error('servico') aria-describedby="erro-servico" @enderror>
        <legend><span class="etapa-numero" aria-hidden="true">1</span> Qual é o problema?</legend>
        <div class="opcoes">
            @foreach($servicos as $codigo => $item)
                <label class="opcao">
                    <input type="radio" name="servico" value="{{ $codigo }}" required @checked(old('servico') === $codigo)>
                    <span class="opcao-corpo">
                        <span class="opcao-icone">@include('cidade.icone', ['nome' => $item['icone'], 'tamanho' => 32])</span>
                        <span class="opcao-texto"><strong>{{ $item['nome'] }}</strong><span class="muted">{{ $item['exemplo'] }}</span></span>
                        <span class="prazo">@include('cidade.icone', ['nome' => 'relogio', 'tamanho' => 20]) {{ $item['dias'] }} dias</span>
                        <span class="opcao-marca">@include('cidade.icone', ['nome' => 'check', 'tamanho' => 20])</span>
                    </span>
                </label>
            @endforeach
        </div>
        @error('servico')<p class="erro" id="erro-servico">@include('cidade.icone', ['nome' => 'alerta', 'tamanho' => 20]) {{ $message }}</p>@enderror
    </fieldset>

    <div class="cartao etapa">
        <h2 class="etapa-titulo"><span class="etapa-numero" aria-hidden="true">2</span> <label for="descricao">Descreva o que você viu</label></h2>
        <p class="muted" id="descricao-ajuda">Detalhes como um ponto de referência ajudam a equipe a encontrar o local.</p>
        <textarea id="descricao" name="descricao" rows="4" maxlength="1200" required aria-describedby="descricao-ajuda @error('descricao') erro-descricao @enderror" @error('descricao') aria-invalid="true" @enderror placeholder="Ex.: poste apagado em frente à praça fictícia, perto do ponto de ônibus.">{{ old('descricao') }}</textarea>
        @error('descricao')<p class="erro" id="erro-descricao">@include('cidade.icone', ['nome' => 'alerta', 'tamanho' => 20]) {{ $message }}</p>@enderror
    </div>

    <fieldset class="cartao etapa">
        <legend><span class="etapa-numero" aria-hidden="true">3</span> Onde fica?</legend>
        <p class="muted">Toque no mapa ou use sua localização. Depois, arraste o pin para ajustar.</p>
        <div id="mapa" aria-label="Mapa para marcar o local do problema"></div>
        <div class="mapa-acoes">
            <button class="botao botao-secundario" type="button" id="localizar" hidden>@include('cidade.icone', ['nome' => 'mira', 'tamanho' => 20]) Usar minha localização</button>
            <p id="mapa-status" class="status-mapa" aria-live="polite"></p>
        </div>
        <details class="coordenadas" @if($errors->hasAny(['latitude', 'longitude']) || old('latitude')) open @endif>
            <summary>Prefiro digitar as coordenadas</summary>
            <div class="coords">
                <div class="campo">
                    <label for="latitude">Latitude</label>
                    <input id="latitude" name="latitude" type="number" step="any" min="-90" max="90" required inputmode="decimal" value="{{ old('latitude') }}" @error('latitude') aria-invalid="true" aria-describedby="erro-latitude" @enderror>
                    @error('latitude')<p class="erro" id="erro-latitude">@include('cidade.icone', ['nome' => 'alerta', 'tamanho' => 20]) {{ $message }}</p>@enderror
                </div>
                <div class="campo">
                    <label for="longitude">Longitude</label>
                    <input id="longitude" name="longitude" type="number" step="any" min="-180" max="180" required inputmode="decimal" value="{{ old('longitude') }}" @error('longitude') aria-invalid="true" aria-describedby="erro-longitude" @enderror>
                    @error('longitude')<p class="erro" id="erro-longitude">@include('cidade.icone', ['nome' => 'alerta', 'tamanho' => 20]) {{ $message }}</p>@enderror
                </div>
            </div>
        </details>
    </fieldset>

    <div class="enviar">
        <button class="botao botao-primario" type="submit">Registrar chamado @include('cidade.icone', ['nome' => 'seta'])</button>
        <p class="muted">Use apenas dados fictícios: este é um protótipo.</p>
    </div>
</form>
@endsection
