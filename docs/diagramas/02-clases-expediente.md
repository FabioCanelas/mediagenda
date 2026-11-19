# Diagrama de clases · Expediente del paciente

Modelos de Laravel del Sprint 1 (`app/Models`). Los métodos son los que contienen reglas del negocio; los campos de control (`created_at`, `updated_at`, `deleted_at`, `created_by`, `updated_by`) se omiten para que el diagrama se lea mejor.

```mermaid
classDiagram
    direction LR

    class User {
        +int id
        +string name
        +string email
        +Rol rol
        +bool activo
        -string password
        -string app_authentication_secret
        +canAccessPanel(Panel panel) bool
        +esDoctora() bool
    }

    class Paciente {
        +uuid id
        +string ci
        +string ci_complemento
        +Departamento ci_expedido
        +string nombres
        +string apellido_paterno
        +string apellido_materno
        +date fecha_nacimiento
        +Sexo sexo
        +string celular
        +string tutor_nombre
        +nombreCompleto() string
        +ciCompleto() string
        +edad() int
        +esMenorDeEdad() bool
        +alergiasVigentes() Collection
    }

    class HistoriaClinica {
        +uuid id
        +text padecimiento_actual
        +text antecedentes_heredofamiliares
        +text antecedentes_personales_patologicos
        +text antecedentes_personales_no_patologicos
        +text interrogatorio_aparatos_sistemas
        +int presion_sistolica
        +int presion_diastolica
        +decimal temperatura
        +decimal peso_kg
        +decimal talla_cm
        +text pronostico
        +text indicacion_terapeutica
        +imc() float
    }

    class Diagnostico {
        +uuid id
        +string cie10_codigo
        +string descripcion
        +TipoDiagnostico tipo
        +tieneCodigo() bool
    }

    class Cie10 {
        +string codigo
        +string descripcion
        +bool activo
    }

    class Alergia {
        +uuid id
        +string sustancia
        +CategoriaAlergia categoria
        +EstadoAlergia estado
        +CriticidadAlergia criticidad
        +GravedadReaccion gravedad
        +text reaccion
        +estaVigente() bool
    }

    class Alergeno {
        +int id
        +string nombre
        +string nombre_cientifico
        +GrupoAlergeno grupo
        +int orden
        +bool activo
        +delPanel() Builder
    }

    class PruebaCutanea {
        +uuid id
        +date fecha
        +decimal control_positivo_mm
        +decimal control_negativo_mm
        +decimal criterio_positividad_mm
        +int minutos_lectura
        +bool interpretable
        +esInterpretable() bool
        +esPositivo(float papulaMm) bool
        +motivoNoInterpretable() string
    }

    class PruebaCutaneaResultado {
        +int id
        +decimal papula_mm
        +decimal eritema_mm
        +bool positivo
    }

    class RegistroAuditoria {
        +int id
        +string tabla
        +string registro_id
        +string accion
        +json datos_antes
        +json datos_despues
        +datetime realizado_en
        +cambios() array
    }

    class Rol {
        <<enumeration>>
        Doctora
        Recepcion
    }

    class EstadoAlergia {
        <<enumeration>>
        Sospechada
        Confirmada
        Descartada
    }

    Paciente "1" -- "0..1" HistoriaClinica : tiene
    HistoriaClinica "1" *-- "0..*" Diagnostico : contiene
    Diagnostico "0..*" --> "0..1" Cie10 : se codifica con
    Paciente "1" -- "0..*" Alergia : tiene
    Paciente "1" -- "0..*" PruebaCutanea : se realiza
    PruebaCutanea "1" *-- "1..*" PruebaCutaneaResultado : resultados
    PruebaCutaneaResultado "0..*" --> "1" Alergeno : mide
    User "1" -- "0..*" PruebaCutanea : realiza
    RegistroAuditoria "0..*" --> "0..1" User : hecho por
    User --> Rol
    Alergia --> EstadoAlergia
```

**Reglas que viven en los modelos:**

- `PruebaCutanea::esInterpretable()`: la histamina debe reaccionar (pápula ≥ criterio) y la solución salina no (pápula < criterio).
- `PruebaCutanea::esPositivo()`: la pápula del alérgeno supera al control negativo en el criterio o más (3 mm por defecto).
- `PruebaCutaneaResultado` calcula `positivo` al guardarse; si cambian los controles, la prueba recalcula todos sus resultados.
- `RegistroAuditoria` no tiene métodos para escribir: la tabla la llenan los triggers de la base.
