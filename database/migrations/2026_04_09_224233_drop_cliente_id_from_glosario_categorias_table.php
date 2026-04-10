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
            $table->dropForeign(['cliente_id']);
            $table->dropUnique(['nombre', 'parent_id', 'cliente_id']);
            $table->dropColumn('cliente_id');
            $table->unique(['nombre', 'parent_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('glosario_categorias', function (Blueprint $table) {
            $table->dropUnique(['nombre', 'parent_id']);
            $table->unsignedBigInteger('cliente_id')->nullable();
            $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('cascade');
            $table->unique(['nombre', 'parent_id', 'cliente_id']);
        });
    }
};
