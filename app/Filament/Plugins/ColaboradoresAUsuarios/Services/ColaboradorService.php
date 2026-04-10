<?php

namespace App\Filament\Plugins\ColaboradoresAUsuarios\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ColaboradorService
{
    /**
     * Crea un acceso para un colaborador
     *
     * @param string $email
     * @return array ['success' => bool, 'message' => string]
     */
    public function crearAcceso(string $email): array
    {
        // 1. Verificar si el email ya existe en users local
        if (User::where('email', $email)->exists()) {
            return [
                'success' => false,
                'message' => '¿Ya tienes acceso? Deseas recuperar contraseña?',
                'type' => 'already_exists',
            ];
        }

        // 2. Buscar el email en la BD externa tabla secc_users
        try {
            $colaborador = DB::connection('erp')->table('secc_users')
                ->where('email', $email)
                ->first();

            if (!$colaborador) {
                return [
                    'success' => false,
                    'message' => 'Colaborador no encontrado, contacta con la administración',
                    'type' => 'not_found',
                ];
            }

            // 3. Validar que active = 'Y'
            if ($colaborador->active !== 'Y') {
                return [
                    'success' => false,
                    'message' => 'Cuenta inactiva, contactar con Administración',
                    'type' => 'inactive',
                ];
            }

            // 4. Generar contraseña provisional (8 caracteres: letras y números)
            $password = $this->generatePassword();

            // 5. Crear usuario
            $user = User::create([
                'name' => $colaborador->name ?? 'Colaborador',
                'email' => $email,
                'password' => bcrypt($password),
                'login' => $email, // opcional: sync con ERP
            ]);

            // 6. Asignar roles (traductor + revisor)
            $user->assignRole(['traductor', 'revisor']);

            // 7. Enviar email con contraseña
            try {
                \Mail::to($email)->send(new \App\Filament\Plugins\ColaboradoresAUsuarios\Mail\ColaboradorCredentialsMail(
                    $email,
                    $password
                ));

                return [
                    'success' => true,
                    'message' => 'Correo enviado a tu email con tu contraseña',
                    'type' => 'created',
                ];
            } catch (\Exception $emailException) {
                // Usuario creado pero email falló
                \Log::error('Error al enviar email de credenciales a ' . $email . ': ' . $emailException->getMessage());

                return [
                    'success' => false,
                    'message' => 'Se ha detectado un error, favor contactar con Administración',
                    'type' => 'email_error',
                ];
            }
        } catch (\Exception $e) {
            \Log::error('Error al crear acceso de colaborador: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Se ha detectado un error, favor contactar con Administración',
                'type' => 'error',
            ];
        }
    }

    /**
     * Genera una contraseña aleatoria de 8 caracteres (letras y números)
     */
    private function generatePassword(): string
    {
        // Caracteres disponibles: letras mayúsculas, minúsculas y números
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $password = '';

        for ($i = 0; $i < 8; $i++) {
            $password .= $characters[random_int(0, strlen($characters) - 1)];
        }

        return $password;
    }
}
