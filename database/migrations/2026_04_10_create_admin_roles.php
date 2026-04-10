<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Crear roles si no existen
        $roles = ['super_admin', 'admin', 'revisor', 'traductor'];

        foreach ($roles as $roleName) {
            if (!Role::where('name', $roleName)->exists()) {
                Role::create(['name' => $roleName, 'guard_name' => 'web']);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar roles
        $roles = ['super_admin', 'admin', 'revisor', 'traductor'];

        foreach ($roles as $roleName) {
            Role::where('name', $roleName)->delete();
        }
    }
};
