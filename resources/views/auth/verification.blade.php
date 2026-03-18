<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Verificación</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <span class="badge bg-secondary ms-2">
            {{ config('app.instance', 'N/A') }}
        </span>
        <h2 class="mb-3">Verificación de cuenta</h2>

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

        <p class="text-muted">Ingresa el código de 6 dígitos que enviamos a tu correo.</p>

        <form id="verificationForm" action="{{ route('verification.submit') }}" method="POST" novalidate>
            @csrf
            <div class="mb-3">
                <label for="code" class="form-label">Código de verificación</label>
                <input type="text" class="form-control @error('code') is-invalid @enderror"
                    id="code" name="code" maxlength="6" autocomplete="one-time-code"
                    inputmode="numeric" pattern="\d{6}" placeholder="000000">
                <div id="codeError" class="invalid-feedback"></div>
                @error('code')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" id="submitBtn" class="btn btn-dark">Verificar</button>
        </form>

        <div class="mt-3">
            <p>¿No recibiste el código? <a href="{{ route('login.form') }}" class="btn btn-link">Volver al login</a></p>
        </div>
    </div>

    <script>
        document.getElementById('code').addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, '').slice(0, 6);
        });

        document.getElementById('verificationForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const code    = document.getElementById('code').value.trim();
            const codeErr = document.getElementById('codeError');
            const btn     = document.getElementById('submitBtn');

            codeErr.textContent = '';
            document.getElementById('code').classList.remove('is-invalid');

            if (!code) {
                document.getElementById('code').classList.add('is-invalid');
                codeErr.textContent = 'El código es obligatorio.';
                return;
            }
            if (!/^\d{6}$/.test(code)) {
                document.getElementById('code').classList.add('is-invalid');
                codeErr.textContent = 'El código debe ser de 6 dígitos.';
                return;
            }

            btn.disabled = true;
            btn.textContent = 'Verificando...';
            this.submit();
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
