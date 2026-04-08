<?php

namespace Tests\Feature;

use App\Models\PresupAdjAsignacion;
use App\Models\PresupAdj;
use App\Models\SeccUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TraduccionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_traduccion_page_requiere_autenticacion()
    {
        $response = $this->get('/traduccion/1');
        $response->assertRedirect('/login');
    }

    public function test_usuario_puede_ver_su_asignacion()
    {
        $usuario = SeccUser::factory()->create();
        $documento = PresupAdj::factory()->create();
        $asignacion = PresupAdjAsignacion::factory()->create([
            'login' => $usuario->login,
            'id_adjun' => $documento->id_adjun,
        ]);

        $response = $this->actingAs($usuario)->get("/traduccion/{$asignacion->id}");
        $response->assertOk();
    }

    public function test_usuario_no_puede_ver_asignacion_de_otro()
    {
        $usuario1 = SeccUser::factory()->create();
        $usuario2 = SeccUser::factory()->create();
        $documento = PresupAdj::factory()->create();
        $asignacion = PresupAdjAsignacion::factory()->create([
            'login' => $usuario1->login,
            'id_adjun' => $documento->id_adjun,
        ]);

        $response = $this->actingAs($usuario2)->get("/traduccion/{$asignacion->id}");
        $response->assertForbidden();
    }

    public function test_admin_puede_ver_cualquier_asignacion()
    {
        $admin = SeccUser::factory()->create();
        $admin->assignRole('admin');

        $usuario = SeccUser::factory()->create();
        $documento = PresupAdj::factory()->create();
        $asignacion = PresupAdjAsignacion::factory()->create([
            'login' => $usuario->login,
            'id_adjun' => $documento->id_adjun,
        ]);

        $response = $this->actingAs($admin)->get("/traduccion/{$asignacion->id}");
        $response->assertOk();
    }

    public function test_estado_cambia_a_en_traduccion()
    {
        $usuario = SeccUser::factory()->create();
        $documento = PresupAdj::factory()->create();
        $asignacion = PresupAdjAsignacion::factory()->create([
            'login' => $usuario->login,
            'id_adjun' => $documento->id_adjun,
            'estado' => 'Asignado',
        ]);

        $this->actingAs($usuario)->get("/traduccion/{$asignacion->id}");

        $asignacion->refresh();
        $this->assertEquals('En Traducción', $asignacion->estado);
    }
}
