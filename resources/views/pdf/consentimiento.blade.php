@extends('pdf.layout')

@section('contenido')
    <div style="font-size: 9.6pt; line-height: 1.32;">
    <h1>Consentimiento informado</h1>
    <p style="text-align: center; margin-top: -2mm;"><strong>{{ $consentimiento->procedimiento->getLabel() }}</strong></p>

    <table class="datos">
        <tr>
            <td class="etiqueta">Lugar y fecha</td>
            <td colspan="3">{{ $consentimiento->lugar }}, {{ $consentimiento->fecha->translatedFormat('j \d\e F \d\e Y') }}</td>
        </tr>
        <tr>
            <td class="etiqueta">Paciente</td>
            <td>{{ $paciente->nombre_completo }}</td>
            <td class="etiqueta">CI</td>
            <td>{{ $paciente->ci_completo }}</td>
        </tr>
        @if ($consentimiento->firmante_es_tutor)
            <tr>
                <td class="etiqueta">Tutor o representante</td>
                <td colspan="3">{{ $consentimiento->firmante_nombre }}@if (filled($paciente->tutor_parentesco)) ({{ $paciente->tutor_parentesco }})@endif</td>
            </tr>
        @endif
    </table>

    <p>
        Yo, <strong>{{ $consentimiento->firmante_nombre }}</strong>,
        @if ($consentimiento->firmante_es_tutor)
            en representación del paciente,
        @endif
        declaro que {{ $consultorio['doctora'] }} me explicó en palabras claras el procedimiento, sus beneficios,
        sus riesgos y las alternativas, y que pude hacer todas las preguntas que quise.
    </p>

    <h2>En qué consiste</h2>
    <p style="margin: 0;">{{ $textos['descripcion'] }}</p>

    <h2>Beneficios esperados</h2>
    <ul>
        @foreach ($textos['beneficios'] as $beneficio)
            <li>{{ $beneficio }}</li>
        @endforeach
    </ul>

    <h2>Riesgos</h2>
    <ul>
        @foreach ($textos['riesgos'] as $riesgo)
            <li>{{ $riesgo }}</li>
        @endforeach
    </ul>

    <h2>Cuidados y alternativas</h2>
    <p style="margin: 0;">{{ $textos['cuidados'] }} Alternativas: {{ $textos['alternativas'] }}</p>

    <div style="page-break-inside: avoid;">
        <h2>Autorización</h2>
        <p style="margin: 0;">
            Autorizo la realización del procedimiento y autorizo a la doctora y a su personal a atender las
            contingencias y urgencias que se presenten durante el mismo. Sé que puedo retirar este consentimiento
            en cualquier momento, antes de cada aplicación, sin que eso afecte mi atención.
        </p>

        <table class="firma" style="margin-top: 13mm;">
            <tr>
                <td>
                    <div class="linea">
                        {{ $consentimiento->firmante_nombre }}<br>
                        {{ $consentimiento->firmante_es_tutor ? 'Tutor o representante' : 'Paciente' }}
                    </div>
                </td>
                <td>
                    <div class="linea">
                        {{ $consultorio['doctora'] }}<br>
                        {{ $consultorio['matricula'] }}
                    </div>
                </td>
            </tr>
        </table>

        <table class="firma" style="margin-top: 11mm;">
            <tr>
                <td><div class="linea">Testigo 1: nombre y firma</div></td>
                <td><div class="linea">Testigo 2: nombre y firma</div></td>
            </tr>
        </table>

        <p class="muestra" style="margin-top: 4mm;">Texto de ejemplo del prototipo: la doctora debe revisarlo antes de usarlo con pacientes reales.</p>
    </div>
    </div>
@endsection
