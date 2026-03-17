<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Resend Activation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://www.google.com/recaptcha/enterprise.js?render=6LcSqoosAAAAAD04LAyD8ciu9m9kB2cvxgOzT5eV" async
        defer></script>
</head>

<body>
    <div class="container mt-5">
        <h2 class="mb-3">Resend Activation Email</h2>

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

        <div id="jsMessage" class="alert" style="display:none;"></div>

        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" id="email" name="email" required>
            <div id="emailError" class="text-danger"></div>
        </div>

        <input type="hidden" id="g-recaptcha-response" name="g-recaptcha-response">

        <button type="button" id="resendBtn" class="btn btn-dark">Resend Activation Email</button>

        <div class="mt-3">
            <p>Already have an account? <a href="/login" class="btn btn-link">Login here</a></p>
        </div>
    </div>

    <script>
        document.getElementById('resendBtn').addEventListener('click', function() {
            const email = document.getElementById('email').value.trim();
            const emailError = document.getElementById('emailError');
            const jsMessage = document.getElementById('jsMessage');

            emailError.textContent = '';
            jsMessage.style.display = 'none';

            if (!email) {
                emailError.textContent = 'Please enter an email address.';
                return;
            }

            grecaptcha.enterprise.ready(function() {
                grecaptcha.enterprise.execute('6LcSqoosAAAAAD04LAyD8ciu9m9kB2cvxgOzT5eV', {
                    action: 'submit'
                }).then(function(token) {
                    document.getElementById('g-recaptcha-response').value = token;

                    fetch('{{ route('resend.activation.email') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                email: email,
                                'g-recaptcha-response': token,
                            })
                        })
                        .then(response => response.json().then(data => ({ status: response.status, data })))
                        .then(({ status, data }) => {
                            jsMessage.style.display = 'block';
                            if (status === 200) {
                                jsMessage.className = 'alert alert-success';
                            } else {
                                jsMessage.className = 'alert alert-danger';
                            }
                            jsMessage.textContent = data.message;
                        })
                        .catch(function() {
                            jsMessage.style.display = 'block';
                            jsMessage.className = 'alert alert-danger';
                            jsMessage.textContent = 'An error occurred while resending the activation email.';
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
        background-color: rgb(255, 255, 255);
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        margin-top: 100px;
    }
</style>

</html>
