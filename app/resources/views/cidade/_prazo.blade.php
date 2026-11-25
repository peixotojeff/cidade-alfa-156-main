{{-- Etiqueta da situação do prazo (SLA). Uso: @include('cidade._prazo', ['demanda' => $demanda]) --}}
@php $p = \App\Models\Demanda::PRAZO[$demanda->situacaoPrazo()]; $dias = $demanda->diasAteResolver(); @endphp
<span class="pilula pilula-{{ $p['tom'] }}">@include('cidade.icone', ['nome' => $p['icone'], 'tamanho' => 20]) {{ $p['rotulo'] }}@if($dias !== null) · {{ $dias === 0 ? 'mesmo dia' : $dias.($dias === 1 ? ' dia' : ' dias') }}@endif</span>
