<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\NormalizadorArticuloCarrito;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NormalizadorArticuloCarritoTest extends TestCase
{
    use RefreshDatabase;

    public function test_los_dos_origenes_producen_el_mismo_articulo_y_funcionan_en_el_carrito(): void
    {
        $normalizador = new NormalizadorArticuloCarrito;
        $formulario = $normalizador->desdeFormulario(['productoId' => 1, 'nombreProducto' => ' Taza personalizada ', 'unidades' => 2, 'precio' => '8,50']);
        $servicio = $normalizador->desdeServicio(['product' => ['id' => 1, 'name' => 'Taza personalizada'], 'quantity' => 2, 'unit_price_cents' => 850]);
        $this->assertSame(['producto_id' => 1, 'nombre' => 'Taza personalizada', 'cantidad' => 2, 'precio_centavos' => 850], $formulario);
        $this->assertSame($formulario, $servicio);

        $cliente = User::factory()->create();
        Cache::put('carrito:'.$cliente->id, [$formulario]);
        Sanctum::actingAs($cliente);
        $this->getJson('/api/carrito')->assertOk()->assertJsonPath('data.articulos.0.precio_unitario', 8.5)->assertJsonPath('data.articulos.0.subtotal', 17);
    }

    public function test_rechaza_precio_mal_formado(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Precio del formulario inválido');
        (new NormalizadorArticuloCarrito)->desdeFormulario(['productoId' => 1, 'nombreProducto' => 'Taza', 'unidades' => 2, 'precio' => '8,5abc']);
    }

    public function test_rechaza_campo_faltante_del_servicio(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('El nombre del producto es obligatorio.');
        (new NormalizadorArticuloCarrito)->desdeServicio(['product' => ['id' => 1], 'quantity' => 2, 'unit_price_cents' => 850]);
    }
}
