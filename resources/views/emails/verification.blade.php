<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Código de verificación</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
        .wrapper { max-width: 600px; margin: 40px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .header { background-color: #252729; padding: 24px; text-align: center; }
        .header h1 { color: #fff; margin: 0; font-size: 22px; }
        .body { padding: 32px; color: #333; text-align: center; }
        .body p { line-height: 1.6; }
        .code { display: inline-block; margin: 20px 0; padding: 16px 40px; background-color: #f0f0f0; border-radius: 8px; font-size: 36px; font-weight: bold; letter-spacing: 10px; color: #252729; border: 2px dashed #ccc; }
        .footer { padding: 16px 32px; background-color: #f4f4f4; font-size: 12px; color: #888; text-align: center; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>Código de verificación</h1>
        </div>
        <div class="body">
            <p>Hola, <strong>{{ $user->name }}</strong>.</p>
            <p>Usa el siguiente código para completar tu inicio de sesión. Es válido por <strong>10 minutos</strong>.</p>
            <div class="code">{{ $code }}</div>
            <p style="font-size: 13px; color: #666;">
                Si no intentaste iniciar sesión, ignora este correo y considera cambiar tu contraseña.
            </p>
        </div>
    </div>
</body>
</html>
