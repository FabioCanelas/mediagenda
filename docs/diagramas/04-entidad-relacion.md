# Diagrama entidad-relación · Base `mediagenda` (MariaDB)

Tablas tal como las crean las migraciones. `PK` = llave primaria, `FK` = llave foránea, `UK` = único. Los comentarios indican la restricción `CHECK` o la regla de la columna. Todas las llaves foráneas usan `ON DELETE RESTRICT`.

## Sprint 1 · Expediente del paciente

```mermaid
erDiagram
    users ||--o{ pacientes : "registra"
    pacientes ||--o| historias_clinicas : "tiene"
    historias_clinicas ||--o{ diagnosticos : "contiene"
    cie10 |o--o{ diagnosticos : "codifica"
    pacientes ||--o{ alergias : "tiene"
    pacientes ||--o{ pruebas_cutaneas : "se realiza"
    users |o--o{ pruebas_cutaneas : "realiza"
    pruebas_cutaneas ||--|{ pruebas_cutaneas_resultados : "tiene"
    alergenos ||--o{ pruebas_cutaneas_resultados : "se mide en"

    users {
        bigint id PK
        varchar name
        varchar email UK
        varchar rol "CHECK doctora o recepcion"
        tinyint activo "nace en 0"
        varchar password "hash bcrypt"
        text app_authentication_secret "cifrado por la app"
    }

    pacientes {
        char36 id PK "UUID"
        varchar ci UK "CHECK solo digitos, 5 a 12"
        varchar ci_complemento UK "vacio si no tiene"
        char2 ci_expedido "CHECK departamento"
        varchar nombres
        varchar apellido_paterno "CHECK al menos un apellido"
        varchar apellido_materno
        date fecha_nacimiento "CHECK desde 1900"
        char1 sexo "CHECK F o M"
        varchar celular
        varchar tutor_nombre "obligatorio si es menor"
        bigint created_by FK
        timestamp deleted_at "borrado logico"
    }

    historias_clinicas {
        char36 id PK
        char36 paciente_id FK, UK "una por paciente"
        text padecimiento_actual
        text antecedentes_heredofamiliares
        text antecedentes_personales_patologicos
        text antecedentes_personales_no_patologicos
        smallint presion_sistolica "CHECK 50 a 260"
        decimal temperatura "CHECK 30 a 45"
        decimal peso_kg "CHECK 0.5 a 400"
        tinyint saturacion_oxigeno "CHECK 50 a 100"
        text indicacion_terapeutica
    }

    diagnosticos {
        char36 id PK
        char36 historia_clinica_id FK
        varchar cie10_codigo FK "nulo si es texto libre"
        varchar descripcion "CHECK codigo o descripcion"
        varchar tipo "CHECK principal o secundario"
        timestamp deleted_at
    }

    cie10 {
        varchar codigo PK
        varchar descripcion
        tinyint activo
    }

    alergias {
        char36 id PK
        char36 paciente_id FK
        varchar sustancia
        varchar categoria "CHECK medicamento, alimento..."
        varchar estado "CHECK sospechada, confirmada, descartada"
        varchar criticidad "CHECK baja, alta, no_evaluable"
        varchar gravedad "CHECK leve, moderada, grave"
        timestamp deleted_at
    }

    alergenos {
        bigint id PK
        varchar nombre UK
        varchar nombre_cientifico
        varchar grupo "CHECK acaros, hongos..."
        smallint orden
        tinyint activo
    }

    pruebas_cutaneas {
        char36 id PK
        char36 paciente_id FK
        date fecha
        decimal control_positivo_mm "CHECK 0 a 50"
        decimal control_negativo_mm "CHECK 0 a 50"
        decimal criterio_positividad_mm "CHECK 1 a 10"
        tinyint interpretable "lo calcula la app"
        bigint realizado_por FK
        timestamp deleted_at
    }

    pruebas_cutaneas_resultados {
        bigint id PK
        char36 prueba_cutanea_id FK, UK
        bigint alergeno_id FK, UK
        decimal papula_mm "CHECK 0 a 50"
        decimal eritema_mm "CHECK 0 a 100"
        tinyint positivo "lo calcula la app"
    }

    auditoria {
        bigint id PK
        varchar tabla
        varchar registro_id
        varchar accion "INSERT, UPDATE, BORRADO, DELETE"
        json datos_antes
        json datos_despues
        bigint usuario_id "sin FK a proposito"
        timestamp realizado_en
    }
```

