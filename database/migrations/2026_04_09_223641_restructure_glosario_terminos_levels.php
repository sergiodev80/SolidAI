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
        Schema::table('glosario_terminos', function (Blueprint $table) {
            // Cambiar enum de nivel
            $table->enum('nivel', ['empresa', 'categoria', 'cliente', 'documento'])
                ->change();
        });

        Schema::table('glosario_categorias', function (Blueprint $table) {
            // Actualizar enum de categorías
            $table->enum('nivel', ['empresa', 'categoria', 'cliente', 'documento'])
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('glosario_terminos', function (Blueprint $table) {
            $table->enum('nivel', ['empresa', 'cliente', 'documento'])
                ->change();
        });

        Schema::table('glosario_categorias', function (Blueprint $table) {
            $table->enum('nivel', ['empresa', 'cliente', 'documento'])
                ->change();
        });
    }
};
