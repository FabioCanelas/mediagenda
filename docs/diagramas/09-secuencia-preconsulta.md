# Diagrama de secuencia · Pre-consulta en tablet (HU-05)

Desde que la recepción prepara la tablet hasta que la doctora pasa las respuestas a la historia clínica.

```mermaid
sequenceDiagram
    autonumber
    actor R as Recepcionista
    actor P as Paciente
    actor D as Doctora
    participant A as Panel MediAgenda<br/>(Filament)
    participant T as Tablet<br/>(FormularioPreconsulta)
    participant M as Preconsulta<br/>(modelo)
    participant BD as MariaDB
    participant TR as Triggers

    R->>A: "Preparar tablet" en la cita de hoy o en la ficha
    A->>M: preparar(paciente, cita)
    M->>BD: UPDATE preconsultas pendientes del paciente → descartada
    M->>M: token = 48 caracteres aleatorios
    M->>BD: INSERT preconsultas (token_hash = SHA-256, expira_en = +3 h)
    BD->>TR: AFTER INSERT (auditoría sin token_hash)
    A-->>R: Código QR con el enlace (el token solo se muestra esta vez)

    P->>T: Escanea el QR en la tablet
    T->>T: Si había una sesión del personal, la cierra
    T->>M: vigentePorToken(token)
    M->>BD: SELECT por token_hash, estado pendiente y sin vencer
    alt Enlace usado, vencido o inventado
        T-->>P: "Este enlace ya no sirve"
    else Enlace vigente
        T-->>P: "Hola, (nombre)" y el formulario
        P->>T: Responde y presiona "Enviar respuestas"
        T->>T: Valida obligatorias (motivo, desde cuándo, alergia a medicamentos, tabaco, celular)
        alt Faltan respuestas
            T-->>P: Marca las preguntas que faltan
        else Completo
            T->>M: enviar(respuestas)
            M->>BD: UPDATE preconsultas SET estado = enviada
            BD->>TR: BEFORE UPDATE trg_preconsultas_flujo
            TR->>TR: ¿El enlace venció? Entonces SIGNAL
            BD->>BD: CHECK chk_preconsultas_envio (motivo y alergia obligatorios)
            T-->>P: "¡Gracias! Devuelva la tablet en recepción"
        end
    end

    D->>A: Abre la ficha (o ve "Pre-consultas por revisar" en el panel de inicio)
    A->>M: propuestaParaHistoria()
    A-->>D: Texto propuesto para cada apartado de la NOM-004
    D->>A: Corrige y presiona "Pasar a la historia clínica"
    A->>M: pasarAHistoria(doctora, datos)
    M->>BD: BEGIN
    M->>BD: INSERT o UPDATE historias_clinicas (agrega al final si ya hay texto)
    opt Refirió alergia a un medicamento
        M->>BD: INSERT alergias (estado sospechada)
    end
    opt Cambió su celular
        M->>BD: UPDATE pacientes SET celular
    end
    M->>BD: UPDATE preconsultas SET estado = revisada
    M->>BD: COMMIT
    BD->>TR: AFTER INSERT/UPDATE (auditoría con el usuario de la doctora)
    A-->>D: Abre la historia clínica para completar el resto
```

**Puntos de seguridad en el flujo:**

1. El paciente no tiene usuario: el enlace es el permiso. Sirve una sola vez, vence en 3 horas, la ruta acepta solo tokens de 48 caracteres y tiene un límite de 30 peticiones por minuto.
2. La base guarda solo el hash SHA-256 del token, y la auditoría nunca lo copia. Quien lea la tabla no puede abrir el formulario.
3. La recepción prepara la tablet, pero no puede ver las respuestas: solo la doctora las revisa.
4. Una pre-consulta revisada o descartada ya no se modifica: lo impide el trigger `trg_preconsultas_flujo`.
