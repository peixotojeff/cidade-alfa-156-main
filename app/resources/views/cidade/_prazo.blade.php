{{-- Etiqueta da situação do prazo (SLA). Uso: @include('cidade._prazo', ['demanda' => $demanda])
     Chamados do SP156 em aberto mostram a situação na data de corte da planilha, não hoje. --}}
@php
    $p = \App\Models\Demanda::PRAZO[$demanda->situacaoPrazo()];
    $dias = $demanda->diasAteResolver();
    $corte = $demanda->veioDoSp156() && $demanda->status !== 'resolvida' ? \App\Models\Demanda::corteSp156($demanda->created_at) : null;
@endphp
<span class="pilula pilula-{{ $p['tom'] }}">@include('cidade.icone', ['nome' => $p['icone'], 'tamanho' => 20]) {{ $p['rotulo'] }}@if($dias !== null) · {{ $dias === 0 ? 'mesmo dia' : $dias.($dias === 1 ? ' dia' : ' dias') }}@endif @if($corte)<span class="pilula-nota">em {{ $corte->format('d/m/Y') }}</span>@endif</span>
