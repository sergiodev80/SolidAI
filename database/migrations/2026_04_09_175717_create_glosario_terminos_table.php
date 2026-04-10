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
        Schema::create('glosario_terminos', function (Blueprint $table) {
            $table->id();
            $table->string('termino');
            $table->text('definicion');
            $table->unsignedBigInteger('id_idiom'); // Referencia a tabla idiomas existente
            $table->foreign('id_idiom')->references('id_idiom')->on('idiomas')->onDelete('cascade');
            $table->unsignedBigInteger('glosario_categoria_id');
            $table->foreign('glosario_categoria_id')->references('id')->on('glosario_categorias')->onDelete('cascade');
            $table->enum('nivel', ['empresa', 'cliente', 'documento'])->default('empresa');
            $table->unsignedBigInteger('cliente_id')->nullable();
            $table->unsignedBigInteger('documento_id')->nullable(); // Relación a documentos traducidos
            $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('cascade');
            $table->enum('estado', ['borrador', 'propuesto', 'aprobado', 'rechazado'])->default('borrador');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->text('notas')->nullable();
            $table->integer('usos')->default(0); // Contador de veces usado en traducciones
            $table->timestamps();
            $table->unique(['termino', 'id_idiom', 'glosario_categoria_id', 'nivel', 'cliente_id']);
            $table->index(['estado', 'nivel']);
            $table->index(['cliente_id', 'nivel']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('glosario_terminos');
    }
};
