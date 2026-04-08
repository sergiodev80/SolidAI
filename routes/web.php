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

    // Proxy para obtener PDF como blob
    Route::get('/ftp-pdf/{filename}', [\App\Http\Controllers\FtpFileController::class, 'getPdfBlob'])
        ->where('filename', '.*')
        ->name('ftp-pdf.blob');

    // Rutas de Traducción
    Route::get('/traduccion/{id_asignacion}', [\App\Http\Controllers\TraduccionController::class, 'show'])
        ->name('traduccion.show');

    Route::post('/traduccion/{id_asignacion}/guardar', [\App\Http\Controllers\TraduccionController::class, 'guardar'])
        ->name('traduccion.guardar');

    Route::post('/traduccion/{id_asignacion}/comparar', [\App\Http\Controllers\TraduccionController::class, 'comparar'])
        ->name('traduccion.comparar');

    Route::post('/traduccion/{id_asignacion}/enviar-revision', [\App\Http\Controllers\TraduccionController::class, 'enviarRevision'])
        ->name('traduccion.enviar-revision');
});
