# Diagrama de secuencia · Aplicar una dosis de inmunoterapia (HU-11 a HU-14)

Desde que la doctora abre "Registrar aplicación" hasta que termina la observación y agenda la siguiente dosis.

```mermaid
sequenceDiagram
    autonumber
    actor D as Doctora
    participant F as Modal "Registrar aplicación"<br/>(Filament)
    participant I as Inmunoterapia<br/>(modelo)
    participant A as Aplicacion<br/>(modelo)
    participant BD as MariaDB
    participant T as Triggers

    D->>F: Abre "Registrar aplicación" en el tratamiento del paciente
    F->>BD: SELECT FROM v_proxima_aplicacion
    BD-->>F: Fecha prevista de la dosis
    F->>I: diasDeAtraso(), reglaDeAtraso(), ultimaReaccionSistemica()
    I->>BD: SELECT reglas_atraso y reacciones_sistemicas
    BD-->>I: Regla del tramo y última reacción
    F->>I: dosisEsperada()
    I-->>F: Paso, dilución y volumen del esquema
    F->>BD: SELECT frascos activos y sin vencer
    F-->>D: Avisos de atraso y de reacción previa, dosis sugerida y frasco vigente

    D->>F: Ajusta el volumen si la regla lo pide, elige el brazo y presiona "Registrar"
    F->>F: Valida rangos (0.01 a 1 mL)

    alt El volumen difiere del esquema y no está confirmado
        F-->>D: Pide marcar "Confirmo que esta dosis es distinta"
    else Datos válidos
        F->>A: create(frasco, paso, volumen, brazo)
        A->>A: observacion_hasta = aplicada_en + 30 min
        A->>A: difiereDelEsquema()
        A->>BD: INSERT INTO aplicaciones
        BD->>T: BEFORE INSERT trg_aplicaciones_validar_frasco
        alt Frasco vencido, dado de baja o de otro tratamiento
            T-->>BD: SIGNAL 45000
            BD-->>F: Error de la base
            F-->>D: "La base de datos rechazó la aplicación"
        else Frasco válido
            BD->>BD: Verifica CHECK de volumen, brazo y observación
            BD->>T: AFTER INSERT (auditoría)
            T->>BD: INSERT INTO auditoria (usuario = @app_usuario_id)
            F-->>D: "Mantén al paciente en observación hasta las HH:MM"
        end
    end

    Note over D,F: La tabla se actualiza cada 30 segundos

    opt Reacción sistémica durante la observación
        D->>F: "Reacción sistémica": grado WAO, síntomas, epinefrina
        F->>BD: INSERT INTO reacciones_sistemicas (grado 1 a 5)
    end

    D->>F: "Terminar observación" (se habilita a los 30 minutos)
    F->>A: terminarObservacion()
    A->>BD: UPDATE aplicaciones SET observacion_completada_en
    A->>A: fechaSiguienteSugerida()
    F-->>D: Notificación con el botón "Agendar siguiente aplicación"

    D->>F: Agenda la cita en la fecha sugerida
    F->>BD: SELECT citas que se cruzan (Cita::seCruza)
    alt Hay choque de horario
        F-->>D: Error con los horarios libres del día
    else Horario libre
        F->>BD: INSERT INTO citas
        BD->>T: BEFORE INSERT trg_citas_sin_choque_insert
        T->>BD: Revisa de nuevo el choque (por si dos personas agendan a la vez)
        BD-->>F: Cita guardada
    end
```

**Puntos de seguridad en el flujo:**

1. Las reglas importantes se revisan dos veces: en el formulario, para dar un mensaje claro, y en la base con triggers y `CHECK`, por si alguien escribe directo en phpMyAdmin o dos personas guardan al mismo tiempo.
2. El usuario `mediagenda_app` no tiene `DELETE` sobre `aplicaciones`, `reacciones_sistemicas` ni `citas`: una dosis registrada no se puede borrar y una cita se cancela con motivo.
3. Cada aplicación, reacción y cita queda en la `auditoria` con el usuario que la registró.
