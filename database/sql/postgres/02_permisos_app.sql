-- =====================================================================
-- MediAgenda · Permisos en PostgreSQL (Supabase)
-- Equivale a database/sql/02_permisos_app.sql (MariaDB) y agrega lo que
-- PostgreSQL permite además: seguridad por filas (RLS) y pgaudit.
--
-- Lo ejecuta el dueño de las tablas, después de migrar:
--   php artisan base:sql database/sql/postgres/02_permisos_app.sql
-- Hay que repetirlo si una migración crea tablas nuevas.
-- =====================================================================

-- 1. Privilegio mínimo: se parte de cero y se da solo lo necesario.
REVOKE ALL ON ALL TABLES IN SCHEMA mediagenda FROM mediagenda_app, PUBLIC;
REVOKE ALL ON ALL SEQUENCES IN SCHEMA mediagenda FROM mediagenda_app, PUBLIC;
REVOKE ALL ON ALL FUNCTIONS IN SCHEMA mediagenda FROM PUBLIC;

-- Expediente clínico: se lee, se agrega y se corrige, pero NO se borra.
GRANT SELECT, INSERT, UPDATE ON
    users, pacientes, historias_clinicas, diagnosticos, alergias, alergenos,
    pruebas_cutaneas, pruebas_cutaneas_resultados,
    notas_evolucion, inmunoterapias, frascos, aplicaciones, reacciones_sistemicas,
    reglas_atraso, citas,
    adjuntos, cuestionarios_act, consentimientos, recetas, preconsultas
TO mediagenda_app;

-- Lo que una vez escrito ya no cambia: anexos de notas y documentos emitidos.
GRANT SELECT, INSERT ON notas_evolucion_anexos, documentos_emitidos TO mediagenda_app;

-- Detalles que se reemplazan al editar (esquema de dosis, alérgenos del tratamiento,
-- medicamentos de una receta en borrador; un trigger impide tocar las emitidas).
GRANT SELECT, INSERT, UPDATE, DELETE ON esquema_dosis, inmunoterapia_alergeno, receta_medicamentos TO mediagenda_app;

-- Solo lectura: catálogo CIE-10, auditoría y vistas.
GRANT SELECT ON cie10, auditoria, v_proxima_aplicacion, v_pacientes_atrasados TO mediagenda_app;

-- Tablas técnicas de Laravel (sesiones, caché, colas).
GRANT SELECT, INSERT, UPDATE, DELETE ON
    sessions, cache, cache_locks, jobs, job_batches, failed_jobs, password_reset_tokens
TO mediagenda_app;

-- Las llaves autoincrementales necesitan su contador.
GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA mediagenda TO mediagenda_app;

-- 2. Seguridad por filas (RLS) en todas las tablas: aunque un error de
--    configuración le diera permisos a otro rol (por ejemplo, a los de la API
--    pública de Supabase), no vería ninguna fila. Solo la aplicación tiene política.
DO $$
DECLARE
    tabla record;
BEGIN
    FOR tabla IN SELECT tablename FROM pg_tables WHERE schemaname = 'mediagenda' LOOP
        EXECUTE format('ALTER TABLE mediagenda.%I ENABLE ROW LEVEL SECURITY', tabla.tablename);
        EXECUTE format('DROP POLICY IF EXISTS solo_aplicacion ON mediagenda.%I', tabla.tablename);
        EXECUTE format('CREATE POLICY solo_aplicacion ON mediagenda.%I TO mediagenda_app USING (true) WITH CHECK (true)', tabla.tablename);
    END LOOP;
END
$$;

-- 3. pgaudit por objeto: se registra cada lectura o cambio que la aplicación hace
--    en estas tablas clínicas (quién, cuándo y qué consulta). Los registros se ven
--    en Supabase → Logs → Postgres.
GRANT SELECT, INSERT, UPDATE, DELETE ON
    pacientes, historias_clinicas, diagnosticos, alergias,
    pruebas_cutaneas, pruebas_cutaneas_resultados,
    notas_evolucion, notas_evolucion_anexos, inmunoterapias, aplicaciones, reacciones_sistemicas,
    adjuntos, cuestionarios_act, consentimientos, recetas, receta_medicamentos,
    documentos_emitidos, preconsultas
TO mediagenda_auditor;

SELECT 'Permisos, RLS y pgaudit aplicados' AS resultado;
