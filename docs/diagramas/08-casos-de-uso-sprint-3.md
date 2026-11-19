# Diagrama de casos de uso · Sprint 3

Historias HU-05, HU-08, HU-16, HU-17 y HU-18. Aparece un tercer actor, el paciente, que solo llega a su formulario de pre-consulta en la tablet y nunca al expediente.

```mermaid
flowchart LR
    doctora(("Doctora"))

    subgraph sistema ["MediAgenda · Sprint 3"]
        direction TB

        subgraph preconsulta ["HU-05 · Pre-consulta en tablet"]
            UC1(["Preparar la tablet"])
            UC2(["Generar enlace de un solo uso con QR"])
            UC3(["Llenar la pre-consulta"])
            UC4(["Validar preguntas obligatorias"])
            UC5(["Revisar y pasar a la historia clínica"])
            UC6(["Descartar la pre-consulta"])
        end

        subgraph estudios ["HU-08 · Estudios"]
            UC7(["Adjuntar estudio PDF o foto"])
            UC8(["Validar el formato del archivo"])
            UC9(["Descargar el estudio"])
        end

        subgraph act ["HU-16 · Control del asma"]
            UC10(["Registrar el cuestionario ACT"])
            UC11(["Calcular el puntaje"])
            UC12(["Ver la evolución en un gráfico"])
        end

        subgraph documentos ["HU-17 y HU-18 · Documentos"]
            UC13(["Crear receta"])
            UC14(["Emitir PDF con membrete"])
            UC15(["Reimprimir la copia emitida"])
            UC16(["Generar consentimiento informado"])
            UC17(["Registrar la firma"])
            UC18(["Recordar el consentimiento faltante"])
        end
    end

    recepcion(("Recepcionista"))
    paciente(("Paciente"))

    UC1 --- recepcion
    UC3 --- paciente

    doctora --- UC1
    doctora --- UC5
    doctora --- UC7
    doctora --- UC9
    doctora --- UC10
    doctora --- UC12
    doctora --- UC13
    doctora --- UC15
    doctora --- UC16
    doctora --- UC17

    UC1 -.->|"«include»"| UC2
    UC3 -.->|"«include»"| UC4
    UC6 -.->|"«extend»"| UC5
    UC7 -.->|"«include»"| UC8
    UC10 -.->|"«include»"| UC11
    UC13 -.->|"«include»"| UC14
    UC16 -.->|"«include»"| UC14
    UC18 -.->|"«extend» si falta la firma"| UC17
```

| Caso de uso | Actor | Historia | Regla principal |
|---|---|---|---|
| Preparar la tablet | Recepcionista, doctora | HU-05 | El enlace sirve una vez, vence en 3 horas y se guarda solo como hash SHA-256 |
| Llenar la pre-consulta | Paciente | HU-05 | No inicia sesión; si el navegador tenía una sesión del personal, se cierra |
| Revisar la pre-consulta | Doctora | HU-05 | Las respuestas son borrador hasta que la doctora las acepta o corrige |
| Adjuntar estudio | Doctora | HU-08 | Solo PDF, JPG o PNG de hasta 10 MB; el archivo no se reemplaza |
| Registrar el ACT | Doctora | HU-16 | 5 respuestas de 1 a 5; la base calcula el puntaje (5 a 25) |
| Emitir PDF | Doctora | HU-17 | Folio correlativo y copia con huella SHA-256; lo emitido no se modifica |
| Reimprimir | Doctora | HU-17 | Se entrega la copia guardada, después de comprobar su huella |
| Generar consentimiento | Doctora | HU-18 | Apartados de la NOM-004: lugar y fecha, riesgos, beneficios, firmas y testigos |
| Recordar el consentimiento | Doctora | HU-18 | Aviso al registrar una aplicación o un prick test sin consentimiento firmado |
