# Diagrama de secuencia · Registrar un prick test (HU-07)

Desde que la doctora abre el formulario hasta que la prueba queda guardada y auditada.

```mermaid
sequenceDiagram
    autonumber
    actor D as Doctora
    participant F as Formulario prick test<br/>(Filament)
    participant M as Middleware<br/>EstablecerUsuarioAuditoria
    participant P as PruebaCutanea<br/>(modelo)
    participant R as PruebaCutaneaResultado<br/>(modelo)
    participant BD as MariaDB
    participant T as Triggers de auditoría

    D->>F: Abre "Nuevo prick test" desde la ficha del paciente
    F->>BD: SELECT alérgenos activos del panel, por orden
    BD-->>F: Panel de alérgenos
    F->>BD: SELECT alergias vigentes del paciente
    BD-->>F: Alergias
    F-->>D: Formulario con el panel cargado y aviso rojo de alergias

    D->>F: Anota histamina, solución salina y la pápula de cada alérgeno
    D->>F: Presiona "Crear"
    F->>F: Valida campos obligatorios y rangos (0 a 50 mm)

    alt Datos inválidos
        F-->>D: Muestra los errores junto a cada campo
    else Datos válidos
        F->>M: Petición de Livewire con la sesión de la doctora
        M->>BD: SET @app_usuario_id = id de la doctora
        F->>P: create(paciente, fecha, controles, criterio)
        P->>P: esInterpretable()
        P->>BD: INSERT INTO pruebas_cutaneas
        BD->>BD: Verifica CHECK de controles y criterio
        BD->>T: AFTER INSERT
        T->>BD: INSERT INTO auditoria (usuario = @app_usuario_id)

        loop Por cada alérgeno del panel
            F->>R: create(alergeno, papula_mm, eritema_mm)
            R->>P: esPositivo(papula_mm)
            P-->>R: positivo o negativo
            R->>BD: INSERT INTO pruebas_cutaneas_resultados
            BD->>BD: Verifica CHECK de pápula y llave única (prueba, alérgeno)
            BD->>T: AFTER INSERT
            T->>BD: INSERT INTO auditoria
        end

        F-->>D: Abre la vista de la prueba con los positivos primero
    end
```

**Puntos de seguridad en el flujo:**

1. El usuario de base de datos de la aplicación (`mediagenda_app`) solo puede hacer `SELECT`, `INSERT` y `UPDATE` en estas tablas: un resultado guardado no se puede borrar.
2. Las restricciones `CHECK` repiten en la base las validaciones del formulario, por si alguien escribe directo en phpMyAdmin.
3. La auditoría la escriben los triggers con los permisos del usuario migrador; la aplicación no puede modificarla.
