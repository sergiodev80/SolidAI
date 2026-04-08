<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['web', 'auth'])->group(function () {
    Route::prefix('admin/presupuestos')->name('presupuestos.')->group(function () {
        Route::get('/documentos/{presupuestoId}', [\App\Http\Controllers\Presupuestos\DocumentosController::class, 'lista'])
            ->name('documentos.lista');
    });

    // Proxy para descargar archivos desde FTP
    Route::get('/ftp-file/{filename}', [\App\Http\Controllers\FtpFileController::class, 'stream'])
        ->where('filename', '.*')
        ->name('ftp-file.stream');
});
