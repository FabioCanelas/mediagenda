-- Sprint 3: prueba de las reglas de la base con el usuario de la aplicación.
-- Debe correr como mediagenda_app (no como root, que se salta los permisos):
--   MYSQL_PWD="<clave de DB_PASSWORD>" /c/xampp/mysql/bin/mysql.exe -u mediagenda_app -h 127.0.0.1 mediagenda --force --table < database/sql/04_verificar_reglas_sprint_3.sql
-- Cada prueba marcada "debe fallar" tiene que mostrar un ERROR (trigger, CHECK o permiso).
-- Los cambios se deshacen con ROLLBACK al final.

START TRANSACTION;

SELECT id, paciente_id INTO @adjunto, @paciente_adjunto FROM adjuntos LIMIT 1;
SELECT id INTO @act FROM cuestionarios_act LIMIT 1;
SELECT id, paciente_id INTO @consentimiento, @paciente_consentimiento FROM consentimientos WHERE estado = 'firmado' LIMIT 1;
SELECT r.id, r.paciente_id INTO @receta_emitida, @paciente_receta FROM recetas r JOIN documentos_emitidos d ON d.receta_id = r.id LIMIT 1;
SELECT r.id INTO @receta_borrador FROM recetas r LEFT JOIN documentos_emitidos d ON d.receta_id = r.id WHERE d.id IS NULL LIMIT 1;
SELECT id INTO @otro_paciente FROM pacientes WHERE id <> @paciente_receta LIMIT 1;
SELECT id INTO @documento FROM documentos_emitidos LIMIT 1;

SELECT '01 Reemplazar el archivo de un estudio (debe fallar)' AS prueba;
UPDATE adjuntos SET archivo = 'adjuntos/otro.pdf' WHERE id = @adjunto;

SELECT '02 Corregir la descripción de un estudio (debe funcionar)' AS prueba;
UPDATE adjuntos SET descripcion = 'Descripción corregida' WHERE id = @adjunto;
SELECT ROW_COUNT() AS filas_modificadas;

SELECT '03 Estudio en formato de texto (debe fallar)' AS prueba;
INSERT INTO adjuntos (id, paciente_id, tipo, fecha_estudio, archivo, nombre_original, mime, tamano_bytes, sha256, created_at)
VALUES (UUID(), @paciente_adjunto, 'otro', CURDATE(), 'adjuntos/x.txt', 'x.txt', 'text/plain', 10, SHA2('x', 256), NOW());

SELECT '04 Borrar un estudio (debe fallar: sin permiso)' AS prueba;
DELETE FROM adjuntos WHERE id = @adjunto;

SELECT '05 ACT con una respuesta de 6 puntos (debe fallar)' AS prueba;
UPDATE cuestionarios_act SET respuesta_1 = 6 WHERE id = @act;

SELECT '06 La base calcula el puntaje del ACT (debe funcionar)' AS prueba;
UPDATE cuestionarios_act SET respuesta_1 = 5, respuesta_2 = 5, respuesta_3 = 5, respuesta_4 = 4, respuesta_5 = 4 WHERE id = @act;
SELECT respuesta_1 + respuesta_2 + respuesta_3 + respuesta_4 + respuesta_5 AS suma, puntaje FROM cuestionarios_act WHERE id = @act;

SELECT '07 Escribir el puntaje a mano (MariaDB lo ignora y deja la suma)' AS prueba;
UPDATE cuestionarios_act SET puntaje = 25 WHERE id = @act;
SHOW WARNINGS;
SELECT puntaje AS puntaje_despues FROM cuestionarios_act WHERE id = @act;

SELECT '08 Cambiar el procedimiento de un consentimiento emitido (debe fallar)' AS prueba;
UPDATE consentimientos SET procedimiento = 'prick_test' WHERE id = @consentimiento;

SELECT '09 Devolver a pendiente un consentimiento firmado (debe fallar)' AS prueba;
UPDATE consentimientos SET estado = 'pendiente', firmado_en = NULL WHERE id = @consentimiento;

SELECT '10 Marcar firmado sin la fecha de la firma (debe fallar)' AS prueba;
SET @pendiente = UUID();
INSERT INTO consentimientos (id, paciente_id, procedimiento, fecha, lugar, firmante_nombre, estado, created_at)
VALUES (@pendiente, @paciente_consentimiento, 'prick_test', CURDATE(), 'La Paz', 'Paciente de prueba', 'pendiente', NOW());
UPDATE consentimientos SET estado = 'firmado' WHERE id = @pendiente;

