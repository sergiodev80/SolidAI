<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="flex items-center justify-center min-h-screen">
        <div class="w-full max-w-md">
            <div class="bg-white rounded-lg shadow-lg p-8">
                <h1 class="text-3xl font-bold text-center mb-8 text-gray-800">Iniciar Sesión</h1>

                {{-- Enlace a login de Filament original --}}
                <form method="GET" action="{{ route('filament.admin.auth.login') }}" class="mb-6">
                    <button
                        type="submit"
                        class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors"
                    >
                        Ir al Login Principal
                    </button>
                </form>

                {{-- Separador --}}
                <div class="relative mb-6">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-300"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-2 bg-white text-gray-500">O</span>
                    </div>
                </div>

                {{-- Link para Colaboradores --}}
                <div>
                    <p class="text-center text-sm text-gray-600 mb-4">¿Eres un colaborador sin usuario?</p>
                    <a
                        href="{{ url('/admin/colabtouser') }}"
                        class="w-full inline-flex items-center justify-center px-4 py-3 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors"
                    >
                        Crear Acceso como Colaborador
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
