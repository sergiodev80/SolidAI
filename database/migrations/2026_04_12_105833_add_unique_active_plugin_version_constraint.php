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
        // Agregar un índice único para garantizar que solo una versión por plugin está activa
        \DB::statement("
            CREATE UNIQUE INDEX idx_unique_active_plugin_version
            ON plugin_versions(plugin_slug)
            WHERE is_active = 1
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \DB::statement("DROP INDEX IF EXISTS idx_unique_active_plugin_version");
    }
};
