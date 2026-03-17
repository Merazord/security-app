<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Activación de cuenta</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
        .wrapper { max-width: 600px; margin: 40px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .header { background-color: #252729; padding: 24px; text-align: center; }
        .header h1 { color: #fff; margin: 0; font-size: 22px; }
        .body { padding: 32px; color: #333; }
        .body p { line-height: 1.6; }
        .btn { display: inline-block; margin-top: 20px; padding: 12px 28px; background-color: #252729; color: #fff !important; text-decoration: none; border-radius: 6px; font-weight: bold; }
        .footer { padding: 16px 32px; background-color: #f4f4f4; font-size: 12px; color: #888; text-align: center; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>Activa tu cuenta</h1>
        </div>
        <div class="body">
            <p>Hola, <strong>{{ $user->name }}</strong>.</p>
            <p>Gracias por registrarte. Haz clic en el botón de abajo para activar tu cuenta. Este enlace expirará en <strong>24 horas</strong>.</p>
            <a href="{{ $url }}" class="btn">Activar cuenta</a>
            <p style="margin-top: 24px; font-size: 13px; color: #666;">
                Si no solicitaste este registro, puedes ignorar este correo.
            </p>
        </div>
    </div>
</body>
</html>
