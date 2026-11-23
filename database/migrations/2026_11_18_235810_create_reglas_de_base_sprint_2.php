<?php

use App\Support\BaseDeDatos\Auditoria;
use App\Support\BaseDeDatos\Restricciones;
use App\Support\BaseDeDatos\TriggersPostgres;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Reglas del Sprint 2 que viven en la base de datos:
 *  - Triggers que protegen las notas cerradas, los anexos, los frascos y la agenda.
 *  - Vista v_proxima_aplicacion (la usa la aplicación) y v_pacientes_atrasados (para consultas en phpMyAdmin).
 *  - Triggers de auditoría para las tablas nuevas.
 */
return new class extends Migration
{
    /**
     * @var array<int, string>
     */
    private array $tablasAuditadas = [
        'notas_evolucion',
        'notas_evolucion_anexos',
        'inmunoterapias',
        'inmunoterapia_alergeno',
        'esquema_dosis',
        'frascos',
        'aplicaciones',
        'reacciones_sistemicas',
        'reglas_atraso',
        'citas',
    ];

    /**
     * Trigger => tabla.
     *
     * @var array<string, string>
     */
    private array $triggers = [
        'trg_notas_evolucion_bloqueo_update' => 'notas_evolucion',
        'trg_notas_evolucion_bloqueo_delete' => 'notas_evolucion',
        'trg_anexos_solo_nota_cerrada' => 'notas_evolucion_anexos',
        'trg_aplicaciones_validar_frasco' => 'aplicaciones',
        'trg_citas_sin_choque_insert' => 'citas',
        'trg_citas_sin_choque_update' => 'citas',
    ];

    public function up(): void
    {
        $this->crearVistaProximaAplicacion();

        if (Restricciones::esMysql()) {
            $this->crearTriggersDeProteccion();
        }

        if (Restricciones::esPostgres()) {
            $this->crearTriggersDeProteccionPostgres();
        }

        if (Restricciones::soportado()) {
            $this->crearVistaPacientesAtrasados();
        }

        foreach ($this->tablasAuditadas as $tabla) {
            Auditoria::crearTriggers($tabla);
        }
    }

    public function down(): void
    {
        foreach ($this->tablasAuditadas as $tabla) {
            Auditoria::eliminarTriggers($tabla);
        }

        foreach ($this->triggers as $trigger => $tabla) {
            if (Restricciones::esMysql()) {
                DB::unprepared("DROP TRIGGER IF EXISTS `{$trigger}`");
            }

            if (Restricciones::esPostgres()) {
                TriggersPostgres::eliminar($tabla, $trigger);
            }
        }

        if (Restricciones::soportado()) {
            DB::statement('DROP VIEW IF EXISTS v_pacientes_atrasados');
        }

        DB::statement('DROP VIEW IF EXISTS v_proxima_aplicacion');
    }

    /**
     * Las mismas reglas que crearTriggersDeProteccion(), escritas en PL/pgSQL.
     */
    private function crearTriggersDeProteccionPostgres(): void
    {
        TriggersPostgres::antesDe('UPDATE', 'notas_evolucion', 'trg_notas_evolucion_bloqueo_update', <<<'SQL'
            IF OLD.estado = 'cerrada' THEN
                RAISE EXCEPTION 'Una nota de evolucion cerrada no se puede modificar' USING ERRCODE = '45000';
            END IF;
            SQL);

        TriggersPostgres::antesDe('DELETE', 'notas_evolucion', 'trg_notas_evolucion_bloqueo_delete', <<<'SQL'
            IF OLD.estado = 'cerrada' THEN
                RAISE EXCEPTION 'Una nota de evolucion cerrada no se puede borrar' USING ERRCODE = '45000';
            END IF;
            SQL);

        TriggersPostgres::antesDe('INSERT', 'notas_evolucion_anexos', 'trg_anexos_solo_nota_cerrada', <<<'SQL'
            IF (SELECT estado FROM notas_evolucion WHERE id = NEW.nota_evolucion_id) IS DISTINCT FROM 'cerrada' THEN
                RAISE EXCEPTION 'Solo se agregan anexos a notas cerradas' USING ERRCODE = '45000';
            END IF;
            SQL);

        TriggersPostgres::antesDe('INSERT', 'aplicaciones', 'trg_aplicaciones_validar_frasco', <<<'SQL'
            DECLARE
                frasco RECORD;
            BEGIN
                SELECT inmunoterapia_id, fecha_vencimiento, activo INTO frasco
                  FROM frascos WHERE id = NEW.frasco_id;

                IF frasco.inmunoterapia_id IS DISTINCT FROM NEW.inmunoterapia_id THEN
                    RAISE EXCEPTION 'El frasco pertenece a otro tratamiento' USING ERRCODE = '45000';
                END IF;

                IF frasco.fecha_vencimiento < NEW.aplicada_en::date THEN
                    RAISE EXCEPTION 'No se puede aplicar un frasco vencido' USING ERRCODE = '45000';
                END IF;

                IF NOT frasco.activo THEN
                    RAISE EXCEPTION 'El frasco esta dado de baja' USING ERRCODE = '45000';
                END IF;
            END;
            SQL);

        foreach (['insert' => 'INSERT', 'update' => 'UPDATE'] as $sufijo => $evento) {
            $excluirMisma = $evento === 'UPDATE' ? 'AND c.id <> NEW.id' : '';

            TriggersPostgres::antesDe($evento, 'citas', "trg_citas_sin_choque_{$sufijo}", <<<SQL
                IF NEW.estado NOT IN ('cancelada', 'no_asistio') AND EXISTS (
                    SELECT 1 FROM citas c
                     WHERE c.estado NOT IN ('cancelada', 'no_asistio')
                       AND c.inicio < NEW.fin
                       AND c.fin > NEW.inicio
                       {$excluirMisma}
                ) THEN
                    RAISE EXCEPTION 'Choque de horario con otra cita' USING ERRCODE = '45000';
                END IF;
                SQL);
        }
    }

    private function crearTriggersDeProteccion(): void
    {
        // HU-06: una nota cerrada no se modifica ni se borra, ni siquiera desde phpMyAdmin.
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER `trg_notas_evolucion_bloqueo_update` BEFORE UPDATE ON `notas_evolucion` FOR EACH ROW
            BEGIN
                IF OLD.estado = 'cerrada' THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Una nota de evolucion cerrada no se puede modificar';
                END IF;
            END
            SQL);

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER `trg_notas_evolucion_bloqueo_delete` BEFORE DELETE ON `notas_evolucion` FOR EACH ROW
            BEGIN
                IF OLD.estado = 'cerrada' THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Una nota de evolucion cerrada no se puede borrar';
                END IF;
            END
            SQL);

        // HU-06: los anexos solo corrigen notas cerradas; un borrador se edita directamente.
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER `trg_anexos_solo_nota_cerrada` BEFORE INSERT ON `notas_evolucion_anexos` FOR EACH ROW
            BEGIN
                IF (SELECT estado FROM notas_evolucion WHERE id = NEW.nota_evolucion_id) <> 'cerrada' THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Solo se agregan anexos a notas cerradas';
                END IF;
            END
            SQL);

        // HU-10 y HU-11: no se aplica un frasco vencido, dado de baja o de otro tratamiento.
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER `trg_aplicaciones_validar_frasco` BEFORE INSERT ON `aplicaciones` FOR EACH ROW
            BEGIN
                DECLARE v_inmunoterapia CHAR(36);
                DECLARE v_vencimiento DATE;
                DECLARE v_activo TINYINT;

                SELECT inmunoterapia_id, fecha_vencimiento, activo
                  INTO v_inmunoterapia, v_vencimiento, v_activo
                  FROM frascos WHERE id = NEW.frasco_id;

                IF v_inmunoterapia <> NEW.inmunoterapia_id THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El frasco pertenece a otro tratamiento';
                END IF;

                IF v_vencimiento < DATE(NEW.aplicada_en) THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No se puede aplicar un frasco vencido';
                END IF;

                IF v_activo = 0 THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El frasco esta dado de baja';
                END IF;
            END
            SQL);

        // HU-14: dos citas vigentes no se pueden cruzar.
        foreach (['insert' => 'INSERT', 'update' => 'UPDATE'] as $sufijo => $evento) {
            $excluirMisma = $evento === 'UPDATE' ? 'AND c.id <> NEW.id' : '';

            DB::unprepared(<<<SQL
                CREATE TRIGGER `trg_citas_sin_choque_{$sufijo}` BEFORE {$evento} ON `citas` FOR EACH ROW
                BEGIN
                    IF NEW.estado NOT IN ('cancelada', 'no_asistio') AND EXISTS (
                        SELECT 1 FROM citas c
                         WHERE c.estado NOT IN ('cancelada', 'no_asistio')
                           AND c.inicio < NEW.fin
                           AND c.fin > NEW.inicio
                           {$excluirMisma}
                    ) THEN
                        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Choque de horario con otra cita';
                    END IF;
                END
                SQL);
        }
    }

    /**
     * Próxima aplicación de cada inmunoterapia subcutánea activa: la fecha de inicio si no
     * tiene aplicaciones, o la última aplicación más el intervalo de su paso del esquema
     * (o el de mantenimiento).
     */
    private function crearVistaProximaAplicacion(): void
    {
        $sumarDias = fn (string $fecha, string $dias): string => match (true) {
            Restricciones::esMysql() => "DATE_ADD(DATE({$fecha}), INTERVAL {$dias} DAY)",
            Restricciones::esPostgres() => "(({$fecha})::date + ({$dias})::integer)",
            default => "date({$fecha}, '+' || {$dias} || ' days')",
        };

        $mantenimiento = $sumarDias('ua.aplicada_en', 'COALESCE(i.intervalo_mantenimiento_dias, 28)');
        $inicio = $sumarDias('ua.aplicada_en', 'COALESCE(ed.intervalo_dias, 7)');

        DB::statement(<<<SQL
            CREATE VIEW v_proxima_aplicacion AS
            SELECT i.id AS inmunoterapia_id,
                   i.paciente_id,
                   i.fase,
                   ua.aplicada_en AS ultima_aplicacion,
                   CASE
                       WHEN ua.id IS NULL THEN i.fecha_inicio
                       WHEN i.fase = 'mantenimiento' THEN {$mantenimiento}
                       ELSE {$inicio}
                   END AS fecha_prevista
              FROM inmunoterapias i
              LEFT JOIN aplicaciones ua ON ua.id = (
                    SELECT a.id FROM aplicaciones a
                     WHERE a.inmunoterapia_id = i.id
                     ORDER BY a.aplicada_en DESC
                     LIMIT 1)
              LEFT JOIN esquema_dosis ed ON ed.id = ua.esquema_dosis_id
             WHERE i.estado = 'activa'
               AND i.via = 'subcutanea'
            SQL);
    }

    /**
     * Pacientes con la vacuna atrasada y cuántos días. Pensada para consultar en phpMyAdmin.
     */
    private function crearVistaPacientesAtrasados(): void
    {
        if (Restricciones::esPostgres()) {
            DB::statement(<<<'SQL'
                CREATE VIEW v_pacientes_atrasados AS
                SELECT v.inmunoterapia_id,
                       v.paciente_id,
                       p.ci,
                       CONCAT_WS(' ', p.nombres, p.apellido_paterno, p.apellido_materno) AS paciente,
                       v.fase,
                       v.ultima_aplicacion,
                       v.fecha_prevista,
                       (CURRENT_DATE - v.fecha_prevista) AS dias_atraso
                  FROM v_proxima_aplicacion v
                  JOIN pacientes p ON p.id = v.paciente_id
                 WHERE v.fecha_prevista < CURRENT_DATE
                   AND p.deleted_at IS NULL
                SQL);

            return;
        }

        DB::statement(<<<'SQL'
            CREATE VIEW v_pacientes_atrasados AS
            SELECT v.inmunoterapia_id,
                   v.paciente_id,
                   p.ci,
                   CONCAT_WS(' ', p.nombres, p.apellido_paterno, p.apellido_materno) AS paciente,
                   v.fase,
                   v.ultima_aplicacion,
                   v.fecha_prevista,
                   DATEDIFF(CURDATE(), v.fecha_prevista) AS dias_atraso
              FROM v_proxima_aplicacion v
              JOIN pacientes p ON p.id = v.paciente_id
             WHERE v.fecha_prevista < CURDATE()
               AND p.deleted_at IS NULL
            SQL);
    }
};
