<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['web', 'auth'])->prefix('admin/presupuestos')->name('presupuestos.')->group(function () {
    Route::get('/documentos/{presupuestoId}', [\App\Http\Controllers\Presupuestos\DocumentosController::class, 'lista'])
        ->name('documentos.lista');
});
