<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Registro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://www.google.com/recaptcha/enterprise.js?render=6LcSqoosAAAAAD04LAyD8ciu9m9kB2cvxgOzT5eV" async
        defer></script>
</head>

<body>
    <div class="container mt-5">
        <span class="badge bg-secondary ms-2">
            {{ env('APP_INSTANCE', 'N/A') }}
        </span>
        <h2 class="mb-3">Registro de Usuario</h2>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <form id="registerForm" action="{{ route('register') }}" method="POST" novalidate>
            @csrf

            <div class="mb-3">
                <label for="name" class="form-label">Nombre</label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"
                    name="name" value="{{ old('name') }}" autocomplete="name">
                <div id="nameError" class="invalid-feedback"></div>
                @error('name')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

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
                <label for="password" class="form-label">
                    Contraseña <small class="text-muted">(mín. 8 caracteres, mayúscula, número y carácter
                        especial)</small>
                </label>
                <input type="password" class="form-control @error('password') is-invalid @enderror" id="password"
                    name="password" autocomplete="new-password">
                <div id="passwordError" class="invalid-feedback"></div>
                @error('password')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="password_confirmation" class="form-label">Confirmar contraseña</label>
                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation"
                    autocomplete="new-password">
                <div id="confirmError" class="invalid-feedback"></div>
            </div>

            <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">

            <button type="submit" id="submitBtn" class="btn btn-dark">Registrarse</button>
        </form>

        @if (session('success'))
            <div class="mt-3">
                <a href="{{ route('resend.activation.form') }}" class="btn btn-link">
                    ¿No recibiste el correo? Reenviar activación
                </a>
            </div>
        @endif

        <div class="mt-3">
            <p>¿Ya tienes cuenta? <a href="{{ route('login.form') }}" class="btn btn-link">Inicia sesión aquí</a></p>
        </div>
    </div>

    <script>
        const RECAPTCHA_KEY = '6LcSqoosAAAAAD04LAyD8ciu9m9kB2cvxgOzT5eV';

        const rules = [{
                test: v => v.length >= 8,
                msg: '• Mínimo 8 caracteres'
            },
            {
                test: v => /[A-Z]/.test(v),
                msg: '• Al menos una mayúscula'
            },
            {
                test: v => /[a-z]/.test(v),
                msg: '• Al menos una minúscula'
            },
            {
                test: v => /[0-9]/.test(v),
                msg: '• Al menos un número'
            },
            {
                test: v => /[@$!%*#?&.]/.test(v),
                msg: '• Al menos un carácter especial (@$!%*#?&.)'
            },
        ];

        function showFieldError(fieldId, message) {
            const field = document.getElementById(fieldId);
            const errDiv = document.getElementById(fieldId.replace('password_confirmation', 'confirm') + 'Error') ||
                document.getElementById(fieldId + 'Error');
            if (field) field.classList.add('is-invalid');
            if (errDiv) errDiv.innerHTML = message;
        }

        function clearFieldError(fieldId) {
            const field = document.getElementById(fieldId);
            const errId = fieldId === 'password_confirmation' ? 'confirmError' : fieldId + 'Error';
            const errDiv = document.getElementById(errId);
            if (field) field.classList.remove('is-invalid');
            if (errDiv) errDiv.innerHTML = '';
        }

        // Validación en tiempo real de contraseña
        document.getElementById('password').addEventListener('input', function() {
            const val = this.value;
            const failed = rules.filter(r => !r.test(val)).map(r => r.msg);
            const errDiv = document.getElementById('passwordError');
            if (failed.length) {
                this.classList.add('is-invalid');
                errDiv.innerHTML = failed.join('<br>');
            } else {
                this.classList.remove('is-invalid');
                errDiv.innerHTML = '';
            }
        });

        // Validación en tiempo real de confirmación
        document.getElementById('password_confirmation').addEventListener('input', function() {
            const pw = document.getElementById('password').value;
            const errDiv = document.getElementById('confirmError');
            if (this.value && this.value !== pw) {
                this.classList.add('is-invalid');
                errDiv.textContent = 'Las contraseñas no coinciden.';
            } else {
                this.classList.remove('is-invalid');
                errDiv.textContent = '';
            }
        });

        document.getElementById('registerForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const name = document.getElementById('name').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const confirm = document.getElementById('password_confirmation').value;
            let valid = true;

            ['name', 'email', 'password'].forEach(clearFieldError);
            document.getElementById('password_confirmation').classList.remove('is-invalid');
            document.getElementById('confirmError').textContent = '';

            if (!name) {
                showFieldError('name', 'El nombre es obligatorio.');
                valid = false;
            }

            if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                showFieldError('email', 'Ingresa un correo electrónico válido.');
                valid = false;
            }

            const failedRules = rules.filter(r => !r.test(password));
            if (failedRules.length) {
                showFieldError('password', failedRules.map(r => r.msg).join('<br>'));
                valid = false;
            }

            if (password !== confirm) {
                document.getElementById('password_confirmation').classList.add('is-invalid');
                document.getElementById('confirmError').textContent = 'Las contraseñas no coinciden.';
                valid = false;
            }

            if (!valid) return;

            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.textContent = 'Registrando...';

            grecaptcha.enterprise.ready(function() {
                grecaptcha.enterprise.execute(RECAPTCHA_KEY, {
                    action: 'submit'
                }).then(function(token) {
                    document.getElementById('g-recaptcha-response').value = token;
                    document.getElementById('registerForm').submit();
                }).catch(function() {
                    btn.disabled = false;
                    btn.textContent = 'Registrarse';
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
