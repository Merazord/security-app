<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://www.google.com/recaptcha/enterprise.js?render=6LdY5usqAAAAAMcGtth93FEay2BoxiVV3Qsw7yXJ" async defer></script>
</head>
<body>
    <div class="container mt-5">
        <h2 class="mb-3">Login</h2>

        <!-- Mostrar errores generales -->
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Mostrar mensajes de éxito o error -->
        @if (session('message'))
            <div class="alert alert-info">
                {{ session('message') }}
            </div>
        @endif

        <form id="LoginForm" action="{{ route('login') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" required>
                @error('email')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
                @error('password')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>

            <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">

            <button type="submit" class="btn btn-dark">Login</button>

            <div class="mt-3">
                <p>Not registered yet? <a href="{{ route('register') }}" class="btn btn-link">Register here</a></p>
            </div>
        </form>
    </div>
    </div>


    <script>
        document.getElementById('LoginForm').addEventListener('submit', function(event) {
            event.preventDefault();
            grecaptcha.enterprise.ready(function() {
                grecaptcha.enterprise.execute('6LdY5usqAAAAAMcGtth93FEay2BoxiVV3Qsw7yXJ', {action: 'submit'}).then(function(token) {
                    document.getElementById('g-recaptcha-response').value = token;
                    document.getElementById('LoginForm').submit();
                });
            });
        });
        </script>
</body>


<style>
    body {
        background-color:rgb(37, 39, 41); /* Fondo azul */
        color: #333; /* Texto en color oscuro para contraste */
        font-family: Arial, sans-serif;
    }

    .container {
        max-width: 500px;
        background-color:rgb(255, 255, 255);
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        margin-top: 100px;
    }
</style>
</html>
