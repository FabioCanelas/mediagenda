<x-filament-panels::page>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 1rem;">
        @foreach ($this->getDias() as $dia)
            <x-filament::section compact :heading="ucfirst($dia['fecha']->translatedFormat('l d/m')).($dia['fecha']->isToday() ? ' · hoy' : '')">
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    @forelse ($dia['citas'] as $cita)
                        <a href="{{ \App\Filament\Resources\Citas\CitaResource::getUrl('edit', ['record' => $cita]) }}"
                           style="display: block; text-decoration: none;">
                            <div style="font-weight: 600;">
                                {{ $cita->inicio->format('H:i') }} – {{ $cita->fin->format('H:i') }}
                            </div>
                            <div>{{ $cita->paciente->nombre_completo }}</div>
                            <div style="display: flex; flex-wrap: wrap; gap: 0.25rem; margin-top: 0.25rem;">
                                <x-filament::badge :color="$cita->tipo->getColor()" size="sm">{{ $cita->tipo->getLabel() }}</x-filament::badge>
                                <x-filament::badge :color="$cita->estado->getColor()" size="sm">{{ $cita->estado->getLabel() }}</x-filament::badge>
                            </div>
                        </a>
                    @empty
                        <p style="opacity: 0.6;">Sin citas</p>
                    @endforelse
                </div>
            </x-filament::section>
        @endforeach
    </div>
</x-filament-panels::page>
