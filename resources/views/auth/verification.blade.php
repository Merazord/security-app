<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://www.google.com/recaptcha/enterprise.js?render=6LdY5usqAAAAAMcGtth93FEay2BoxiVV3Qsw7yXJ" async defer></script>
</head>
<body>
    <div class="container mt-5">
        <h2 class="mb-3">Verification</h2>

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

        @if (session('message'))
            <div class="alert alert-info">
                {{ session('message') }}
            </div>
        @endif

        <form id="verificationForm" action="{{ route('verification') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label for="code" class="form-label">Verification Code</label>
                <input type="text" class="form-control" id="code" name="code" required>
                @error('code')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
            <button type="submit" class="btn btn-dark">Verify</button>
        </form>
    </div>

    </div>

    <script>
        document.getElementById('verificationForm').addEventListener('submit', function(event) {
            event.preventDefault();
            grecaptcha.enterprise.ready(function() {
                grecaptcha.enterprise.execute('6LdY5usqAAAAAMcGtth93FEay2BoxiVV3Qsw7yXJ', {action: 'submit'}).then(function(token) {
                    document.getElementById('verificationForm').submit();
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


