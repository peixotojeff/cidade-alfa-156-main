{{-- Barra de resolutividade no prazo. Uso: @include('cidade._medidor', ['pct' => 72]) --}}
@if($pct === null)
    <span class="muted">Sem chamados resolvidos</span>
@else
    @php $tom = $pct >= 80 ? 'leaf' : ($pct >= 50 ? 'sun' : 'coral'); @endphp
    <span class="medidor medidor-{{ $tom }}">
        <span class="medidor-trilho" aria-hidden="true"><span style="--valor: {{ $pct }}%"></span></span>
        <strong class="mono">{{ $pct }}%</strong>
    </span>
@endif
