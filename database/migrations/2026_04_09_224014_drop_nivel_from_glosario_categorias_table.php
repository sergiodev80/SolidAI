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
        Schema::table('glosario_categorias', function (Blueprint $table) {
            $table->dropUnique(['nombre', 'parent_id', 'nivel', 'cliente_id']);
            $table->dropColumn('nivel');
            $table->unique(['nombre', 'parent_id', 'cliente_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('glosario_categorias', function (Blueprint $table) {
            $table->dropUnique(['nombre', 'parent_id', 'cliente_id']);
            $table->enum('nivel', ['empresa', 'categoria', 'cliente', 'documento'])->default('empresa');
            $table->unique(['nombre', 'parent_id', 'nivel', 'cliente_id']);
        });
    }
};
