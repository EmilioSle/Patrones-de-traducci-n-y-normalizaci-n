<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PublicacionesCarritoApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_publicaciones_son_publicas_y_contienen_los_tres_tipos(): void
    {
        $this->getJson('/api/publicaciones')->assertOk()->assertJsonCount(3, 'data')
            ->assertJsonFragment(['tipo' => 'novedad'])->assertJsonFragment(['tipo' => 'trabajo realizado'])
            ->assertJsonFragment(['tipo' => 'promoción']);
    }

    public function test_publicacion_se_consulta_por_id(): void
    {
        $this->getJson('/api/publicaciones/2')->assertOk()->assertJsonPath('data.id', 2)
            ->assertJsonPath('data.tipo', 'trabajo realizado');
    }

    public function test_publicacion_inexistente_devuelve_404(): void
    {
        $this->getJson('/api/publicaciones/999')->assertNotFound()
            ->assertJsonPath('success', false)->assertJsonPath('message', 'Publicación no encontrada');
    }

    public function test_carrito_exige_autenticacion(): void
    {
        $this->getJson('/api/carrito')->assertUnauthorized();
        $this->getJson('/api/carrito/resumen')->assertUnauthorized();
    }

    public function test_token_invalido_no_da_acceso_al_carrito(): void
    {
        $this->withToken('token-invalido')->getJson('/api/carrito')->assertUnauthorized();
    }

    public function test_carrito_muestra_articulos_y_subtotales(): void
    {
        $cliente = User::factory()->create();
        Cache::put('carrito:'.$cliente->id, [
            ['producto_id' => 1, 'nombre' => 'Taza', 'cantidad' => 2, 'precio_centavos' => 850],
        ]);
        Sanctum::actingAs($cliente);

        $this->getJson('/api/carrito')->assertOk()->assertJsonPath('data.cliente_id', $cliente->id)
            ->assertJsonCount(1, 'data.articulos')->assertJsonPath('data.articulos.0.precio_unitario', 8.5)
            ->assertJsonPath('data.articulos.0.subtotal', 17);
    }

    public function test_resumen_suma_cantidades_y_precios_por_cantidad(): void
    {
        $cliente = User::factory()->create();
        Cache::put('carrito:'.$cliente->id, [
            ['producto_id' => 1, 'nombre' => 'Taza', 'cantidad' => 2, 'precio_centavos' => 850],
            ['producto_id' => 2, 'nombre' => 'Caja', 'cantidad' => 1, 'precio_centavos' => 1800],
        ]);
        Sanctum::actingAs($cliente);

        $this->getJson('/api/carrito/resumen')->assertOk()->assertJsonPath('data.productos_distintos', 2)
            ->assertJsonPath('data.cantidad_total', 3)->assertJsonPath('data.total', 35)->assertJsonPath('data.moneda', 'USD');
    }

    public function test_usuario_no_puede_elegir_el_carrito_de_otro_cliente(): void
    {
        $cliente = User::factory()->create();
        $otro = User::factory()->create();
        Cache::put('carrito:'.$otro->id, [
            ['producto_id' => 99, 'nombre' => 'Artículo de otro cliente', 'cantidad' => 1, 'precio_centavos' => 9999],
        ]);
        Sanctum::actingAs($cliente);

        $this->getJson('/api/carrito?cliente_id='.$otro->id)->assertOk()->assertJsonPath('data.cliente_id', $cliente->id)
            ->assertJsonPath('data.articulos', []);
        $this->getJson('/api/carrito/resumen?cliente_id='.$otro->id)->assertOk()
            ->assertJsonPath('data.cliente_id', $cliente->id)->assertJsonPath('data.total', 0);
    }

    public function test_carrito_vacio_devuelve_totales_cero(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/carrito/resumen')->assertOk()->assertJsonPath('data.productos_distintos', 0)
            ->assertJsonPath('data.cantidad_total', 0)->assertJsonPath('data.total', 0);
    }

    public function test_token_real_de_sanctum_autentica_al_cliente(): void
    {
        $cliente = User::factory()->create();
        $token = $cliente->createToken('prueba', ['*'], now()->addHour())->plainTextToken;

        $this->withToken($token)->getJson('/api/carrito')->assertOk()->assertJsonPath('data.cliente_id', $cliente->id);
    }

    public function test_token_caducado_no_da_acceso(): void
    {
        $cliente = User::factory()->create();
        $token = $cliente->createToken('caducado', ['*'], now()->subMinute())->plainTextToken;

        $this->withToken($token)->getJson('/api/carrito')->assertUnauthorized();
    }

    public function test_rutas_anteriores_ya_no_existen(): void
    {
        $this->getJson('/api/productos')->assertNotFound();
        $this->getJson('/api/categorias')->assertNotFound();
    }
}
