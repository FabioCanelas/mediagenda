<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * PostgreSQL, a diferencia de MariaDB, no crea un índice por cada llave foránea.
 * La verificación origen vs. destino (php artisan base:comparar) encontró 38 llaves
 * foráneas sin índice: al crecer las tablas, cada JOIN y cada comprobación de
 * ON DELETE RESTRICT recorrería la tabla entera.
 *
 * Esta migración crea el índice que falta en cada llave foránea de una columna del
 * esquema. En MariaDB no hace nada, porque allí esos índices ya existen.
 */
return new class extends Migration
{
    private const SUFIJO = '_fk_index';

    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach ($this->llavesSinIndice() as $llave) {
            DB::statement(sprintf(
                'CREATE INDEX IF NOT EXISTS %s ON %s (%s)',
                $this->identificador($llave->tabla.'_'.$llave->columna.self::SUFIJO),
                $this->identificador($llave->tabla),
                $this->identificador($llave->columna),
            ));
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $indices = DB::select(
            'SELECT indexname FROM pg_indexes WHERE schemaname = current_schema() AND right(indexname, ?) = ?',
            [strlen(self::SUFIJO), self::SUFIJO],
        );

        foreach ($indices as $indice) {
            DB::statement('DROP INDEX IF EXISTS '.$this->identificador($indice->indexname));
        }
    }

    /**
     * Llaves foráneas de una sola columna cuya columna no encabeza ningún índice.
     *
     * @return array<int, object{tabla: string, columna: string}>
     */
    private function llavesSinIndice(): array
    {
        return DB::select(<<<'SQL'
            SELECT c.conrelid::regclass::text AS tabla, a.attname AS columna
              FROM pg_constraint c
              JOIN pg_attribute a ON a.attrelid = c.conrelid AND a.attnum = c.conkey[1]
             WHERE c.contype = 'f'
               AND c.connamespace = (SELECT oid FROM pg_namespace WHERE nspname = current_schema())
               AND array_length(c.conkey, 1) = 1
               AND NOT EXISTS (
                   SELECT 1 FROM pg_index i WHERE i.indrelid = c.conrelid AND i.indkey[0] = c.conkey[1]
               )
             ORDER BY 1, 2
            SQL);
    }

    private function identificador(string $nombre): string
    {
        return '"'.str_replace('"', '""', $nombre).'"';
    }
};
