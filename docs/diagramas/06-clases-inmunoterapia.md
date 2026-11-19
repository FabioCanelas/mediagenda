# Diagrama de clases · Inmunoterapia, notas y agenda

Modelos de Laravel del Sprint 2 (`app/Models`). Se muestran los métodos que contienen reglas del negocio; los campos de control (`created_at`, `updated_at`, `created_by`, `updated_by`) se omiten.

```mermaid
classDiagram
    direction LR

    class Paciente {
        +uuid id
        +string nombre_completo
        +notasEvolucion() HasMany
        +inmunoterapias() HasMany
        +citas() HasMany
    }

    class NotaEvolucion {
        +uuid id
        +date fecha
        +text evolucion
        +signos vitales
        +EstadoNota estado
        +datetime cerrada_en
        +estaCerrada() bool
        +cerrar(User doctora) void
    }

    class NotaEvolucionAnexo {
        +uuid id
        +text texto
        +datetime created_at
    }

    class Inmunoterapia {
        +uuid id
        +ViaInmunoterapia via
        +string extracto
        +FaseInmunoterapia fase
        +EstadoInmunoterapia estado
        +decimal dosis_mantenimiento_ml
        +int intervalo_mantenimiento_dias
        +admiteAplicaciones() bool
        +dosisEsperada() array
        +diasDeAtraso() int
        +reglaDeAtraso() ReglaAtraso
        +ultimaReaccionSistemica() ReaccionSistemica
        +esquemaConvencional()$ array
    }

    class EsquemaDosis {
        +int id
        +int paso
        +Dilucion dilucion
        +decimal volumen_ml
        +int intervalo_dias
        +descripcion() string
    }

    class Frasco {
        +uuid id
        +Dilucion dilucion
        +ColorFrasco color
        +string lote
        +date fecha_vencimiento
        +bool activo
        +estaVencido() bool
        +sePuedeUsar() bool
        +etiqueta() string
    }

    class Aplicacion {
        +uuid id
        +datetime aplicada_en
        +decimal volumen_ml
        +bool dosis_difiere_esquema
        +Brazo brazo
        +ReaccionLocal reaccion_local
        +datetime observacion_hasta
        +datetime observacion_completada_en
        +MINUTOS_OBSERVACION$ = 30
        +difiereDelEsquema() bool
        +puedeTerminarObservacion() bool
        +terminarObservacion() void
        +fechaSiguienteSugerida() Carbon
    }

    class ReaccionSistemica {
        +uuid id
        +GradoWao grado
        +int minutos_desde_aplicacion
        +text sintomas
        +bool epinefrina
        +resumen() string
    }

    class ReglaAtraso {
        +int id
        +FaseInmunoterapia fase
        +int dias_desde
        +int dias_hasta
        +string accion
        +paraAtraso(fase, dias)$ ReglaAtraso
        +tramo() string
    }

    class ProximaAplicacion {
        <<vista v_proxima_aplicacion>>
        +uuid inmunoterapia_id
        +datetime ultima_aplicacion
        +date fecha_prevista
        +diasDeAtraso() int
    }

    class Cita {
        +uuid id
        +datetime inicio
        +datetime fin
        +TipoCita tipo
        +EstadoCita estado
        +string motivo_cancelacion
        +estaVigente() bool
        +seCruza(inicio, fin)$ bool
        +horariosLibres(dia, minutos)$ array
    }

    class Dilucion {
        <<enumeration>>
        DiezMil · plata
        Mil · verde
        Cien · azul
        Diez · amarillo
        Concentrado · rojo
        +color() ColorFrasco
    }

    Paciente "1" --> "*" NotaEvolucion
    NotaEvolucion "1" --> "*" NotaEvolucionAnexo : solo si está cerrada
    Paciente "1" --> "*" Inmunoterapia
    Paciente "1" --> "*" Cita
    Inmunoterapia "1" *-- "*" EsquemaDosis : pasos
    Inmunoterapia "1" --> "*" Frasco
    Inmunoterapia "1" --> "*" Aplicacion
    Inmunoterapia "1" --> "0..1" ProximaAplicacion
    Aplicacion "*" --> "1" Frasco
    Aplicacion "*" --> "0..1" EsquemaDosis
    Aplicacion "1" --> "0..1" ReaccionSistemica
    Inmunoterapia ..> ReglaAtraso : consulta según el atraso
    Frasco ..> Dilucion
    EsquemaDosis ..> Dilucion
```

**Reglas que viven en los modelos:**

- `Inmunoterapia::dosisEsperada()`: en la fase de inicio es el paso siguiente al último aplicado; en mantenimiento, la dosis de mantenimiento del frasco concentrado (1:1).
- `Inmunoterapia::esquemaConvencional()`: plantilla editable de 0.05 a 0.50 mL por frasco, una vez por semana; la doctora la ajusta para cada paciente.
- `Frasco` fija su color al guardarse según la dilución; `sePuedeUsar()` exige que esté activo y sin vencer.
- `Aplicacion` calcula al crearse el fin de la observación (30 minutos) y si la dosis difiere del esquema.
- `ProximaAplicacion` es un modelo de solo lectura sobre la vista `v_proxima_aplicacion`: la fecha prevista se calcula en la base.
- `Cita::seCruza()` ignora las citas canceladas y las de pacientes que no asistieron; `horariosLibres()` recorre la jornada (08:00 a 19:00) en tramos de 15 minutos.
