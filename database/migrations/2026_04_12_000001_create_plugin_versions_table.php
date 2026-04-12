<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('plugin_versions', function (Blueprint $table) {
            $table->id();
            $table->string('plugin_slug'); // ej: 'traduccion', 'asignar'
            $table->string('plugin_name'); // ej: 'Traducción AI'
            $table->string('version'); // ej: '1.0.0'
            $table->string('version_type')->default('patch'); // major, minor, patch
            $table->text('description')->nullable(); // Descripción de cambios
            $table->text('changelog')->nullable(); // Detalle completo de cambios
            $table->string('github_repository')->nullable(); // URL del repositorio
            $table->string('github_tag')->nullable(); // Tag en GitHub
            $table->string('github_release_url')->nullable(); // URL del release
            $table->string('file_path')->nullable(); // Ruta del archivo backup
            $table->integer('file_size')->nullable(); // Tamaño del archivo
            $table->string('file_hash')->nullable(); // Hash SHA256 del archivo
            $table->string('previous_version')->nullable(); // Versión anterior
            $table->string('created_by')->nullable(); // Usuario que creó la versión
            $table->text('notes')->nullable(); // Notas adicionales
            $table->boolean('is_active')->default(true); // Si esta versión está activa
            $table->boolean('is_stable')->default(true); // Si es versión estable
            $table->timestamp('released_at')->nullable(); // Fecha de release oficial
            $table->timestamps();

            // Índices
            $table->index(['plugin_slug', 'version']);
            $table->index(['plugin_slug', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plugin_versions');
    }
};
