@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\RegistroAuditoria> $registros */
@endphp

<div style="display: flex; flex-direction: column; gap: 1rem;">
    @forelse ($registros as $registro)
        <x-filament::section compact>
            <x-slot name="heading">
                {{ $registro->realizado_en->format('d/m/Y H:i') }}
                · {{ $registro->usuario?->name ?? 'Sistema' }}
                · {{ $registro->accion === 'INSERT' ? 'Creación' : 'Modificación' }}
            </x-slot>

            @if ($registro->accion === 'INSERT')
                <p>Se creó la historia clínica.</p>
            @else
                @php($cambios = $registro->cambios())

                @if ($cambios === [])
                    <p>Sin cambios en el contenido.</p>
                @else
                    <dl style="display: grid; gap: 0.5rem;">
                        @foreach ($cambios as $campo => $valores)
                            <div>
                                <dt style="font-weight: 600;">{{ $etiquetas[$campo] ?? $campo }}</dt>
                                <dd>
                                    <span style="opacity: 0.7;">Antes:</span> {{ \Illuminate\Support\Str::limit((string) ($valores['antes'] ?? '—'), 200) }}
                                    <br>
                                    <span style="opacity: 0.7;">Después:</span> {{ \Illuminate\Support\Str::limit((string) ($valores['despues'] ?? '—'), 200) }}
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                @endif
            @endif
        </x-filament::section>
    @empty
        <p>Todavía no hay cambios registrados.</p>
    @endforelse
</div>
