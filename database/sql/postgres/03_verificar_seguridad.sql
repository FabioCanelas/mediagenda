-- =====================================================================
-- MediAgenda · Verificación del hardening en PostgreSQL (Supabase)
--
-- Cada prueba se ejecuta COMO el rol indicado (SET ROLE) dentro de una
-- subtransacción que siempre se deshace: el script no cambia ningún dato.
-- Resultado esperado de cada prueba:
--   permitido  → la operación funciona
--   denegado   → el rol no tiene privilegio (GRANT)
--   rechazado  → un trigger o un CHECK de la base la impide
--
-- Se ejecuta con el administrador, que puede actuar como los demás roles:
--   php artisan base:sql database/sql/postgres/03_verificar_seguridad.sql --conexion=pgsql_admin
-- o pegándolo en el SQL Editor de Supabase.
-- =====================================================================

CREATE TEMP TABLE IF NOT EXISTS resultados (
    n serial,
    control text,
    prueba text,
    rol text,
    esperado text,
    obtenido text,
    ok boolean
);
TRUNCATE resultados;

CREATE OR REPLACE FUNCTION pg_temp.probar(p_control text, p_prueba text, p_rol text, p_sql text, p_esperado text)
RETURNS void LANGUAGE plpgsql AS $$
DECLARE
    v_obtenido text;
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_roles WHERE rolname = p_rol) THEN
        INSERT INTO resultados (control, prueba, rol, esperado, obtenido, ok)
        VALUES (p_control, p_prueba, p_rol, p_esperado, 'omitido: el rol no existe en este servidor', NULL);
        RETURN;
    END IF;

    IF NOT pg_has_role(session_user, p_rol, 'MEMBER') THEN
        INSERT INTO resultados (control, prueba, rol, esperado, obtenido, ok)
        VALUES (p_control, p_prueba, p_rol, p_esperado, 'omitido: el administrador no puede actuar como ' || p_rol, NULL);
        RETURN;
    END IF;

    BEGIN
        EXECUTE format('SET LOCAL ROLE %I', p_rol);
        SET LOCAL search_path = mediagenda;
        EXECUTE p_sql;
        v_obtenido := 'permitido';
        -- Deshace lo que hizo la prueba (y el SET ROLE).
        RAISE EXCEPTION 'deshacer' USING ERRCODE = 'P0099';
    EXCEPTION
        WHEN SQLSTATE 'P0099' THEN NULL;
        WHEN insufficient_privilege THEN v_obtenido := 'denegado: ' || SQLERRM;
        WHEN OTHERS THEN v_obtenido := 'rechazado: ' || SQLERRM;
    END;

    INSERT INTO resultados (control, prueba, rol, esperado, obtenido, ok)
    VALUES (p_control, p_prueba, p_rol, p_esperado, v_obtenido, split_part(v_obtenido, ':', 1) = p_esperado);
END
$$;

-- 1. Privilegio mínimo del usuario de la aplicación.
SELECT pg_temp.probar('Privilegio mínimo', 'Leer pacientes', 'mediagenda_app',
    'SELECT count(*) FROM pacientes', 'permitido');
SELECT pg_temp.probar('Privilegio mínimo', 'Borrar un paciente', 'mediagenda_app',
    'DELETE FROM pacientes WHERE id = (SELECT id FROM pacientes LIMIT 1)', 'denegado');
SELECT pg_temp.probar('Privilegio mínimo', 'Borrar historias clínicas', 'mediagenda_app',
    'DELETE FROM historias_clinicas', 'denegado');
SELECT pg_temp.probar('Privilegio mínimo', 'Modificar un documento emitido', 'mediagenda_app',
    'UPDATE documentos_emitidos SET folio = folio', 'denegado');
SELECT pg_temp.probar('Privilegio mínimo', 'Modificar la auditoría', 'mediagenda_app',
    'UPDATE auditoria SET accion = accion', 'denegado');
SELECT pg_temp.probar('Privilegio mínimo', 'Escribir en la auditoría a mano', 'mediagenda_app',
    $q$INSERT INTO auditoria (tabla, registro_id, accion) VALUES ('pacientes', '1', 'INSERT')$q$, 'denegado');
SELECT pg_temp.probar('Privilegio mínimo', 'Vaciar una tabla', 'mediagenda_app',
    'TRUNCATE pacientes', 'denegado');
SELECT pg_temp.probar('Privilegio mínimo', 'Borrar una tabla', 'mediagenda_app',
    'DROP TABLE pacientes', 'denegado');
SELECT pg_temp.probar('Privilegio mínimo', 'Crear tablas en el esquema', 'mediagenda_app',
    'CREATE TABLE mediagenda.intrusa (id int)', 'denegado');

-- 2. La API pública de Supabase no llega a los datos clínicos.
SELECT pg_temp.probar('API pública de Supabase', 'Leer pacientes sin iniciar sesión', 'anon',
    'SELECT count(*) FROM mediagenda.pacientes', 'denegado');
SELECT pg_temp.probar('API pública de Supabase', 'Leer pacientes como usuario de la API', 'authenticated',
    'SELECT count(*) FROM mediagenda.pacientes', 'denegado');

-- 3. Reglas de negocio que la base hace cumplir (triggers y CHECK).
SELECT pg_temp.probar('Triggers', 'Modificar una nota de evolución cerrada', 'mediagenda_app',
    $q$UPDATE notas_evolucion SET updated_at = now() WHERE id = (SELECT id FROM notas_evolucion WHERE estado = 'cerrada' LIMIT 1)$q$, 'rechazado');
