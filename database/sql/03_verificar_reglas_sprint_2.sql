-- Sprint 2: prueba de las reglas de la base con el usuario de la aplicación.
-- Debe correr como mediagenda_app (no como root, que se salta los permisos):
--   MYSQL_PWD="<clave de DB_PASSWORD>" /c/xampp/mysql/bin/mysql.exe -u mediagenda_app -h 127.0.0.1 mediagenda --force --table < database/sql/03_verificar_reglas_sprint_2.sql
-- Cada prueba marcada "debe fallar" tiene que mostrar un ERROR (trigger, CHECK o permiso).
-- Los cambios se deshacen con ROLLBACK al final.

START TRANSACTION;

SELECT id INTO @nota_cerrada FROM notas_evolucion WHERE estado = 'cerrada' LIMIT 1;
SELECT id INTO @nota_borrador FROM notas_evolucion WHERE estado = 'borrador' LIMIT 1;
SELECT id, paciente_id INTO @inmuno, @paciente FROM inmunoterapias LIMIT 1;
SELECT id INTO @otra_inmuno FROM inmunoterapias WHERE id <> @inmuno LIMIT 1;
SELECT id INTO @frasco_otra FROM frascos WHERE inmunoterapia_id = @otra_inmuno LIMIT 1;
SELECT id INTO @aplicacion FROM aplicaciones LIMIT 1;
SELECT inicio, fin INTO @cita_inicio, @cita_fin FROM citas WHERE estado <> 'cancelada' ORDER BY inicio DESC LIMIT 1;

SELECT '01 Modificar una nota cerrada (debe fallar)' AS prueba;
UPDATE notas_evolucion SET evolucion = 'cambio' WHERE id = @nota_cerrada;

SELECT '02 Borrar una nota con el usuario de la app (debe fallar)' AS prueba;
DELETE FROM notas_evolucion WHERE id = @nota_cerrada;

SELECT '03 Anexo en una nota en borrador (debe fallar)' AS prueba;
INSERT INTO notas_evolucion_anexos (id, nota_evolucion_id, texto, created_at) VALUES (UUID(), @nota_borrador, 'x', NOW());

SELECT '04 Anexo en una nota cerrada (debe funcionar)' AS prueba;
INSERT INTO notas_evolucion_anexos (id, nota_evolucion_id, texto, created_at) VALUES (UUID(), @nota_cerrada, 'Corrección de prueba', NOW());
SELECT ROW_COUNT() AS filas_insertadas;

SELECT '05 Modificar o borrar un anexo (debe fallar: sin permiso)' AS prueba;
UPDATE notas_evolucion_anexos SET texto = 'cambio' WHERE nota_evolucion_id = @nota_cerrada;

SELECT '06 Frasco con color que no corresponde a la dilución (debe fallar)' AS prueba;
INSERT INTO frascos (id, inmunoterapia_id, dilucion, color, lote, fecha_vencimiento, activo, created_at)
VALUES (UUID(), @inmuno, '1:100', 'rojo', 'PRUEBA1', '2027-06-01', 1, NOW());

SELECT '07 Aplicar un frasco vencido (debe fallar)' AS prueba;
SET @vencido = UUID();
INSERT INTO frascos (id, inmunoterapia_id, dilucion, color, lote, fecha_preparacion, fecha_vencimiento, activo, created_at)
VALUES (@vencido, @inmuno, '1:100', 'azul', 'VENCIDO1', '2025-01-01', '2025-06-01', 1, NOW());
INSERT INTO aplicaciones (id, inmunoterapia_id, frasco_id, fase, aplicada_en, volumen_ml, brazo, observacion_hasta, created_at)
VALUES (UUID(), @inmuno, @vencido, 'inicio', NOW(), 0.10, 'izquierdo', NOW() + INTERVAL 30 MINUTE, NOW());

SELECT '08 Aplicar un frasco de otro paciente (debe fallar)' AS prueba;
INSERT INTO aplicaciones (id, inmunoterapia_id, frasco_id, fase, aplicada_en, volumen_ml, brazo, observacion_hasta, created_at)
VALUES (UUID(), @inmuno, @frasco_otra, 'inicio', NOW(), 0.10, 'izquierdo', NOW() + INTERVAL 30 MINUTE, NOW());

SELECT '09 Volumen fuera de rango, 2 mL (debe fallar)' AS prueba;
INSERT INTO aplicaciones (id, inmunoterapia_id, frasco_id, fase, aplicada_en, volumen_ml, brazo, observacion_hasta, created_at)
SELECT UUID(), @inmuno, id, 'inicio', NOW(), 2.00, 'izquierdo', NOW() + INTERVAL 30 MINUTE, NOW() FROM frascos WHERE inmunoterapia_id = @inmuno AND activo = 1 AND fecha_vencimiento >= CURDATE() LIMIT 1;

SELECT '10 Borrar una aplicación (debe fallar: sin permiso)' AS prueba;
DELETE FROM aplicaciones WHERE id = @aplicacion;

SELECT '11 Reacción sistémica grado 6 (debe fallar)' AS prueba;
INSERT INTO reacciones_sistemicas (id, aplicacion_id, grado, sintomas, created_at) VALUES (UUID(), @aplicacion, 6, 'x', NOW());

SELECT '12 Cita que se cruza con otra (debe fallar)' AS prueba;
INSERT INTO citas (id, paciente_id, inicio, fin, tipo, estado, created_at)
VALUES (UUID(), @paciente, @cita_inicio + INTERVAL 10 MINUTE, @cita_fin + INTERVAL 10 MINUTE, 'control', 'programada', NOW());

SELECT '13 Cancelar una cita sin motivo (debe fallar)' AS prueba;
UPDATE citas SET estado = 'cancelada' WHERE inicio = @cita_inicio;

SELECT '14 Cita que termina antes de empezar (debe fallar)' AS prueba;
INSERT INTO citas (id, paciente_id, inicio, fin, tipo, estado, created_at)
VALUES (UUID(), @paciente, '2027-01-04 10:00', '2027-01-04 09:00', 'control', 'programada', NOW());

SELECT '15 Borrar una cita (debe fallar: sin permiso)' AS prueba;
DELETE FROM citas WHERE inicio = @cita_inicio;

ROLLBACK;

SELECT '16 Vistas: pacientes atrasados' AS prueba;
SELECT CONCAT(p.nombres, ' ', p.apellido_paterno) AS paciente, v.fase, DATE(v.fecha_prevista) AS le_tocaba, v.dias_atraso
  FROM v_pacientes_atrasados v JOIN pacientes p ON p.id = v.paciente_id ORDER BY v.dias_atraso DESC;

SELECT '17 Auditoría de las tablas nuevas' AS prueba;
SELECT tabla, accion, COUNT(*) AS registros FROM auditoria
 WHERE tabla IN ('notas_evolucion', 'notas_evolucion_anexos', 'inmunoterapias', 'esquema_dosis', 'frascos', 'aplicaciones', 'reacciones_sistemicas', 'citas', 'reglas_atraso', 'inmunoterapia_alergeno')
 GROUP BY tabla, accion ORDER BY tabla, accion;
