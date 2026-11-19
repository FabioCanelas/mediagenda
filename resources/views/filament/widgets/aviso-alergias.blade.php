<x-filament-widgets::widget>
    @php
        $paciente = $this->getPaciente();
        $alergias = $this->getAlergias();
    @endphp

    @if ($paciente)
        @if ($alergias->isNotEmpty())
            <x-filament::callout
                color="danger"
                icon="heroicon-o-exclamation-triangle"
                heading="Alergias de {{ $paciente->nombre_completo }}"
                description="Revisa antes de indicar cualquier medicamento o procedimiento."
            >
                <x-slot name="footer">
                    <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                        @foreach ($alergias as $alergia)
                            <x-filament::badge :color="$alergia->criticidad->getColor()" size="lg">
                                {{ $alergia->sustancia }} · {{ mb_strtolower($alergia->estado->getLabel()) }} · criticidad {{ mb_strtolower($alergia->criticidad->getLabel()) }}
                            </x-filament::badge>
                        @endforeach
                    </div>
                </x-slot>
            </x-filament::callout>
        @else
            <x-filament::callout
                color="gray"
                icon="heroicon-o-check-circle"
                heading="Sin alergias registradas"
                description="{{ $paciente->nombre_completo }} no tiene alergias registradas o todas fueron descartadas."
            />
        @endif
    @endif
</x-filament-widgets::widget>
