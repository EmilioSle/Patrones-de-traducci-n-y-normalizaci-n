<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ResumenSolicitudController extends Controller
{
    public function show(Request $request, int $id): JsonResponse
    {
        $cid = $request->header('X-Correlation-ID') ?: (string) Str::uuid();

        try {
            $usuario = Http::withHeaders(['X-Correlation-ID' => $cid])
                ->connectTimeout(3)->timeout(3)
                ->get(rtrim(config('services.orquestacion.users_url'), '/').'/usuarios/7');
            $estado = Http::withHeaders(['X-Correlation-ID' => $cid])
                ->connectTimeout(3)->timeout(3)
                ->get(rtrim(config('services.orquestacion.status_url'), '/').'/estados/'.$id);

            if (! $usuario->successful() || ! $estado->successful()) {
                throw new \RuntimeException('Servicio remoto no disponible');
            }

            Log::info('Orquestación completada', ['cid' => $cid, 'solicitud_id' => $id]);

            return response()->json([
                'solicitud_id' => $id,
                'usuario' => $usuario->json(),
                'estado' => $estado->json(),
                'correlation_id' => $cid,
            ])->header('X-Correlation-ID', $cid);
        } catch (Throwable $exception) {
            Log::warning('Falla de orquestación', ['cid' => $cid, 'solicitud_id' => $id]);

            return response()->json([
                'error' => 'Dependencia no disponible',
                'correlation_id' => $cid,
            ], 502)->header('X-Correlation-ID', $cid);
        }
    }
}
