@php
    $enlace = $getState();
@endphp

<div style="display: flex; flex-direction: column; align-items: center; gap: 0.75rem; text-align: center;">
    @if (filled($enlace))
        <div style="background: #fff; padding: 0.75rem; border-radius: 0.75rem;">
            {!! \App\Support\CodigoQr::svg($enlace) !!}
        </div>

        <p style="font-size: 0.8rem; word-break: break-all; opacity: 0.75;">{{ $enlace }}</p>

        <x-filament::link :href="$enlace" target="_blank" size="sm" icon="heroicon-o-arrow-top-right-on-square">
            Abrir en este navegador (cierra tu sesión aquí)
        </x-filament::link>
    @endif
</div>
