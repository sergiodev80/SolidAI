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
            // Renombrar campos existentes
            $table->renameColumn('termino', 'termino_original');
            $table->renameColumn('definicion', 'definicion_original');
            $table->renameColumn('id_idiom', 'id_idiom_original');

            // Agregar campos para término traducido
            $table->string('termino_traducido')->nullable()->after('termino_original');
            $table->text('definicion_traducido')->nullable()->after('definicion_original');
            $table->unsignedBigInteger('id_idiom_traducido')->nullable()->after('id_idiom_original');
            $table->text('contexto')->nullable()->after('termino_traducido');

            // Foreign key para idioma traducido
            $table->foreign('id_idiom_traducido')->references('id_idiom')->on('idiomas')->onDelete('set null');

            // Actualizar índice único
            $table->dropUnique(['termino', 'id_idiom', 'glosario_categoria_id', 'nivel', 'cliente_id']);
            $table->unique(['termino_original', 'id_idiom_original', 'glosario_categoria_id', 'nivel', 'cliente_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('glosario_terminos', function (Blueprint $table) {
            // Remover foreign key del idioma traducido
            $table->dropForeign(['id_idiom_traducido']);

            // Remover columnas nuevas
            $table->dropColumn(['termino_traducido', 'definicion_traducido', 'id_idiom_traducido', 'contexto']);

            // Renombrar de vuelta
            $table->renameColumn('termino_original', 'termino');
            $table->renameColumn('definicion_original', 'definicion');
            $table->renameColumn('id_idiom_original', 'id_idiom');

            // Restaurar índice único original
            $table->dropUnique(['termino_original', 'id_idiom_original', 'glosario_categoria_id', 'nivel', 'cliente_id']);
            $table->unique(['termino', 'id_idiom', 'glosario_categoria_id', 'nivel', 'cliente_id']);
        });
    }
};