SELECT pg_temp.probar('Triggers', 'Cambiar una receta ya emitida', 'mediagenda_app',
    'UPDATE recetas SET updated_at = now() WHERE id = (SELECT receta_id FROM documentos_emitidos WHERE receta_id IS NOT NULL LIMIT 1)', 'rechazado');
SELECT pg_temp.probar('Triggers', 'Quitar un medicamento de una receta emitida', 'mediagenda_app',
    'DELETE FROM receta_medicamentos WHERE receta_id = (SELECT receta_id FROM documentos_emitidos WHERE receta_id IS NOT NULL LIMIT 1)', 'rechazado');
SELECT pg_temp.probar('Triggers', 'Reemplazar el archivo de un estudio', 'mediagenda_app',
    $q$UPDATE adjuntos SET sha256 = repeat('0', 64) WHERE id = (SELECT id FROM adjuntos LIMIT 1)$q$, 'rechazado');
SELECT pg_temp.probar('Triggers', 'Agendar una cita encima de otra', 'mediagenda_app',
    $q$INSERT INTO citas SELECT (jsonb_populate_record(c, jsonb_build_object('id', gen_random_uuid()))).*
         FROM citas c WHERE c.estado NOT IN ('cancelada', 'no_asistio') LIMIT 1$q$, 'rechazado');
SELECT pg_temp.probar('CHECK y columna calculada', 'Escribir a mano el puntaje del ACT', 'mediagenda_app',
    'UPDATE cuestionarios_act SET puntaje = 25 WHERE id = (SELECT id FROM cuestionarios_act LIMIT 1)', 'rechazado');
SELECT pg_temp.probar('CHECK y columna calculada', 'Respuesta del ACT fuera de 1 a 5', 'mediagenda_app',
    'UPDATE cuestionarios_act SET respuesta_1 = 9 WHERE id = (SELECT id FROM cuestionarios_act LIMIT 1)', 'rechazado');
SELECT pg_temp.probar('CHECK y columna calculada', 'CI con letras', 'mediagenda_app',
    $q$UPDATE pacientes SET ci = 'ABC' WHERE id = (SELECT id FROM pacientes LIMIT 1)$q$, 'rechazado');

-- 4. Auditoría: un cambio de la aplicación queda registrado aunque ella no pueda escribir ahí.
SELECT pg_temp.probar('Auditoría', 'Un cambio en pacientes deja su registro', 'mediagenda_app',
    $q$DO $a$
        DECLARE antes bigint; despues bigint;
        BEGIN
            SELECT count(*) INTO antes FROM auditoria;
            UPDATE pacientes SET updated_at = updated_at WHERE id = (SELECT id FROM pacientes LIMIT 1);
            SELECT count(*) INTO despues FROM auditoria;
            IF despues <> antes + 1 THEN
                RAISE EXCEPTION 'la auditoria no registro el cambio';
            END IF;
        END
    $a$$q$, 'permitido');

-- 5. Configuración del servidor.
-- Desde aquí solo se ve el tramo interno entre el pooler de Supabase y la base,
-- que no usa TLS. El tramo que cruza internet (aplicación → pooler) se prueba
-- desde afuera con: php artisan base:probar-tls
INSERT INTO resultados (control, prueba, rol, esperado, obtenido, ok)
SELECT 'Servidor', 'El servidor tiene TLS activo (ver base:probar-tls)', '—', 'sí',
       CASE WHEN current_setting('ssl') = 'on' THEN 'sí' ELSE 'no' END,
       current_setting('ssl') = 'on';

INSERT INTO resultados (control, prueba, rol, esperado, obtenido, ok)
SELECT 'Servidor', 'Tablas del esquema sin seguridad por filas (RLS)', '—', '0',
       count(*)::text, count(*) = 0
  FROM pg_class c JOIN pg_namespace s ON s.oid = c.relnamespace
 WHERE s.nspname = 'mediagenda' AND c.relkind = 'r' AND NOT c.relrowsecurity;

INSERT INTO resultados (control, prueba, rol, esperado, obtenido, ok)
SELECT 'Servidor', 'La aplicación no es superusuario ni se salta RLS', rolname, 'no',
       CASE WHEN rolsuper OR rolbypassrls THEN 'sí' ELSE 'no' END, NOT (rolsuper OR rolbypassrls)
  FROM pg_roles WHERE rolname = 'mediagenda_app';

INSERT INTO resultados (control, prueba, rol, esperado, obtenido, ok)
SELECT 'Servidor', 'pgaudit instalado (registro de lecturas)', '—', 'sí',
       CASE WHEN EXISTS (SELECT 1 FROM pg_extension WHERE extname = 'pgaudit') THEN 'sí' ELSE 'no' END,
       EXISTS (SELECT 1 FROM pg_extension WHERE extname = 'pgaudit');

SELECT n AS "#",
       control,
       prueba,
       rol,
       esperado,
       split_part(obtenido, ':', 1) AS obtenido,
       CASE WHEN ok THEN '✓' WHEN ok IS NULL THEN '–' ELSE '✗' END AS ok,
       left(nullif(trim(substr(obtenido, length(split_part(obtenido, ':', 1)) + 2)), ''), 70) AS detalle
  FROM resultados
 ORDER BY n;
