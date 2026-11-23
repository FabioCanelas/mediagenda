<?php

use App\Support\BaseDeDatos\Auditoria;
use App\Support\BaseDeDatos\Restricciones;
use App\Support\BaseDeDatos\TriggersPostgres;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Reglas del Sprint 3 que viven en la base de datos:
 *  - El archivo de un adjunto no se cambia (su huella SHA-256 lo identifica).
 *  - Un consentimiento emitido solo admite registrar la firma.
 *  - Una receta emitida no se modifica, ni sus medicamentos.
 *  - Los documentos emitidos no se modifican ni se borran, y su paciente coincide con el origen.
 *  - La pre-consulta sigue su flujo: pendiente → enviada → revisada (o descartada).
 *  - Triggers de auditoría para las tablas nuevas.
 */
return new class extends Migration
{
    /**
     * Tabla => columnas que no se copian a la auditoría.
     *
     * @var array<string, array<int, string>>
     */
    private array $tablasAuditadas = [
        'adjuntos' => [],
        'cuestionarios_act' => [],
        'consentimientos' => [],
        'recetas' => [],
        'receta_medicamentos' => [],
        'documentos_emitidos' => [],
        'preconsultas' => ['token_hash'],
    ];

    /**
     * Trigger => tabla.
     *
     * @var array<string, string>
     */
    private array $triggers = [
        'trg_adjuntos_archivo_inmutable' => 'adjuntos',
        'trg_consentimientos_solo_firma' => 'consentimientos',
        'trg_recetas_bloqueo_emitida' => 'recetas',
        'trg_receta_medicamentos_bloqueo_insert' => 'receta_medicamentos',
        'trg_receta_medicamentos_bloqueo_update' => 'receta_medicamentos',
        'trg_receta_medicamentos_bloqueo_delete' => 'receta_medicamentos',
        'trg_documentos_emitidos_validar' => 'documentos_emitidos',
        'trg_documentos_emitidos_sin_cambios' => 'documentos_emitidos',
        'trg_documentos_emitidos_sin_borrado' => 'documentos_emitidos',
        'trg_preconsultas_flujo' => 'preconsultas',
    ];

    public function up(): void
    {
        if (Restricciones::esMysql()) {
            $this->crearTriggersDeProteccion();
        }

        if (Restricciones::esPostgres()) {
            $this->crearTriggersDeProteccionPostgres();
        }

        foreach ($this->tablasAuditadas as $tabla => $excluir) {
            Auditoria::crearTriggers($tabla, $excluir);
        }
    }

    public function down(): void
    {
        foreach (array_keys($this->tablasAuditadas) as $tabla) {
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
    }

    /**
     * Las mismas reglas que crearTriggersDeProteccion(), escritas en PL/pgSQL.
     * El operador <=> de MariaDB (igualdad que admite NULL) es IS NOT DISTINCT FROM.
     */
    private function crearTriggersDeProteccionPostgres(): void
    {
        TriggersPostgres::antesDe('UPDATE', 'adjuntos', 'trg_adjuntos_archivo_inmutable', <<<'SQL'
            IF NEW.archivo IS DISTINCT FROM OLD.archivo OR NEW.sha256 IS DISTINCT FROM OLD.sha256
               OR NEW.paciente_id IS DISTINCT FROM OLD.paciente_id THEN
                RAISE EXCEPTION 'El archivo de un estudio adjunto no se puede reemplazar' USING ERRCODE = '45000';
            END IF;
            SQL);

        TriggersPostgres::antesDe('UPDATE', 'consentimientos', 'trg_consentimientos_solo_firma', <<<'SQL'
            IF NEW.paciente_id IS DISTINCT FROM OLD.paciente_id OR NEW.procedimiento IS DISTINCT FROM OLD.procedimiento
               OR NEW.fecha IS DISTINCT FROM OLD.fecha OR NEW.lugar IS DISTINCT FROM OLD.lugar
               OR NEW.firmante_nombre IS DISTINCT FROM OLD.firmante_nombre
               OR NEW.firmante_es_tutor IS DISTINCT FROM OLD.firmante_es_tutor THEN
                RAISE EXCEPTION 'Un consentimiento emitido no se modifica; solo se registra la firma' USING ERRCODE = '45000';
            END IF;

            IF OLD.estado = 'firmado' AND NEW.estado <> 'firmado' THEN
                RAISE EXCEPTION 'Un consentimiento firmado no vuelve a quedar pendiente' USING ERRCODE = '45000';
            END IF;
            SQL);

        TriggersPostgres::antesDe('UPDATE', 'recetas', 'trg_recetas_bloqueo_emitida', <<<'SQL'
            IF EXISTS (SELECT 1 FROM documentos_emitidos WHERE receta_id = OLD.id) THEN
                RAISE EXCEPTION 'Una receta emitida no se puede modificar' USING ERRCODE = '45000';
            END IF;
            SQL);

        foreach (['insert' => ['INSERT', 'NEW'], 'update' => ['UPDATE', 'OLD'], 'delete' => ['DELETE', 'OLD']] as $sufijo => [$evento, $fila]) {
            TriggersPostgres::antesDe($evento, 'receta_medicamentos', "trg_receta_medicamentos_bloqueo_{$sufijo}", <<<SQL
                IF EXISTS (SELECT 1 FROM documentos_emitidos WHERE receta_id = {$fila}.receta_id) THEN
                    RAISE EXCEPTION 'Los medicamentos de una receta emitida no se pueden cambiar' USING ERRCODE = '45000';
                END IF;
                SQL);
        }

        TriggersPostgres::antesDe('INSERT', 'documentos_emitidos', 'trg_documentos_emitidos_validar', <<<'SQL'
            IF NEW.receta_id IS NOT NULL
               AND (SELECT paciente_id FROM recetas WHERE id = NEW.receta_id) IS DISTINCT FROM NEW.paciente_id THEN
                RAISE EXCEPTION 'La receta es de otro paciente' USING ERRCODE = '45000';
            END IF;

            IF NEW.consentimiento_id IS NOT NULL
               AND (SELECT paciente_id FROM consentimientos WHERE id = NEW.consentimiento_id) IS DISTINCT FROM NEW.paciente_id THEN
                RAISE EXCEPTION 'El consentimiento es de otro paciente' USING ERRCODE = '45000';
            END IF;
            SQL);

        TriggersPostgres::antesDe('UPDATE', 'documentos_emitidos', 'trg_documentos_emitidos_sin_cambios', TriggersPostgres::error('Un documento emitido no se puede modificar'));
        TriggersPostgres::antesDe('DELETE', 'documentos_emitidos', 'trg_documentos_emitidos_sin_borrado', TriggersPostgres::error('Un documento emitido no se puede borrar'));

        TriggersPostgres::antesDe('UPDATE', 'preconsultas', 'trg_preconsultas_flujo', <<<'SQL'
            IF OLD.estado IN ('revisada', 'descartada') THEN
                RAISE EXCEPTION 'Una pre-consulta revisada o descartada no se modifica' USING ERRCODE = '45000';
            END IF;

            IF OLD.estado = 'pendiente' AND NEW.estado = 'enviada' AND OLD.expira_en < LOCALTIMESTAMP THEN
                RAISE EXCEPTION 'El enlace de la pre-consulta ya vencio' USING ERRCODE = '45000';
            END IF;

            IF OLD.estado = 'pendiente' AND NEW.estado = 'revisada' THEN
                RAISE EXCEPTION 'La pre-consulta aun no fue enviada por el paciente' USING ERRCODE = '45000';
            END IF;

            IF OLD.estado = 'enviada' AND NEW.estado = 'pendiente' THEN
                RAISE EXCEPTION 'Una pre-consulta enviada no vuelve a quedar pendiente' USING ERRCODE = '45000';
            END IF;
            SQL);
    }

    private function crearTriggersDeProteccion(): void
    {
        // HU-08: se pueden corregir el tipo, la fecha o la descripción, pero no el archivo.
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER `trg_adjuntos_archivo_inmutable` BEFORE UPDATE ON `adjuntos` FOR EACH ROW
            BEGIN
                IF NOT (NEW.archivo <=> OLD.archivo) OR NOT (NEW.sha256 <=> OLD.sha256) OR NOT (NEW.paciente_id <=> OLD.paciente_id) THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El archivo de un estudio adjunto no se puede reemplazar';
                END IF;
            END
            SQL);

        // HU-18: el documento ya se entregó; solo se registra que el paciente lo firmó.
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER `trg_consentimientos_solo_firma` BEFORE UPDATE ON `consentimientos` FOR EACH ROW
            BEGIN
                IF NOT (NEW.paciente_id <=> OLD.paciente_id) OR NOT (NEW.procedimiento <=> OLD.procedimiento)
                   OR NOT (NEW.fecha <=> OLD.fecha) OR NOT (NEW.lugar <=> OLD.lugar)
                   OR NOT (NEW.firmante_nombre <=> OLD.firmante_nombre) OR NOT (NEW.firmante_es_tutor <=> OLD.firmante_es_tutor) THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Un consentimiento emitido no se modifica; solo se registra la firma';
                END IF;

                IF OLD.estado = 'firmado' AND NEW.estado <> 'firmado' THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Un consentimiento firmado no vuelve a quedar pendiente';
                END IF;
            END
            SQL);

        // HU-17: lo impreso y lo guardado deben coincidir siempre.
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER `trg_recetas_bloqueo_emitida` BEFORE UPDATE ON `recetas` FOR EACH ROW
            BEGIN
                IF EXISTS (SELECT 1 FROM documentos_emitidos WHERE receta_id = OLD.id) THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Una receta emitida no se puede modificar';
                END IF;
            END
            SQL);

        foreach (['insert' => ['INSERT', 'NEW'], 'update' => ['UPDATE', 'OLD'], 'delete' => ['DELETE', 'OLD']] as $sufijo => [$evento, $fila]) {
            DB::unprepared(<<<SQL
                CREATE TRIGGER `trg_receta_medicamentos_bloqueo_{$sufijo}` BEFORE {$evento} ON `receta_medicamentos` FOR EACH ROW
                BEGIN
                    IF EXISTS (SELECT 1 FROM documentos_emitidos WHERE receta_id = {$fila}.receta_id) THEN
                        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Los medicamentos de una receta emitida no se pueden cambiar';
                    END IF;
                END
                SQL);
        }

        // HU-17 y HU-18: el documento pertenece al mismo paciente que la receta o el consentimiento.
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER `trg_documentos_emitidos_validar` BEFORE INSERT ON `documentos_emitidos` FOR EACH ROW
            BEGIN
                IF NEW.receta_id IS NOT NULL AND (SELECT paciente_id FROM recetas WHERE id = NEW.receta_id) <> NEW.paciente_id THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La receta es de otro paciente';
                END IF;

                IF NEW.consentimiento_id IS NOT NULL AND (SELECT paciente_id FROM consentimientos WHERE id = NEW.consentimiento_id) <> NEW.paciente_id THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El consentimiento es de otro paciente';
                END IF;
            END
            SQL);

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER `trg_documentos_emitidos_sin_cambios` BEFORE UPDATE ON `documentos_emitidos` FOR EACH ROW
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Un documento emitido no se puede modificar'
            SQL);

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER `trg_documentos_emitidos_sin_borrado` BEFORE DELETE ON `documentos_emitidos` FOR EACH ROW
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Un documento emitido no se puede borrar'
            SQL);

        // HU-05: el enlace vencido no sirve y lo revisado ya no cambia.
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER `trg_preconsultas_flujo` BEFORE UPDATE ON `preconsultas` FOR EACH ROW
            BEGIN
                IF OLD.estado IN ('revisada', 'descartada') THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Una pre-consulta revisada o descartada no se modifica';
                END IF;

                IF OLD.estado = 'pendiente' AND NEW.estado = 'enviada' AND OLD.expira_en < NOW() THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El enlace de la pre-consulta ya vencio';
                END IF;

                IF OLD.estado = 'pendiente' AND NEW.estado = 'revisada' THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La pre-consulta aun no fue enviada por el paciente';
                END IF;

                IF OLD.estado = 'enviada' AND NEW.estado = 'pendiente' THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Una pre-consulta enviada no vuelve a quedar pendiente';
                END IF;
            END
            SQL);
    }
};