## Sprint 2 · Notas de evolución, inmunoterapia y agenda

`pacientes`, `users` y `alergenos` son las tablas del Sprint 1; aquí solo se muestran sus llaves.

```mermaid
erDiagram
    pacientes ||--o{ notas_evolucion : "tiene"
    notas_evolucion ||--o{ notas_evolucion_anexos : "se corrige con"
    users |o--o{ notas_evolucion : "cierra"
    pacientes ||--o{ inmunoterapias : "recibe"
    inmunoterapias ||--|{ inmunoterapia_alergeno : "incluye"
    alergenos ||--o{ inmunoterapia_alergeno : "forma parte de"
    inmunoterapias ||--o{ esquema_dosis : "define"
    inmunoterapias ||--o{ frascos : "usa"
    inmunoterapias ||--o{ aplicaciones : "registra"
    frascos ||--o{ aplicaciones : "se aplica en"
    esquema_dosis |o--o{ aplicaciones : "corresponde a"
    users |o--o{ aplicaciones : "aplica"
    aplicaciones ||--o| reacciones_sistemicas : "puede causar"
    pacientes ||--o{ citas : "agenda"

    pacientes {
        char36 id PK
    }

    users {
        bigint id PK
    }

    alergenos {
        bigint id PK
    }

    notas_evolucion {
        char36 id PK
        char36 paciente_id FK
        date fecha
        text evolucion
        smallint presion_sistolica "CHECK 50 a 260"
        decimal temperatura "CHECK 30 a 45"
        tinyint saturacion_oxigeno "CHECK 50 a 100"
        text diagnosticos
        text tratamiento
        varchar estado "CHECK borrador o cerrada"
        timestamp cerrada_en "CHECK obligatorio si cerrada"
        bigint cerrada_por FK
    }

    notas_evolucion_anexos {
        char36 id PK
        char36 nota_evolucion_id FK
        text texto
        bigint created_by FK
        timestamp created_at "sin updated_at"
    }

    inmunoterapias {
        char36 id PK
        char36 paciente_id FK
        varchar via "CHECK subcutanea o sublingual"
        varchar extracto
        date fecha_inicio
        varchar fase "CHECK inicio o mantenimiento"
        decimal dosis_mantenimiento_ml "CHECK 0.01 a 1"
        smallint intervalo_mantenimiento_dias "CHECK 7 a 90"
        varchar estado "CHECK activa, suspendida, finalizada"
    }

    inmunoterapia_alergeno {
        bigint id PK
        char36 inmunoterapia_id FK, UK
        bigint alergeno_id FK, UK
    }

    esquema_dosis {
        bigint id PK
        char36 inmunoterapia_id FK, UK
        smallint paso UK "CHECK desde 1"
        varchar dilucion "CHECK 1:10000 a 1:1"
        decimal volumen_ml "CHECK 0.01 a 1"
        smallint intervalo_dias "CHECK 1 a 60"
    }

    frascos {
        char36 id PK
        char36 inmunoterapia_id FK
        varchar dilucion "CHECK 1:10000 a 1:1"
        varchar color "CHECK segun la dilucion"
        varchar lote
        date fecha_preparacion
        date fecha_vencimiento "CHECK no antes de la preparacion"
        tinyint activo "baja logica"
    }

    aplicaciones {
        char36 id PK
        char36 inmunoterapia_id FK
        char36 frasco_id FK "trigger: vigente y del mismo tratamiento"
        bigint esquema_dosis_id FK "nulo en mantenimiento"
        varchar fase "CHECK inicio o mantenimiento"
        datetime aplicada_en
        decimal volumen_ml "CHECK 0.01 a 1"
        tinyint dosis_difiere_esquema
        varchar brazo "CHECK izquierdo o derecho"
        varchar reaccion_local "CHECK ninguna a grande"
        datetime observacion_hasta "CHECK desde aplicada_en"
        datetime observacion_completada_en
        bigint aplicada_por FK
    }

    reacciones_sistemicas {
        char36 id PK
        char36 aplicacion_id FK, UK "una por aplicacion"
        tinyint grado "CHECK WAO 1 a 5"
        smallint minutos_desde_aplicacion "CHECK 0 a 1440"
        text sintomas
        tinyint epinefrina
    }

    reglas_atraso {
        bigint id PK
        varchar fase "CHECK inicio o mantenimiento"
        smallint dias_desde "CHECK desde 1"
        smallint dias_hasta "CHECK nulo o mayor que desde"
        varchar accion
        tinyint activo
    }

    citas {
        char36 id PK
        char36 paciente_id FK
        datetime inicio "trigger: sin choque de horario"
        datetime fin "CHECK despues del inicio"
        varchar tipo "CHECK primera consulta, control..."
        varchar estado "CHECK programada a cancelada"
        varchar motivo_cancelacion "CHECK obligatorio si cancelada"
    }
```

