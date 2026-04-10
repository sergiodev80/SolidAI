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
        Schema::create('glosario_categorias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable(); // Para subcategorías
            $table->enum('nivel', ['empresa', 'cliente', 'documento'])->default('empresa');
            $table->unsignedBigInteger('cliente_id')->nullable();
            $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('cascade');
            $table->foreign('parent_id')->references('id')->on('glosario_categorias')->onDelete('cascade');
            $table->timestamps();
            $table->unique(['nombre', 'parent_id', 'nivel', 'cliente_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('glosario_categorias');
    }
};
