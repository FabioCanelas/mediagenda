@extends('pdf.layout')

@section('contenido')
    <h1>Receta médica</h1>

    <table class="datos">
        <tr>
            <td class="etiqueta">Paciente</td>
            <td>{{ $paciente->nombre_completo }}</td>
            <td class="etiqueta">Fecha</td>
            <td>{{ $receta->fecha->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td class="etiqueta">CI</td>
            <td>{{ $paciente->ci_completo }}</td>
            <td class="etiqueta">Edad</td>
            <td>{{ $paciente->edad !== null ? $paciente->edad.' años' : '—' }}</td>
        </tr>
    </table>

    @if ($paciente->alergiasVigentes->isNotEmpty())
        <div class="aviso">
            Alergias: {{ $paciente->alergiasVigentes->pluck('sustancia')->implode(', ') }}
        </div>
    @endif

    <h2>Rp.</h2>
    <ol>
        @foreach ($receta->medicamentos as $medicamento)
            <li>
                <strong>{{ $medicamento->medicamento }}</strong>
                @if (filled($medicamento->presentacion)) {{ $medicamento->presentacion }} @endif
                <br>
                {{ $medicamento->dosis }}@if (filled($medicamento->duracion)), {{ $medicamento->duracion }}@endif.
                @if (filled($medicamento->indicaciones))
                    <br><span style="font-size: 9.5pt;">{{ $medicamento->indicaciones }}</span>
                @endif
            </li>
        @endforeach
    </ol>

    @if (filled($receta->indicaciones))
        <h2>Indicaciones</h2>
        <p style="white-space: pre-line; margin: 0;">{{ $receta->indicaciones }}</p>
    @endif

    <table class="firma">
        <tr>
            <td></td>
            <td>
                <div class="linea">
                    {{ $consultorio['doctora'] }}<br>
                    {{ $consultorio['matricula'] }}
                </div>
            </td>
        </tr>
    </table>
@endsection