**Vistas:**

- `v_proxima_aplicacion`: por cada inmunoterapia activa y subcutánea, la última aplicación y la fecha prevista de la siguiente (intervalo del paso o del mantenimiento).
- `v_pacientes_atrasados`: las de `v_proxima_aplicacion` cuya fecha prevista ya pasó, con los días de atraso (`DATEDIFF`).

## Seguridad en la base

| Objeto | Qué hace |
|---|---|
| Usuario `mediagenda_migrador` | Crea y modifica la estructura. No puede dar permisos. |
| Usuario `mediagenda_app` | `SELECT`, `INSERT`, `UPDATE` en las tablas clínicas; **sin `DELETE`** ni cambios de estructura. Solo `SELECT` en `cie10`, `auditoria` y las vistas. En los anexos solo `SELECT` e `INSERT`. Entre las tablas clínicas solo puede borrar filas de `esquema_dosis` e `inmunoterapia_alergeno`, protegidas por las llaves foráneas y auditadas (las tablas internas de Laravel, como `sessions` y `cache`, sí admiten `DELETE`). |
| 54 triggers `trg_<tabla>_auditoria_*` | Copian cada `INSERT`, `UPDATE` y `DELETE` de 18 tablas a `auditoria`, con el usuario de `@app_usuario_id`. |
| `trg_auditoria_sin_cambios` y `trg_auditoria_sin_borrado` | Impiden modificar o borrar la auditoría, incluso al migrador. |
| `trg_notas_evolucion_bloqueo_update` y `_delete` | Una nota cerrada no se modifica ni se borra. |
| `trg_anexos_solo_nota_cerrada` | Los anexos solo se agregan a notas cerradas. |
| `trg_aplicaciones_validar_frasco` | Rechaza frascos vencidos, dados de baja o de otro tratamiento. |
| `trg_citas_sin_choque_insert` y `_update` | Rechazan citas vigentes que se cruzan con otra. |
| 65 restricciones `CHECK` | Repiten en la base las reglas del formulario (rangos, listas de valores, formato del CI, color del frasco según la dilución). |

El script `database/sql/03_verificar_reglas_sprint_2.sql` prueba estas reglas con el usuario de la aplicación.

La tabla `auditoria` no tiene llave foránea hacia `users` a propósito: la bitácora no debe depender de que el usuario siga existiendo. Los scripts de usuarios y permisos están en `database/sql/`.
