<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\NormalizadorArticuloCarrito;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class PrepararDemoApi extends Command
{
    protected $signature = 'demo:preparar-api';

    protected $description = 'Prepara un cliente y un carrito de ejemplo y genera un token local de 24 horas';

    public function handle(): int
    {
        if (! app()->environment('local')) {
            $this->error('Este comando de demostración solo está disponible en el entorno local.');

            return self::FAILURE;
        }

        $cliente = User::firstOrCreate(
            ['email' => 'demo.carrito@nexocommerce.test'],
            ['name' => 'Cliente de demostración', 'password' => Str::random(48)],
        );

        $normalizador = app(NormalizadorArticuloCarrito::class);
        Cache::forever('carrito:'.$cliente->id, [
            $normalizador->desdeFormulario(['productoId' => 1, 'nombreProducto' => 'Taza personalizada', 'unidades' => 2, 'precio' => '8,50']),
            $normalizador->desdeServicio(['product' => ['id' => 2, 'name' => 'Caja de regalo'], 'quantity' => 1, 'unit_price_cents' => 1800]),
        ]);

        $token = $cliente->createToken('practica-api', ['*'], now()->addDay())->plainTextToken;
        $this->info('Cliente de prueba preparado. Carrito: 2 productos distintos, 3 unidades, USD 35.00.');
        $this->line('En Postman: Authorization > Bearer Token. Copia únicamente el token de la siguiente línea:');
        $this->line($token);
        $this->comment('El token caduca en 24 horas. No lo incluyas en capturas ni en el ZIP.');

        return self::SUCCESS;
    }
}
