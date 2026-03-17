<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Reenviar Activación</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://www.google.com/recaptcha/enterprise.js?render=6LcSqoosAAAAAD04LAyD8ciu9m9kB2cvxgOzT5eV" async defer></script>
</head>
<body>
    <div class="container mt-5">
        <span class="badge bg-secondary ms-2">
            {{ env('APP_INSTANCE', 'N/A') }}
        </span>
        <h2 class="mb-3">Reenviar Correo de Activación</h2>

        @if (session('warning'))
            <div class="alert alert-warning">{{ session('warning') }}</div>
        @endif

        <div id="jsMessage" class="alert" style="display:none;" role="alert"></div>

        <div class="mb-3">
            <label for="email" class="form-label">Correo electrónico</label>
            <input type="email" class="form-control" id="email" name="email"
                autocomplete="email" placeholder="tucorreo@ejemplo.com">
            <div id="emailError" class="invalid-feedback d-block"></div>
        </div>

        <input type="hidden" id="g-recaptcha-response">

        <button type="button" id="resendBtn" class="btn btn-dark">Reenviar correo de activación</button>

        <div class="mt-3">
            <p>¿Ya activaste tu cuenta? <a href="{{ route('login.form') }}" class="btn btn-link">Inicia sesión</a></p>
        </div>
    </div>

    <script>
        const RECAPTCHA_KEY = '6LcSqoosAAAAAD04LAyD8ciu9m9kB2cvxgOzT5eV';

        document.getElementById('resendBtn').addEventListener('click', function () {
            const email     = document.getElementById('email').value.trim();
            const emailErr  = document.getElementById('emailError');
            const jsMessage = document.getElementById('jsMessage');
            const btn       = this;

            // Reset UI
            emailErr.textContent = '';
            document.getElementById('email').classList.remove('is-invalid');
            jsMessage.style.display = 'none';
            jsMessage.className = 'alert';

            // Validación frontend
            if (!email) {
                document.getElementById('email').classList.add('is-invalid');
                emailErr.textContent = 'El correo es obligatorio.';
                return;
            }
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                document.getElementById('email').classList.add('is-invalid');
                emailErr.textContent = 'Ingresa un correo electrónico válido.';
                return;
            }

            btn.disabled = true;
            btn.textContent = 'Enviando...';

            grecaptcha.enterprise.ready(function () {
                grecaptcha.enterprise.execute(RECAPTCHA_KEY, { action: 'submit' }).then(function (token) {
                    document.getElementById('g-recaptcha-response').value = token;

                    fetch('{{ route('resend.activation.email') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({
                            email: email,
                            'g-recaptcha-response': token,
                        }),
                    })
                    .then(res => res.json().then(data => ({ status: res.status, data })))
                    .then(({ status, data }) => {
                        jsMessage.style.display = 'block';
                        jsMessage.classList.add(status === 200 ? 'alert-success' : 'alert-danger');
                        jsMessage.textContent = data.message;
                    })
                    .catch(function () {
                        jsMessage.style.display = 'block';
                        jsMessage.classList.add('alert-danger');
                        jsMessage.textContent = 'Ocurrió un error. Por favor intenta de nuevo.';
                    })
                    .finally(function () {
                        btn.disabled = false;
                        btn.textContent = 'Reenviar correo de activación';
                    });
                });
            });
        });
    </script>
</body>

<style>
    body {
        background-color: rgb(37, 39, 41);
        color: #333;
        font-family: Arial, sans-serif;
    }
    .container {
        max-width: 500px;
        background-color: #fff;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        margin-top: 100px;
    }
</style>
</html>
