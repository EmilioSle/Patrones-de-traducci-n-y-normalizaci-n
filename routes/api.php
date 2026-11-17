<?php

use App\Http\Controllers\Api\CarritoController;
use App\Http\Controllers\Api\PublicacionController;
use App\Http\Controllers\Api\ResumenSolicitudController;
use Illuminate\Support\Facades\Route;

Route::get('/resumen-solicitud/{id}', [ResumenSolicitudController::class, 'show'])->whereNumber('id')->name('solicitudes.resumen');

Route::get('/publicaciones', [PublicacionController::class, 'index'])->name('publicaciones.index');
Route::get('/publicaciones/{id}', [PublicacionController::class, 'show'])->whereNumber('id')->name('publicaciones.show');
Route::get('/carrito', [CarritoController::class, 'index'])->middleware('auth:sanctum')->name('carrito.index');
Route::get('/carrito/resumen', [CarritoController::class, 'resumen'])->middleware('auth:sanctum')->name('carrito.resumen');
