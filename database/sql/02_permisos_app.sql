-- =====================================================================
-- MediAgenda · 02: permisos del usuario de la aplicación (mínimo privilegio)
-- Ejecutar como root DESPUÉS de las migraciones, y de nuevo cada vez que
-- un sprint agregue tablas.
--
-- Reglas:
--  * Tablas clínicas: leer, crear y modificar. Nunca DELETE: los registros
--    solo se ocultan con borrado lógico (columna deleted_at).
--  * Catálogo CIE-10 y auditoría: solo lectura. La auditoría la llenan los
--    triggers con los permisos del usuario migrador.
--  * Tablas internas de Laravel (sesiones, caché, colas): permisos completos,
--    porque el framework borra filas vencidas.
--  * Ningún permiso para cambiar la estructura (CREATE, ALTER, DROP).
-- =====================================================================

-- Se parte de cero para que el script se pueda repetir sin acumular permisos.
REVOKE ALL PRIVILEGES, GRANT OPTION FROM 'mediagenda_app'@'localhost', 'mediagenda_app'@'127.0.0.1';

-- Tablas clínicas y de configuración
GRANT SELECT, INSERT, UPDATE ON mediagenda.users                       TO 'mediagenda_app'@'localhost', 'mediagenda_app'@'127.0.0.1';
GRANT SELECT, INSERT, UPDATE ON mediagenda.pacientes                   TO 'mediagenda_app'@'localhost', 'mediagenda_app'@'127.0.0.1';
GRANT SELECT, INSERT, UPDATE ON mediagenda.historias_clinicas          TO 'mediagenda_app'@'localhost', 'mediagenda_app'@'127.0.0.1';
GRANT SELECT, INSERT, UPDATE ON mediagenda.diagnosticos                TO 'mediagenda_app'@'localhost', 'mediagenda_app'@'127.0.0.1';
GRANT SELECT, INSERT, UPDATE ON mediagenda.alergias                    TO 'mediagenda_app'@'localhost', 'mediagenda_app'@'127.0.0.1';
GRANT SELECT, INSERT, UPDATE ON mediagenda.alergenos                   TO 'mediagenda_app'@'localhost', 'mediagenda_app'@'127.0.0.1';
GRANT SELECT, INSERT, UPDATE ON mediagenda.pruebas_cutaneas            TO 'mediagenda_app'@'localhost', 'mediagenda_app'@'127.0.0.1';
GRANT SELECT, INSERT, UPDATE ON mediagenda.pruebas_cutaneas_resultados TO 'mediagenda_app'@'localhost', 'mediagenda_app'@'127.0.0.1';

-- Solo lectura
GRANT SELECT ON mediagenda.cie10     TO 'mediagenda_app'@'localhost', 'mediagenda_app'@'127.0.0.1';
GRANT SELECT ON mediagenda.auditoria TO 'mediagenda_app'@'localhost', 'mediagenda_app'@'127.0.0.1';

-- Tablas internas de Laravel
GRANT SELECT, INSERT, UPDATE, DELETE ON mediagenda.sessions              TO 'mediagenda_app'@'localhost', 'mediagenda_app'@'127.0.0.1';
GRANT SELECT, INSERT, UPDATE, DELETE ON mediagenda.cache                 TO 'mediagenda_app'@'localhost', 'mediagenda_app'@'127.0.0.1';
GRANT SELECT, INSERT, UPDATE, DELETE ON mediagenda.cache_locks           TO 'mediagenda_app'@'localhost', 'mediagenda_app'@'127.0.0.1';
GRANT SELECT, INSERT, UPDATE, DELETE ON mediagenda.jobs                  TO 'mediagenda_app'@'localhost', 'mediagenda_app'@'127.0.0.1';
GRANT SELECT, INSERT, UPDATE, DELETE ON mediagenda.job_batches           TO 'mediagenda_app'@'localhost', 'mediagenda_app'@'127.0.0.1';
GRANT SELECT, INSERT, UPDATE, DELETE ON mediagenda.failed_jobs           TO 'mediagenda_app'@'localhost', 'mediagenda_app'@'127.0.0.1';
GRANT SELECT, INSERT, UPDATE, DELETE ON mediagenda.password_reset_tokens TO 'mediagenda_app'@'localhost', 'mediagenda_app'@'127.0.0.1';

FLUSH PRIVILEGES;
