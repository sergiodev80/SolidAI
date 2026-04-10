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
            $table->dropColumn(['definicion_original', 'definicion_traducido']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('glosario_terminos', function (Blueprint $table) {
            $table->text('definicion_original')->nullable()->after('id_idiom_original');
            $table->text('definicion_traducido')->nullable()->after('termino_traducido');
        });
    }
};
