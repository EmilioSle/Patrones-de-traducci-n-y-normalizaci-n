<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CarritoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $articulos = array_map(fn (array $articulo): array => [
            'producto_id' => $articulo['producto_id'],
            'nombre' => $articulo['nombre'],
            'cantidad' => $articulo['cantidad'],
            'precio_unitario' => $articulo['precio_centavos'] / 100,
            'subtotal' => ($articulo['precio_centavos'] * $articulo['cantidad']) / 100,
        ], $this->articulosDelCliente($request));

        return response()->json([
            'success' => true,
            'message' => 'Artículos del carrito del cliente autenticado',
            'data' => [
                'cliente_id' => $request->user()->getAuthIdentifier(),
                'moneda' => 'USD',
                'articulos' => $articulos,
            ],
        ]);
    }

    public function resumen(Request $request): JsonResponse
    {
        $articulos = $this->articulosDelCliente($request);
        $cantidadTotal = 0;
        $totalCentavos = 0;

        foreach ($articulos as $articulo) {
            $cantidadTotal += $articulo['cantidad'];
            $totalCentavos += $articulo['precio_centavos'] * $articulo['cantidad'];
        }

        return response()->json([
            'success' => true,
            'message' => 'Resumen del carrito del cliente autenticado',
            'data' => [
                'cliente_id' => $request->user()->getAuthIdentifier(),
                'productos_distintos' => count($articulos),
                'cantidad_total' => $cantidadTotal,
                'total' => $totalCentavos / 100,
                'moneda' => 'USD',
            ],
        ]);
    }

    /**
     * Carrito académico almacenado en caché por ID del usuario autenticado.
     * Los importes se guardan en centavos para sumar con enteros.
     *
     * @return array<int, array{producto_id: int, nombre: string, cantidad: int, precio_centavos: int}>
     */
    private function articulosDelCliente(Request $request): array
    {
        return Cache::get('carrito:'.$request->user()->getAuthIdentifier(), []);
    }
}
