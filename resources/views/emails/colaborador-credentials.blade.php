<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .header {
            background-color: #007bff;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            padding: 20px;
            background-color: white;
        }
        .credentials {
            background-color: #f0f0f0;
            padding: 15px;
            border-left: 4px solid #007bff;
            margin: 20px 0;
            font-family: monospace;
        }
        .credentials p {
            margin: 10px 0;
        }
        .footer {
            text-align: center;
            font-size: 12px;
            color: #666;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Bienvenido/a</h1>
        </div>

        <div class="content">
            <p>Hola,</p>

            <p>Tu acceso como colaborador ha sido creado exitosamente. A continuación encontrarás tus credenciales de acceso:</p>

            <div class="credentials">
                <p><strong>Email:</strong> {{ $email }}</p>
                <p><strong>Contraseña provisional:</strong> {{ $password }}</p>
            </div>

            <p><strong>Importante:</strong> Por seguridad, te recomendamos cambiar tu contraseña después de tu primer acceso.</p>

            <p>Puedes acceder a tu cuenta haciendo clic en el siguiente botón:</p>

            <center>
                <a href="{{ $loginUrl }}" class="button">Ir al Login</a>
            </center>

            <p style="margin-top: 20px;">Si tienes problemas para acceder, contacta con el área de administración.</p>

            <div class="footer">
                <p>Este es un correo automático, por favor no respondas.</p>
            </div>
        </div>
    </div>
</body>
</html>
