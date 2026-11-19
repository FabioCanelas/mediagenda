-- =====================================================================
-- MediAgenda · 01: base de datos y usuarios
-- Ejecutar como root una sola vez (phpMyAdmin > SQL, o el cliente mysql).
-- Las contraseñas NO se guardan aquí: {{CLAVE_MIGRADOR}} y {{CLAVE_APP}}
-- se reemplazan al ejecutar con las que están en el archivo .env.
-- =====================================================================

CREATE DATABASE IF NOT EXISTS mediagenda
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Usuario de migraciones: crea y modifica la estructura de la base.
-- No tiene GRANT OPTION, así que no puede dar permisos a nadie.
-- ---------------------------------------------------------------------
CREATE USER IF NOT EXISTS 'mediagenda_migrador'@'localhost' IDENTIFIED BY '{{CLAVE_MIGRADOR}}';
CREATE USER IF NOT EXISTS 'mediagenda_migrador'@'127.0.0.1' IDENTIFIED BY '{{CLAVE_MIGRADOR}}';

GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, DROP, INDEX, REFERENCES,
      TRIGGER, CREATE VIEW, SHOW VIEW, CREATE ROUTINE, ALTER ROUTINE, EXECUTE, LOCK TABLES
  ON mediagenda.*
  TO 'mediagenda_migrador'@'localhost', 'mediagenda_migrador'@'127.0.0.1';

-- ---------------------------------------------------------------------
-- Usuario de la aplicación: se crea sin permisos.
-- Sus permisos se dan tabla por tabla en 02_permisos_app.sql,
-- después de ejecutar las migraciones.
-- ---------------------------------------------------------------------
CREATE USER IF NOT EXISTS 'mediagenda_app'@'localhost' IDENTIFIED BY '{{CLAVE_APP}}';
CREATE USER IF NOT EXISTS 'mediagenda_app'@'127.0.0.1' IDENTIFIED BY '{{CLAVE_APP}}';

FLUSH PRIVILEGES;
