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
        // Para cada plugin, mantener solo la versión más reciente como activa
        \DB::statement("
            UPDATE plugin_versions
            SET is_active = CASE
                WHEN id = (
                    SELECT id FROM (
                        SELECT id FROM plugin_versions pv2
                        WHERE pv2.plugin_slug = plugin_versions.plugin_slug
                        ORDER BY pv2.created_at DESC
                        LIMIT 1
                    ) AS latest
                ) THEN 1
                ELSE 0
            END
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverse necesario, ya que esto es una corrección de datos
    }
};
