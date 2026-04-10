<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Acceso como Colaborador</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-blue-600 to-purple-700 min-h-screen">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="w-full max-w-md">
            <div class="bg-white rounded-lg shadow-2xl p-8">
                <h1 class="text-3xl font-bold text-center text-gray-800 mb-2">Crear Acceso</h1>
                <p class="text-center text-gray-600 mb-8">¿Eres un colaborador sin usuario?</p>

                <form id="colaborador-form" class="space-y-4">
                    @csrf

                    <div>
                        <label for="email" class="block text-sm font-semibold text-gray-800 mb-2">Email</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            required
                            placeholder="tu@email.com"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition"
                        />
                        <span id="email-error" class="text-red-600 text-xs mt-1 hidden"></span>
                    </div>

                    <button
                        type="submit"
                        id="submit-btn"
                        class="w-full py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-colors duration-200"
                    >
                        Crear Acceso
                    </button>
                </form>

                <!-- Mensaje de éxito -->
                <div id="success-message" class="hidden mt-6 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700 text-center">
                    <p id="success-text" class="text-sm font-medium"></p>
                </div>

                <!-- Mensaje de error -->
                <div id="error-message" class="hidden mt-6 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700 text-center">
                    <p id="error-text" class="text-sm font-medium"></p>
                </div>

                <p class="text-center text-sm text-gray-600 mt-6">
                    ¿Ya tienes usuario?
                    <a href="{{ route('filament.admin.auth.login') }}" class="text-blue-600 hover:text-blue-700 font-semibold">Inicia sesión</a>
                </p>
            </div>
        </div>
    </div>

    <script>
        const form = document.getElementById('colaborador-form');
        const emailInput = document.getElementById('email');
        const emailError = document.getElementById('email-error');
        const successMessage = document.getElementById('success-message');
        const errorMessage = document.getElementById('error-message');
        const successText = document.getElementById('success-text');
        const errorText = document.getElementById('error-text');
        const submitBtn = document.getElementById('submit-btn');

        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const email = emailInput.value;

            // Limpiar mensajes previos
            emailError.classList.add('hidden');
            successMessage.classList.add('hidden');
            errorMessage.classList.add('hidden');

            // Deshabilitar botón durante el envío
            submitBtn.disabled = true;
            submitBtn.textContent = 'Procesando...';

            try {
                const response = await fetch('{{ route("colaborador.crear-acceso") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('[name="_token"]').value,
                    },
                    body: JSON.stringify({ email }),
                });

                const result = await response.json();

                if (result.success) {
                    successText.textContent = result.message;
                    successMessage.classList.remove('hidden');
                    form.reset();
                    emailInput.focus();
                } else {
                    errorText.textContent = result.message;
                    errorMessage.classList.remove('hidden');
                }
            } catch (error) {
                console.error('Error:', error);
                errorText.textContent = 'Error al procesar la solicitud. Intenta más tarde.';
                errorMessage.classList.remove('hidden');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Crear Acceso';
            }
        });
    </script>
</body>
</html>
