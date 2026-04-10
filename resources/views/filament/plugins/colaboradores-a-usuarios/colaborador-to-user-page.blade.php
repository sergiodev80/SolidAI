@extends('filament::layouts.base')

@section('content')
<div style="min-height: 100vh; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
    <div style="width: 100%; max-width: 400px; padding: 40px; background: white; border-radius: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.2);">
        <h1 style="text-align: center; color: #333; margin-bottom: 10px; font-size: 24px;">Crear Acceso</h1>
        <p style="text-align: center; color: #666; margin-bottom: 30px; font-size: 14px;">¿Eres un colaborador sin usuario?</p>

        <form id="colaborador-form" style="display: flex; flex-direction: column; gap: 20px;">
            @csrf

            <div>
                <label for="email" style="display: block; font-weight: 600; color: #333; margin-bottom: 8px; font-size: 14px;">Email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    required
                    placeholder="tu@email.com"
                    style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; box-sizing: border-box;"
                >
                <span id="email-error" style="color: #dc2626; font-size: 12px; margin-top: 4px; display: none;"></span>
            </div>

            <button
                type="submit"
                id="submit-btn"
                style="width: 100%; padding: 12px; background-color: #667eea; color: white; border: none; border-radius: 5px; font-weight: 600; cursor: pointer; font-size: 14px; transition: background-color 0.3s;"
            >
                Crear Acceso
            </button>
        </form>

        <!-- Mensaje de éxito -->
        <div id="success-message" style="display: none; padding: 15px; background-color: #dcfce7; border: 1px solid #22c55e; border-radius: 5px; color: #16a34a; margin-top: 20px; text-align: center;">
            <p id="success-text" style="margin: 0; font-size: 14px;"></p>
        </div>

        <!-- Mensaje de error -->
        <div id="error-message" style="display: none; padding: 15px; background-color: #fee2e2; border: 1px solid #ef4444; border-radius: 5px; color: #dc2626; margin-top: 20px; text-align: center;">
            <p id="error-text" style="margin: 0; font-size: 14px;"></p>
        </div>

        <p style="text-align: center; margin-top: 20px; font-size: 13px; color: #666;">
            ¿Ya tienes usuario? <a href="{{ route('filament.admin.auth.login') }}" style="color: #667eea; text-decoration: none; font-weight: 600;">Inicia sesión</a>
        </p>
    </div>
</div>

<script>
document.getElementById('colaborador-form').addEventListener('submit', async (e) => {
    e.preventDefault();

    const email = document.getElementById('email').value;
    const emailError = document.getElementById('email-error');
    const successMessage = document.getElementById('success-message');
    const errorMessage = document.getElementById('error-message');
    const successText = document.getElementById('success-text');
    const errorText = document.getElementById('error-text');
    const submitBtn = document.getElementById('submit-btn');

    // Limpiar mensajes previos
    emailError.style.display = 'none';
    successMessage.style.display = 'none';
    errorMessage.style.display = 'none';

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
            // Éxito
            successText.textContent = result.message;
            successMessage.style.display = 'block';
            document.getElementById('colaborador-form').reset();
            document.getElementById('email').focus();
        } else {
            // Error
            errorText.textContent = result.message;
            errorMessage.style.display = 'block';
        }
    } catch (error) {
        console.error('Error:', error);
        errorText.textContent = 'Error al procesar la solicitud. Intenta más tarde.';
        errorMessage.style.display = 'block';
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Crear Acceso';
    }
});
</script>
@endsection
