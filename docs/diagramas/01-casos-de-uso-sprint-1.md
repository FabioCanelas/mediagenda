# Diagrama de casos de uso · Sprint 1

Historias HU-01, HU-02, HU-03, HU-04 y HU-07. La recepcionista solo llega a los casos de uso de pacientes y acceso; todo lo clínico es exclusivo de la doctora.

```mermaid
flowchart LR
    doctora(("Doctora"))

    subgraph sistema ["MediAgenda · Sprint 1"]
        direction TB

        subgraph acceso ["HU-01 · Acceso"]
            UC1(["Iniciar sesión"])
            UC2(["Activar verificación en dos pasos"])
            UC3(["Gestionar usuarios y activar recepción"])
        end

        subgraph pacientes ["HU-02 · Pacientes"]
            UC4(["Registrar paciente"])
            UC5(["Validar CI duplicado"])
            UC6(["Registrar tutor"])
            UC7(["Buscar paciente por nombre o CI"])
        end

        subgraph historia ["HU-03 · Historia clínica"]
            UC8(["Llenar historia clínica"])
            UC9(["Registrar diagnóstico CIE-10 o texto libre"])
            UC10(["Ver historial de cambios"])
        end

        subgraph alergias ["HU-04 · Alergias"]
            UC11(["Registrar alergia"])
            UC12(["Descartar alergia"])
            UC13(["Ver aviso de alergias"])
        end

        subgraph prick ["HU-07 · Prick test"]
            UC14(["Registrar prick test"])
            UC15(["Calcular positivos y validez de la prueba"])
            UC16(["Configurar panel de alérgenos"])
        end
    end

    recepcion(("Recepcionista"))

    UC1 --- recepcion
    UC2 --- recepcion
    UC4 --- recepcion
    UC7 --- recepcion

    doctora --- UC1
    doctora --- UC2
    doctora --- UC3
    doctora --- UC4
    doctora --- UC7
    doctora --- UC8
    doctora --- UC10
    doctora --- UC11
    doctora --- UC13
    doctora --- UC14
    doctora --- UC16

    UC4 -.->|"«include»"| UC5
    UC6 -.->|"«extend» si es menor de 18 años"| UC4
    UC8 -.->|"«include»"| UC9
    UC12 -.->|"«extend»"| UC11
    UC14 -.->|"«include»"| UC15
    UC8 -.->|"«include»"| UC13
    UC14 -.->|"«include»"| UC13
```

| Caso de uso | Actor | Historia | Regla principal |
|---|---|---|---|
| Iniciar sesión | Doctora, recepcionista | HU-01 | Solo entran usuarios activos |
| Gestionar usuarios | Doctora | HU-01 | Los usuarios no se borran: se desactivan |
| Registrar paciente | Doctora, recepcionista | HU-02 | CI único (con complemento); tutor obligatorio si es menor |
| Llenar historia clínica | Doctora | HU-03 | Apartados de la NOM-004; cada cambio queda en la auditoría |
| Registrar alergia | Doctora | HU-04 | Una alergia errónea se descarta, no se borra |
| Registrar prick test | Doctora | HU-07 | Positivo si la pápula supera al control negativo en el criterio o más |
