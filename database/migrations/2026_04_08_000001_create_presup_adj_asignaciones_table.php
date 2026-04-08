<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'erp';

    public function up(): void
    {
        if (!Schema::connection('erp')->hasTable('presup_adj_asignaciones')) {
            Schema::connection('erp')->create('presup_adj_asignaciones', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('id_adjun');
                $table->string('login');
                $table->enum('rol', ['traductor', 'revisor']);
                $table->unsignedInteger('pag_inicio');
                $table->unsignedInteger('pag_fin');
                $table->enum('estado', ['Asignado', 'En Traducción', 'En Revisión', 'Aceptado', 'Impreso', 'Entregado'])
                      ->default('Asignado');
                $table->timestamp('created_at')->useCurrent();

                $table->index(['id_adjun', 'rol']);
            });
        }
    }

    public function down(): void
    {
        Schema::connection('erp')->dropIfExists('presup_adj_asignaciones');
    }
};