SELECT '11 Registrar la firma con su fecha (debe funcionar)' AS prueba;
UPDATE consentimientos SET estado = 'firmado', firmado_en = NOW() WHERE id = @pendiente;
SELECT ROW_COUNT() AS filas_modificadas;

SELECT '12 Modificar una receta emitida (debe fallar)' AS prueba;
UPDATE recetas SET indicaciones = 'cambio' WHERE id = @receta_emitida;

SELECT '13 Agregar un medicamento a una receta emitida (debe fallar)' AS prueba;
INSERT INTO receta_medicamentos (receta_id, orden, medicamento, dosis, created_at)
VALUES (@receta_emitida, 9, 'Medicamento agregado', '1 al día', NOW());

SELECT '14 Corregir una receta en borrador (debe funcionar)' AS prueba;
UPDATE recetas SET indicaciones = 'Indicación corregida' WHERE id = @receta_borrador;
SELECT ROW_COUNT() AS filas_modificadas;

SELECT '15 Modificar un documento emitido (debe fallar: sin permiso)' AS prueba;
UPDATE documentos_emitidos SET sha256 = SHA2('otro', 256) WHERE id = @documento;

SELECT '16 Borrar un documento emitido (debe fallar: sin permiso)' AS prueba;
DELETE FROM documentos_emitidos WHERE id = @documento;

SELECT '17 Documento de un paciente con la receta de otro (debe fallar)' AS prueba;
INSERT INTO documentos_emitidos (id, tipo, folio, paciente_id, receta_id, archivo, sha256, tamano_bytes, emitido_en)
VALUES (UUID(), 'receta', 'R-2026-900001', @otro_paciente, @receta_borrador, 'documentos/x.pdf', SHA2('x', 256), 10, NOW());

SELECT '18 Documento con un folio mal formado (debe fallar)' AS prueba;
INSERT INTO documentos_emitidos (id, tipo, folio, paciente_id, receta_id, archivo, sha256, tamano_bytes, emitido_en)
SELECT UUID(), 'receta', 'RECETA-1', paciente_id, id, 'documentos/x.pdf', SHA2('x', 256), 10, NOW() FROM recetas WHERE id = @receta_borrador;

SELECT '19 Enviar una pre-consulta con el enlace vencido (debe fallar)' AS prueba;
SET @vencida = UUID();
INSERT INTO preconsultas (id, paciente_id, token_hash, expira_en, estado, created_at)
VALUES (@vencida, @paciente_adjunto, SHA2(UUID(), 256), NOW() - INTERVAL 1 HOUR, 'pendiente', NOW());
UPDATE preconsultas SET estado = 'enviada', enviada_en = NOW(), motivo_consulta = 'x', alergia_medicamentos = 'no' WHERE id = @vencida;

SELECT '20 Marcar enviada una pre-consulta sin respuestas (debe fallar)' AS prueba;
SET @vigente = UUID();
INSERT INTO preconsultas (id, paciente_id, token_hash, expira_en, estado, created_at)
VALUES (@vigente, @paciente_adjunto, SHA2(UUID(), 256), NOW() + INTERVAL 1 HOUR, 'pendiente', NOW());
UPDATE preconsultas SET estado = 'enviada', enviada_en = NOW() WHERE id = @vigente;

SELECT '21 Modificar una pre-consulta ya revisada (debe fallar)' AS prueba;
UPDATE preconsultas SET estado = 'enviada', enviada_en = NOW(), motivo_consulta = 'x', alergia_medicamentos = 'no' WHERE id = @vigente;
UPDATE preconsultas SET estado = 'revisada', revisada_en = NOW() WHERE id = @vigente;
UPDATE preconsultas SET motivo_consulta = 'cambio' WHERE id = @vigente;

ROLLBACK;

SELECT '22 La auditoría nunca copia el token de la pre-consulta' AS prueba;
SELECT COUNT(*) AS registros_de_preconsultas, SUM(datos_despues LIKE '%token_hash%') AS con_token
  FROM auditoria WHERE tabla = 'preconsultas';

SELECT '23 Auditoría de las tablas nuevas' AS prueba;
SELECT tabla, accion, COUNT(*) AS registros FROM auditoria
 WHERE tabla IN ('adjuntos', 'cuestionarios_act', 'consentimientos', 'recetas', 'receta_medicamentos', 'documentos_emitidos', 'preconsultas')
 GROUP BY tabla, accion ORDER BY tabla, accion;
