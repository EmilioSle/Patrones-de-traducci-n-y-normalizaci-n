<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class PublicacionController extends Controller
{
    /**
     * Publicaciones de demostración para la práctica académica.
     *
     * @var array<int, array{id: int, titulo: string, tipo: string, categoria: string, descripcion: string, fecha: string}>
     */
    private const PUBLICACIONES = [
        ['id' => 1, 'titulo' => 'Nueva colección de tortas', 'tipo' => 'novedad', 'categoria' => 'Repostería', 'descripcion' => 'Presentamos tortas de chocolate para celebraciones.', 'fecha' => '2026-09-21'],
        ['id' => 2, 'titulo' => 'Tazas para un evento empresarial', 'tipo' => 'trabajo realizado', 'categoria' => 'Sublimación', 'descripcion' => 'Trabajo de personalización de tazas para un evento.', 'fecha' => '2026-09-20'],
        ['id' => 3, 'titulo' => 'Promoción de cajas de regalo', 'tipo' => 'promoción', 'categoria' => 'Detalles', 'descripcion' => 'Promoción de demostración en cajas con dulces y tarjeta.', 'fecha' => '2026-09-19'],
    ];

    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Listado de publicaciones',
            'data' => self::PUBLICACIONES,
        ]);
    }

    public function show(string $id): JsonResponse
    {
        foreach (self::PUBLICACIONES as $publicacion) {
            if ((string) $publicacion['id'] === $id) {
                return response()->json([
                    'success' => true,
                    'message' => 'Publicación encontrada',
                    'data' => $publicacion,
                ]);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Publicación no encontrada',
            'data' => null,
        ], 404);
    }
}
