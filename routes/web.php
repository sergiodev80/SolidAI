<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Login alternativo con opción de colaborador
Route::get('/admin/login-alt', function () {
    return view('auth.login-alt');
})->name('auth.login-alt');

// Colaborador a Usuario - rutas públicas (sin autenticación)
Route::prefix('admin/colabtouser')->name('colaborador.')->group(function () {
    Route::get('/', function () {
        return view('filament.plugins.colaboradores-a-usuarios.colaborador-to-user-page');
    })->name('page');

    Route::post('/crear-acceso', [\App\Filament\Plugins\ColaboradoresAUsuarios\Http\Controllers\ColaboradorToUserController::class, 'crearAcceso'])
        ->name('crear-acceso');
});

Route::middleware(['web', 'auth'])->group(function () {
    // Plugin Versions
    Route::prefix('filament/plugin-versions')->name('filament.')->group(function () {
        Route::get('/{versionId}/download', [\App\Http\Controllers\PluginVersionController::class, 'download'])
            ->name('download-plugin-version');
        Route::get('/{versionId}/restore', [\App\Http\Controllers\PluginVersionController::class, 'restore'])
            ->name('restore-plugin-version');
    });

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

    // Eliminar traducción (V2)
    Route::post('/admin/traduccion/eliminar-traduccion/{id_asignacion}', [\App\Http\Controllers\TraduccionAiController::class, 'eliminarTraduccion'])
        ->name('traduccion.eliminar-traduccion');

    // Revisión de asignaciones
    Route::post('/admin/traduccion/aprobar/{id_asignacion}', [\App\Http\Controllers\TraduccionAiController::class, 'aprobar'])
        ->name('traduccion.aprobar');

    Route::post('/admin/traduccion/rechazar/{id_asignacion}', [\App\Http\Controllers\TraduccionAiController::class, 'rechazar'])
        ->name('traduccion.rechazar');

    Route::post('/admin/traduccion/comentar/{id_asignacion}', [\App\Http\Controllers\TraduccionAiController::class, 'comentar'])
        ->name('traduccion.comentar');

    // OnlyOffice Callbacks
    Route::post('/api/onlyoffice/callback', [\App\Http\Controllers\OnlyOfficeCallbackController::class, 'callback'])
        ->name('onlyoffice.callback');

    // API Glosario
    Route::prefix('api/glosario')->name('api.glosario.')->group(function () {
        Route::post('/buscar', [\App\Http\Controllers\Api\GlosarioController::class, 'buscar'])->name('buscar');
        Route::post('/para-traduccion', [\App\Http\Controllers\Api\GlosarioController::class, 'paraTraduccion'])->name('para-traduccion');
        Route::post('/registrar-uso', [\App\Http\Controllers\Api\GlosarioController::class, 'registrarUso'])->name('registrar-uso');
        Route::post('/exportar', [\App\Http\Controllers\Api\GlosarioController::class, 'exportar'])->name('exportar');
    });

});
