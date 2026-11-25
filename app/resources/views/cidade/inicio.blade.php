@extends('cidade.layout')
@section('titulo', 'Abrir chamado')
@push('head')
<link rel="stylesheet" href="{{ asset('leaflet/leaflet.css') }}">
<script defer src="{{ asset('leaflet/leaflet.js') }}"></script>
<script defer src="{{ asset('cidade.js') }}"></script>
@endpush
@section('conteudo')
<div class="intro">
    <p class="rotulo">Serviços municipais · Demonstração</p>
    <h1>Abra um chamado</h1>
    <p>Informe o problema, marque onde ele aconteceu e acompanhe a resposta pelo protocolo.</p>
</div>
<div class="colunas">
    <form class="cartao pilha" method="post" action="{{ route('abrir') }}">
        @csrf
        <label for="servico">Tipo de serviço</label>
        <select id="servico" name="servico" required>
            <option value="">Selecione</option>
            @foreach($servicos as $codigo => $item)
                <option value="{{ $codigo }}" data-dias="{{ $item['dias'] }}" @selected(old('servico') === $codigo)>{{ $item['nome'] }}</option>
            @endforeach
        </select>
        <small id="prazo-previsto" aria-live="polite">O prazo depende do serviço escolhido.</small>
        @error('servico')<p class="erro" role="alert">{{ $message }}</p>@enderror
        <label for="descricao">O que aconteceu?</label>
        <textarea id="descricao" name="descricao" rows="3" maxlength="1200" required placeholder="Ex.: poste apagado perto da praça fictícia">{{ old('descricao') }}</textarea>
        @error('descricao')<p class="erro" role="alert">{{ $message }}</p>@enderror
        <fieldset class="pilha">
            <legend>Local do problema</legend>
            <p class="muted">Toque no mapa ou use sua localização e ajuste o pin. Também é possível digitar as coordenadas.</p>
            <div id="mapa" aria-label="Mapa para marcar o local do problema"></div>
            <button class="secundario" type="button" id="localizar" hidden>Usar minha localização</button>
            <p id="mapa-status" class="muted" aria-live="polite"></p>
            <div class="coords">
                <div><label for="latitude">Latitude</label><input id="latitude" name="latitude" type="number" step="any" min="-90" max="90" required value="{{ old('latitude') }}">@error('latitude')<p class="erro" role="alert">{{ $message }}</p>@enderror</div>
                <div><label for="longitude">Longitude</label><input id="longitude" name="longitude" type="number" step="any" min="-180" max="180" required value="{{ old('longitude') }}">@error('longitude')<p class="erro" role="alert">{{ $message }}</p>@enderror</div>
            </div>
        </fieldset>
        <button type="submit">Confirmar local e registrar</button>
    </form>
    <section id="consulta" class="cartao">
        <h2>Acompanhar chamado</h2>
        <p class="muted">Consulte o prazo e veja o que mudou usando seu protocolo.</p>
        <form class="pilha" method="post" action="{{ route('consultar') }}">
            @csrf
            <label for="protocolo">Protocolo</label>
            <input id="protocolo" name="protocolo" required maxlength="21" value="{{ old('protocolo') }}" placeholder="ALFA-…">
            @error('protocolo')<p class="erro" role="alert">{{ $message }}</p>@enderror
            <button type="submit" class="secundario">Consultar</button>
        </form>
    </section>
</div>
@endsection
