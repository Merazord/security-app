<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://www.google.com/recaptcha/enterprise.js?render=6LcSqoosAAAAAD04LAyD8ciu9m9kB2cvxgOzT5eV" async
        defer></script>
</head>

<body>
    <div class="container mt-5">
        <span class="badge bg-secondary ms-2">
            {{ config('app.instance', 'N/A') }}
        </span>
        <h2 class="mb-3">Iniciar Sesión</h2>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('message'))
            <div class="alert alert-info">{{ session('message') }}</div>
        @endif

        @if (session('warning'))
            <div class="alert alert-warning">
                {{ session('warning') }}
                <a href="{{ route('resend.activation.form') }}" class="alert-link">Reenviar activación</a>
            </div>
        @endif

        <form id="loginForm" action="{{ route('login') }}" method="POST" novalidate>
            @csrf

            <div class="mb-3">
                <label for="email" class="form-label">Correo electrónico</label>
                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email"
                    name="email" value="{{ old('email') }}" autocomplete="email">
                <div id="emailError" class="invalid-feedback"></div>
                @error('email')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Contraseña</label>
                <input type="password" class="form-control @error('password') is-invalid @enderror" id="password"
                    name="password" autocomplete="current-password">
                <div id="passwordError" class="invalid-feedback"></div>
                @error('password')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">

            <button type="submit" id="submitBtn" class="btn btn-dark">Iniciar sesión</button>
        </form>

        <div class="mt-3">
            <p>¿No tienes cuenta? <a href="{{ route('register.form') }}" class="btn btn-link">Regístrate aquí</a></p>
            <p>¿No recibiste el correo de activación? <a href="{{ route('resend.activation.form') }}"
                    class="btn btn-link">Reenviar activación</a></p>
        </div>
    </div>

    <script>
        const RECAPTCHA_KEY = '6LcSqoosAAAAAD04LAyD8ciu9m9kB2cvxgOzT5eV';

        function showError(fieldId, message) {
            const field = document.getElementById(fieldId);
            const errorDiv = document.getElementById(fieldId + 'Error');
            field.classList.add('is-invalid');
            if (errorDiv) errorDiv.textContent = message;
        }

        function clearErrors() {
            ['email', 'password'].forEach(id => {
                document.getElementById(id)?.classList.remove('is-invalid');
                const err = document.getElementById(id + 'Error');
                if (err) err.textContent = '';
            });
        }

        function validateEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        }

        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            clearErrors();

            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            let valid = true;

            if (!email) {
                showError('email', 'El correo es obligatorio.');
                valid = false;
            } else if (!validateEmail(email)) {
                showError('email', 'Ingresa un correo válido.');
                valid = false;
            }

            if (!password) {
                showError('password', 'La contraseña es obligatoria.');
                valid = false;
            }

            if (!valid) return;

            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.textContent = 'Verificando...';

            grecaptcha.enterprise.ready(function() {
                grecaptcha.enterprise.execute(RECAPTCHA_KEY, {
                    action: 'submit'
                }).then(function(token) {
                    document.getElementById('g-recaptcha-response').value = token;
                    document.getElementById('loginForm').submit();
                }).catch(function() {
                    btn.disabled = false;
                    btn.textContent = 'Iniciar sesión';
                    alert('Error al validar reCAPTCHA. Intente de nuevo.');
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
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        margin-top: 100px;
    }
</style>

</html>
