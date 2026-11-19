# Diagrama entidad-relación · Base `mediagenda` (MariaDB)

Tablas del Sprint 1 tal como las crean las migraciones. `PK` = llave primaria, `FK` = llave foránea, `UK` = único. Los comentarios indican la restricción `CHECK` o la regla de la columna. Todas las llaves foráneas usan `ON DELETE RESTRICT`.

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

## Seguridad en la base

| Objeto | Qué hace |
|---|---|
| Usuario `mediagenda_migrador` | Crea y modifica la estructura. No puede dar permisos. |
| Usuario `mediagenda_app` | `SELECT`, `INSERT`, `UPDATE` en las tablas clínicas; **sin `DELETE`** ni cambios de estructura. Solo `SELECT` en `cie10` y `auditoria`. |
| 24 triggers `trg_<tabla>_auditoria_*` | Copian cada `INSERT`, `UPDATE` y `DELETE` de 8 tablas a `auditoria`, con el usuario de `@app_usuario_id`. |
| `trg_auditoria_sin_cambios` y `trg_auditoria_sin_borrado` | Impiden modificar o borrar la auditoría, incluso al migrador. |
| 28 restricciones `CHECK` | Repiten en la base las reglas del formulario (rangos, listas de valores, formato del CI). |

La tabla `auditoria` no tiene llave foránea hacia `users` a propósito: la bitácora no debe depender de que el usuario siga existiendo. Los scripts de usuarios y permisos están en `database/sql/`.
