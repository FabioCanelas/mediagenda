<?php

namespace Tests\Feature;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Hardening de la versión web: cabeceras de seguridad, páginas sin caché
 * y verificación en dos pasos obligatoria cuando se pide.
 */
class SeguridadWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_las_respuestas_llevan_cabeceras_de_seguridad(): void
    {
        $this->get('/panel/login')
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'same-origin')
            ->assertHeader('Content-Security-Policy', "frame-ancestors 'none'; base-uri 'self'; form-action 'self'; object-src 'none'")
            ->assertHeaderMissing('X-Powered-By')
            ->assertHeaderMissing('Strict-Transport-Security');
    }

    public function test_con_https_el_navegador_queda_obligado_a_usarlo(): void
    {
        $this->get('https://localhost/panel/login')
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }

    public function test_las_paginas_con_sesion_no_se_guardan_en_la_cache_del_navegador(): void
    {
        $this->actingAs(User::factory()->doctora()->create())
            ->get('/panel')
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private');
    }

    /**
     * Filament agrega a sus rutas, al arrancar, el middleware que obliga a configurar
     * la verificación en dos pasos si el panel la marca como obligatoria.
     */
    public function test_la_verificacion_en_dos_pasos_es_obligatoria_solo_cuando_se_configura(): void
    {
        config(['mediagenda.seguridad.mfa_obligatorio' => true]);
        $this->assertTrue(Filament::getPanel('admin')->isMultiFactorAuthenticationRequired());

        config(['mediagenda.seguridad.mfa_obligatorio' => false]);
        $this->assertFalse(Filament::getPanel('admin')->isMultiFactorAuthenticationRequired());
    }

    public function test_sin_esa_exigencia_la_doctora_entra_directo(): void
    {
        $this->actingAs(User::factory()->doctora()->create())
            ->get('/panel')
            ->assertOk();
    }
}
