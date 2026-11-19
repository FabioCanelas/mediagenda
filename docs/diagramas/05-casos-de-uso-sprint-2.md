# Diagrama de casos de uso · Sprint 2

Historias HU-06 y HU-09 a HU-15. La recepcionista maneja la agenda y ve las citas del día; la inmunoterapia, las notas de evolución y las reglas de atraso son exclusivas de la doctora.

```mermaid
flowchart LR
    doctora(("Doctora"))

    subgraph sistema ["MediAgenda · Sprint 2"]
        direction TB

        subgraph notas ["HU-06 · Notas de evolución"]
            UC1(["Registrar nota de evolución"])
            UC2(["Cerrar nota"])
            UC3(["Agregar anexo de corrección"])
        end

        subgraph tratamiento ["HU-09 y HU-10 · Inmunoterapia y frascos"]
            UC4(["Indicar inmunoterapia"])
            UC5(["Definir esquema de dosis"])
            UC6(["Registrar frasco"])
            UC7(["Asignar color según la dilución"])
            UC8(["Dar de baja un frasco"])
        end

        subgraph aplicaciones ["HU-11 y HU-12 · Aplicaciones y reacciones"]
            UC9(["Registrar aplicación"])
            UC10(["Confirmar dosis distinta al esquema"])
            UC11(["Terminar observación de 30 minutos"])
            UC12(["Registrar reacción sistémica WAO"])
        end

        subgraph atrasos ["HU-13 · Atrasos"]
            UC13(["Configurar reglas de atraso"])
            UC14(["Ver atraso y regla que corresponde"])
        end

        subgraph agenda ["HU-14 · Agenda"]
            UC15(["Agendar cita"])
            UC16(["Validar choque de horario"])
            UC17(["Sugerir horarios libres"])
            UC18(["Confirmar, atender o cancelar cita"])
            UC19(["Ver agenda semanal"])
        end

        subgraph panel ["HU-15 · Panel de inicio"]
            UC20(["Ver citas de hoy"])
            UC21(["Ver aplicaciones de hoy y pacientes atrasados"])
        end
    end

    recepcion(("Recepcionista"))

    UC15 --- recepcion
    UC18 --- recepcion
    UC19 --- recepcion
    UC20 --- recepcion

    doctora --- UC1
    doctora --- UC2
    doctora --- UC3
    doctora --- UC4
    doctora --- UC6
    doctora --- UC8
    doctora --- UC9
    doctora --- UC11
    doctora --- UC12
    doctora --- UC13
    doctora --- UC15
    doctora --- UC19
    doctora --- UC21

    UC3 -.->|"«extend» solo si la nota está cerrada"| UC2
    UC4 -.->|"«include»"| UC5
    UC6 -.->|"«include»"| UC7
    UC9 -.->|"«include»"| UC14
    UC10 -.->|"«extend» si el volumen difiere"| UC9
    UC11 -.->|"«extend» a los 30 minutos"| UC9
    UC15 -.->|"«extend» al terminar la observación"| UC11
    UC15 -.->|"«include»"| UC16
    UC17 -.->|"«extend» si hay choque"| UC16
```

| Caso de uso | Actor | Historia | Regla principal |
|---|---|---|---|
| Cerrar nota | Doctora | HU-06 | Una nota cerrada no se modifica ni se borra; un trigger lo impide en la base |
| Agregar anexo | Doctora | HU-06 | Solo en notas cerradas; los anexos no se editan |
| Registrar frasco | Doctora | HU-10 | El color lo fija la dilución (plata, verde, azul, amarillo, rojo) |
| Registrar aplicación | Doctora | HU-11 | Solo frascos vigentes del mismo tratamiento; la dosis distinta al esquema se confirma |
| Terminar observación | Doctora | HU-11 | Se habilita a los 30 minutos y sugiere la fecha de la siguiente dosis |
| Registrar reacción sistémica | Doctora | HU-12 | Escala WAO de 1 a 5; se avisa en la siguiente aplicación |
| Configurar reglas de atraso | Doctora | HU-13 | Tramos de días de atraso por fase; los ejemplos de la ACAAI los revisa la doctora |
| Agendar cita | Doctora, recepcionista | HU-14 | Dos citas vigentes no pueden cruzarse; se sugieren horarios libres |
| Cancelar cita | Doctora, recepcionista | HU-14 | Pide motivo; la cita queda en el historial, no se borra |
| Panel de inicio | Doctora, recepcionista | HU-15 | La recepción solo ve las citas; lo clínico es de la doctora |
