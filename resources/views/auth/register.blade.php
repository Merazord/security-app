<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Registro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://www.google.com/recaptcha/enterprise.js?render=6LdY5usqAAAAAMcGtth93FEay2BoxiVV3Qsw7yXJ" async
        defer></script>
</head>

<body>
    <div class="container mt-5">
        <h2 class="mb-3">User Registration</h2>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif


        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <form id="registerForm" action="{{ route('register') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label for="name" class="form-label">Name</label>
                <input type="text" class="form-control" id="name" name="name" required>
                <div id="nameError" class="text-danger"></div>
            </div>


            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" required>
                <div id="emailError" class="text-danger"></div>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password (must contain uppercase letters, numbers, and special
                    characters)</label>

                <input type="password" class="form-control" id="password" name="password" required />
                <div id="passwordError" class="text-danger"></div>
            </div>

            <div class="mb-3">
                <label for="password_confirmation" class="form-label">Confirm Password</label>
                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation"
                    required>
            </div>
            <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">


            <button type="submit" class="btn btn-dark">Register</button>

        </form>

        @if (session('success'))
            @if (session('email'))
                <div id="postRegisterButtonContainer" class="mt-3">
                    <a href="{{ route('resend.activation.form') }}" class="btn btn-link">
                        Resend activation email
                    </a>
                </div>
            @endif
        @endif
        <div class="mt-3">
            <p>Already have an account?<a href="/login" class="btn btn-link">Login here</a></p>
        </div>
    </div>


    </div>


    <script>
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const errorDiv = document.getElementById('passwordError');
            const messages = [];

            if (!/[A-Z]/.test(password)) {
                messages.push('• Debe contener al menos una letra mayúscula');
            }
            if (!/[a-z]/.test(password)) {
                messages.push('• Debe contener al menos una letra minúscula');
            }
            if (!/[0-9]/.test(password)) {
                messages.push('• Debe contener al menos un número');
            }
            if (!/[@$!%*#?&.]/.test(password)) {
                messages.push('• Debe contener al menos un carácter especial (@$!%*#?&.)');
            }
            if (password.length < 8) {
                messages.push('• Debe tener al menos 8 caracteres');
            }

            errorDiv.innerHTML = messages.join('<br>');
        });

        document.getElementById('registerForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const password = document.getElementById('password').value;
            const passwordConfirmation = document.getElementById('password_confirmation').value;

            if (password !== passwordConfirmation) {
                document.getElementById('passwordError').textContent = 'Las contraseñas no coinciden.';
                return;
            }

            const errorDiv = document.getElementById('passwordError');
            if (errorDiv.innerHTML.trim() !== '') {
                return;
            }

            grecaptcha.enterprise.ready(function() {
                grecaptcha.enterprise.execute('6LdY5usqAAAAAMcGtth93FEay2BoxiVV3Qsw7yXJ', {
                    action: 'submit'
                }).then(function(token) {
                    document.getElementById('g-recaptcha-response').value = token;
                    document.getElementById('registerForm').submit();
                });
            });
        });

        const resendBtn = document.getElementById('resendActivation');
        if (resendBtn) {
            resendBtn.addEventListener('click', function() {
                const email = document.getElementById('email').value;
                if (!email) {
                    alert('Please enter an email address.');
                    return;
                }
                fetch('{{ route('resend.activation.email') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            email: email
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        alert(data.message);
                    })
                    .catch(error => {
                        alert('An error occurred while resending the activation email.');
                    });
            });
        }
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
        background-color: rgb(255, 255, 255);
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        margin-top: 100px;
    }
</style>

</html>
