<?php

namespace App\Services;

use App\Models\PresupAdjAsignacion;
use Illuminate\Support\Facades\Auth;

class PermissionService
{
    /**
     * Verifica si el usuario puede acceder a una asignación
     * - Admin: puede ver todas
     * - Traductor/Revisor: solo ve sus asignaciones
     */
    public static function canAccessAsignacion(int $idAsignacion, ?string $login = null): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        // Usuario ID 1 es super admin implícito (convención Laravel)
        if ($user->id === 1) {
            return true;
        }

        // Administrador ve todo (verificar roles)
        if ($user->hasRole('super_admin') || $user->hasRole('admin')) {
            return true;
        }

        // Obtener login del usuario (preferir login pasado, luego login, luego email)
        $userLogin = $login ?? $user->login ?? $user->email ?? null;
        if (!$userLogin) {
            return false;
        }

        // Traductor/Revisor: verificar que la asignación es suya
        $asignacion = PresupAdjAsignacion::find($idAsignacion);
        if (!$asignacion) {
            return false;
        }

        return $asignacion->login === $userLogin;
    }

    /**
     * Obtiene el login del usuario autenticado
     */
    public static function getUserLogin(): ?string
    {
        $user = Auth::user();
        return $user?->login ?? $user?->email ?? null;
    }

    /**
     * Verifica si el usuario es administrador
     */
    public static function isAdmin(): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        // Usuario ID 1 es super admin implícito
        if ($user->id === 1) {
            return true;
        }

        return $user->hasRole('super_admin') || $user->hasRole('admin');
    }
}
