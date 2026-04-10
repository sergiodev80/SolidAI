<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Acceso como Colaborador</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Colores de Filament Amber */
        :root {
            --color-primary: 251 191 36; /* Amber-400 */
            --color-primary-dark: 217 119 6; /* Amber-700 */
        }
        .btn-primary {
            background-color: rgb(var(--color-primary));
        }
        .btn-primary:hover {
            background-color: rgb(var(--color-primary-dark));
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="flex min-h-screen items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
        <div class="w-full max-w-md">
            <div class="rounded-lg bg-white p-8 shadow-md">
                <!-- Header -->
                <div class="mb-8 text-center">
                    <h1 class="text-2xl font-bold tracking-tight text-gray-900">Crear Acceso</h1>
                    <p class="mt-2 text-sm text-gray-600">¿Eres un colaborador sin usuario?</p>
                </div>

                <!-- Form -->
                <form id="colaborador-form" class="space-y-6">
                    @csrf

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">
                            Correo Electrónico
                        </label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            autocomplete="email"
                            required
                            placeholder="tu@email.com"
                            class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm focus:border-amber-400 focus:outline-none focus:ring-amber-400 sm:text-sm"
                        />
                        <span id="email-error" class="mt-1 hidden text-sm text-red-600"></span>
                    </div>

                    <button
                        type="submit"
                        id="submit-btn"
                        class="btn-primary w-full rounded-lg py-2 font-medium text-gray-900 transition-colors duration-200"
                    >
                        Crear Acceso
                    </button>
                </form>

                <!-- Success Message -->
                <div id="success-message" class="hidden rounded-lg bg-green-50 p-4">
                    <div class="flex items-center">
                        <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        <p id="success-text" class="ml-3 text-sm font-medium text-green-800"></p>
                    </div>
                </div>

                <!-- Error Message -->
                <div id="error-message" class="hidden rounded-lg bg-red-50 p-4">
                    <div class="flex items-center">
                        <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                        <p id="error-text" class="ml-3 text-sm font-medium text-red-800"></p>
                    </div>
                </div>

                <!-- Footer -->
                <p class="mt-6 text-center text-sm text-gray-600">
                    ¿Ya tienes usuario?
                    <a href="{{ route('filament.admin.auth.login') }}" class="font-medium text-amber-600 hover:text-amber-500">
                        Inicia sesión
                    </a>
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

            // Clear previous messages
            emailError.classList.add('hidden');
            successMessage.classList.add('hidden');
            errorMessage.classList.add('hidden');

            // Disable button
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
