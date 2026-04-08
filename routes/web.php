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

    // Traducción con IA
    Route::post('/admin/traduccion/traducir-ai/{id_asignacion}', [\App\Http\Controllers\TraduccionAiController::class, 'traducir'])
        ->name('traduccion.traducir-ai');

});
