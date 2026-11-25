{{-- Ícones Skylearn: traço 2px, pontas e junções arredondadas, decorativos (aria-hidden). Uso: @include('cidade.icone', ['nome' => 'check']) --}}
@php
    $tracos = [
        'check' => '<path d="M5 12.5l4.5 4.5L19 7.5"/>',
        'alerta' => '<circle cx="12" cy="12" r="9"/><path d="M12 7.5v5.5"/><path d="M12 16.5h.01"/>',
        'relogio' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'pin' => '<path d="M12 21s-6.5-5.6-6.5-11a6.5 6.5 0 0 1 13 0c0 5.4-6.5 11-6.5 11z"/><circle cx="12" cy="10" r="2.5"/>',
        'lupa' => '<circle cx="10.5" cy="10.5" r="6.5"/><path d="M15.5 15.5L20 20"/>',
        'predio' => '<path d="M4 21h16"/><path d="M6 21V8l6-4 6 4v13"/><path d="M10 21v-4h4v4"/><path d="M9.5 11h.01M14.5 11h.01"/>',
        'lampada' => '<path d="M9 18h6"/><path d="M10 21h4"/><path d="M12 3a6 6 0 0 0-3.5 10.9c.6.5 1 1.2 1 2.1h5c0-.9.4-1.6 1-2.1A6 6 0 0 0 12 3z"/>',
        'cone' => '<path d="M4 20h16"/><path d="M9.5 4h5L19 20H5z"/><path d="M8 11h8"/>',
        'folha' => '<path d="M5 19c0-8 5-13 14-14 0 9-5 14-13 14z"/><path d="M5 19l7-7"/>',
        'entrada' => '<path d="M4 13l2.5-7h11L20 13v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1z"/><path d="M4 13h4.5l1 2h5l1-2H20"/>',
        'ferramenta' => '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.8-3.8a6 6 0 0 1-7.9 7.9l-6.9 6.9a2.1 2.1 0 0 1-3-3l6.9-6.9a6 6 0 0 1 7.9-7.9z"/>',
        'lista' => '<rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4V3h6v1"/><path d="M9 10h6M9 14h6M9 18h3"/>',
        'arvore' => '<path d="M12 2l6 8h-3l4 6H5l4-6H6z"/><path d="M12 16v6"/>',
        'carro' => '<path d="M5 16V12l1.8-4.5A2 2 0 0 1 8.7 6h6.6a2 2 0 0 1 1.9 1.5L19 12v4"/><path d="M4 16h16v2a1 1 0 0 1-1 1h-1.5a1 1 0 0 1-1-1v-1h-9v1a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1z"/><path d="M5 12h14"/><path d="M7.5 14.5h.01M16.5 14.5h.01"/>',
        'gota' => '<path d="M12 3s6 6.4 6 11a6 6 0 0 1-12 0c0-4.6 6-11 6-11z"/><path d="M9.5 14.5a2.5 2.5 0 0 0 2.5 2.5"/>',
        'guia' => '<path d="M3 17h18"/><path d="M3 13h7l2 4"/><path d="M14 13h7"/><path d="M6 9h12"/>',
        'grafico' => '<path d="M4 20V10"/><path d="M10 20V4"/><path d="M16 20v-7"/><path d="M3 20h18"/>',
        'seta' => '<path d="M5 12h14"/><path d="M13 6l6 6-6 6"/>',
        'mira' => '<circle cx="12" cy="12" r="7"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3"/><circle cx="12" cy="12" r="1.5"/>',
    ];
@endphp
<svg class="icone {{ $classe ?? '' }}" width="{{ $tamanho ?? 24 }}" height="{{ $tamanho ?? 24 }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">{!! $tracos[$nome] ?? '' !!}</svg>
