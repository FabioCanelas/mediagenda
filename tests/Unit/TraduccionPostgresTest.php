<?php

namespace Tests\Unit;

use App\Support\BaseDeDatos\ErrorDeBase;
use App\Support\BaseDeDatos\Restricciones;
use Illuminate\Database\QueryException;
use PDOException;
use PHPUnit\Framework\TestCase;

/**
 * Las reglas CHECK se escriben una vez (sintaxis de MariaDB) y se traducen a PostgreSQL.
 */
class TraduccionPostgresTest extends TestCase
{
    public function test_traduce_identificadores_y_expresiones_regulares(): void
    {
        $this->assertSame(
            "\"sha256\" ~ '^[0-9a-f]{64}$'",
            Restricciones::traducirAPostgres("`sha256` REGEXP '^[0-9a-f]{64}$'"),
        );

        $this->assertSame(
            "(\"estado\" = 'borrador' AND \"cerrada_en\" IS NULL)",
            Restricciones::traducirAPostgres("(`estado` = 'borrador' AND `cerrada_en` IS NULL)"),
        );
    }

    public function test_limpia_el_mensaje_de_un_trigger_de_postgres(): void
    {
        $this->assertSame('Choque de horario con otra cita', ErrorDeBase::mensaje($this->errorConMensaje(
            "ERROR:  Choque de horario con otra cita\nCONTEXT:  PL/pgSQL function fn_trg_citas_sin_choque_insert() line 3 at RAISE",
        )));
    }

    public function test_deja_igual_el_mensaje_de_mariadb_y_usa_el_texto_por_defecto_si_no_hay(): void
    {
        $this->assertSame('Choque de horario con otra cita', ErrorDeBase::mensaje($this->errorConMensaje('Choque de horario con otra cita')));
        $this->assertSame('Sin detalle', ErrorDeBase::mensaje($this->errorConMensaje(null), 'Sin detalle'));
    }

    private function errorConMensaje(?string $mensaje): QueryException
    {
        $pdo = new PDOException('error');
        $pdo->errorInfo = ['45000', 1644, $mensaje];

        return new QueryException('pgsql', 'insert into citas', [], $pdo);
    }
}
