{{-- Base de los documentos PDF (dompdf): membrete arriba y pie con el folio. --}}
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $folio }}</title>
    <style>
        @page { margin: 28mm 18mm 22mm 18mm; }
        body { font-family: "DejaVu Sans", sans-serif; font-size: 10.5pt; color: #1f2937; line-height: 1.45; }
        header { position: fixed; top: -22mm; left: 0; right: 0; height: 20mm; border-bottom: 2px solid #0f766e; }
        footer { position: fixed; bottom: -16mm; left: 0; right: 0; height: 12mm; font-size: 8pt; color: #6b7280; border-top: 1px solid #d1d5db; padding-top: 2mm; }
        .doctora { font-size: 14pt; font-weight: bold; color: #0f766e; }
        .especialidad { font-size: 9.5pt; }
        .contacto { font-size: 8.5pt; color: #4b5563; text-align: right; }
        h1 { font-size: 13pt; text-align: center; margin: 0 0 4mm 0; text-transform: uppercase; letter-spacing: 0.5px; }
        h2 { font-size: 10.5pt; margin: 5mm 0 1.5mm 0; color: #0f766e; }
        table.datos { width: 100%; border-collapse: collapse; margin-bottom: 3mm; }
        table.datos td { padding: 1.2mm 2mm; border: 1px solid #e5e7eb; vertical-align: top; }
        table.datos td.etiqueta { width: 22%; background: #f3f4f6; font-weight: bold; font-size: 9pt; }
        .aviso { border: 1.5px solid #b91c1c; color: #b91c1c; padding: 2mm 3mm; margin: 2mm 0 4mm 0; font-weight: bold; }
        .firma { margin-top: 18mm; width: 100%; }
        .firma td { width: 50%; text-align: center; vertical-align: bottom; padding: 0 6mm; font-size: 9pt; }
        .linea { border-top: 1px solid #1f2937; padding-top: 1.5mm; }
        .muestra { color: #9a3412; font-size: 8pt; }
        ul, ol { margin: 0 0 0 5mm; padding: 0; }
        li { margin-bottom: 1mm; }
    </style>
</head>
<body>
    <header>
        <table style="width: 100%;">
            <tr>
                <td>
                    <div class="doctora">{{ $consultorio['doctora'] }}</div>
                    <div class="especialidad">{{ $consultorio['especialidad'] }} · {{ $consultorio['matricula'] }}</div>
                </td>
                <td class="contacto">
                    {{ $consultorio['direccion'] }}<br>
                    Tel. {{ $consultorio['telefono'] }} · {{ $consultorio['ciudad'] }}
                </td>
            </tr>
        </table>
    </header>

    <footer>
        Folio {{ $folio }} · emitido el {{ $emitidoEn->format('d/m/Y H:i') }} con MediAgenda.
        La copia de este documento queda guardada en el expediente del paciente.
    </footer>

    <main>
        @yield('contenido')
    </main>
</body>
</html>
