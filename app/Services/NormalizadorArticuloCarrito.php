<?php

namespace App\Services;

use InvalidArgumentException;

class NormalizadorArticuloCarrito
{
    public function desdeFormulario(array $entrada): array
    {
        $precio = $entrada['precio'] ?? null;
        if (! is_string($precio) || ! preg_match('/^(0|[1-9]\d{0,6})(?:,(\d{1,2}))?$/D', trim($precio), $partes)) {
            throw new InvalidArgumentException('Precio del formulario inválido; use una cantidad decimal con coma.');
        }

        $centavos = (int) $partes[1] * 100 + (int) str_pad($partes[2] ?? '', 2, '0');

        return $this->validar($entrada['productoId'] ?? null, $entrada['nombreProducto'] ?? null, $entrada['unidades'] ?? null, $centavos);
    }

    public function desdeServicio(array $entrada): array
    {
        $producto = $entrada['product'] ?? null;
        if (! is_array($producto)) {
            throw new InvalidArgumentException('El producto del servicio es obligatorio.');
        }

        return $this->validar($producto['id'] ?? null, $producto['name'] ?? null, $entrada['quantity'] ?? null, $entrada['unit_price_cents'] ?? null);
    }

    private function validar(mixed $id, mixed $nombre, mixed $cantidad, mixed $centavos): array
    {
        if (! is_int($id) || $id < 1) {
            throw new InvalidArgumentException('El ID del producto debe ser un entero positivo.');
        }
        if (! is_string($nombre) || trim($nombre) === '') {
            throw new InvalidArgumentException('El nombre del producto es obligatorio.');
        }
        if (! is_int($cantidad) || $cantidad < 1 || $cantidad > 10000) {
            throw new InvalidArgumentException('La cantidad debe ser un entero entre 1 y 10000.');
        }
        if (! is_int($centavos) || $centavos < 0 || $centavos > 999999999) {
            throw new InvalidArgumentException('El precio debe ser un entero de centavos entre 0 y 999999999.');
        }

        return ['producto_id' => $id, 'nombre' => trim($nombre), 'cantidad' => $cantidad, 'precio_centavos' => $centavos];
    }
}
