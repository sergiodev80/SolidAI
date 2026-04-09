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

    // Extracción de documento (sin traducción)
    Route::post('/admin/traduccion/extraer-documento/{id_asignacion}', [\App\Http\Controllers\TraduccionAiController::class, 'extraerDocumento'])
        ->name('traduccion.extraer-documento');

    // Traducción con IA
    Route::post('/admin/traduccion/traducir-ai/{id_asignacion}', [\App\Http\Controllers\TraduccionAiController::class, 'traducir'])
        ->name('traduccion.traducir-ai');

    // Guardar idiomas de asignación
    Route::post('/admin/traduccion/guardar-idiomas/{id_asignacion}', [\App\Http\Controllers\TraduccionAiController::class, 'guardarIdiomas'])
        ->name('traduccion.guardar-idiomas');

    // OnlyOffice Callbacks
    Route::post('/api/onlyoffice/callback', [\App\Http\Controllers\OnlyOfficeCallbackController::class, 'callback'])
        ->name('onlyoffice.callback');

});
