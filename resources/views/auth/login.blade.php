<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="api-key" content="{{ config('services.my_api.api_key') }}">
    <meta name="api-origin" content="{{ config('services.my_api.origin') }}">
    <link rel="icon" type="image/png" href="{{ asset('img/ProHand(Adapt).png') }}">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('style/style.css') }}">
    <title>Log into ProHand</title>
</head>
<body class="login-page">

    <a href="{{ url('/') }}" class="btn-back-auth">
        <i class="fa-solid fa-arrow-left"></i>
        Back
    </a>

    <main class="login-main">
        <section class="login-wrapper">
            <div class="login-container">

                <div class="login-logo">
                    <img src="{{ asset('img/ProHand(Adapt).png') }}" alt="ProHand Logo" class="logo-img">
                    <h1>Login to Pro<span>Hand</span></h1>
                </div>

                <div class="login-error-success-message">
                    <div class="error-success-message">
                        <p id="errorElement" class="error" style="display:none;"></p>
                    </div>
                </div>

                <form id="loginForm">
                    @csrf

                    <div class="login-input-box">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required>
                    </div>

                    <div class="login-input-box">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>

                    <button type="submit" class="btn" id="loginButton">
                        Login
                        <div class="circle-spinner" id="loginSpinner" style="display:none;"></div>
                    </button>
                </form>

                <p class="register-link">
                    Don't have an account?
                    <a href="{{ route('register') }}">Register here</a>
                </p>

            </div>
        </section>

        <footer class="index-footer">
            <p>&copy; {{ date('Y') }} ProHand. All rights reserved.</p>
        </footer>
    </main>

<script>
    document.getElementById('loginForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        const button = document.getElementById('loginButton');
        const spinner = document.getElementById('loginSpinner');
        const error = document.getElementById('errorElement');

        button.disabled = true;
        spinner.style.display = 'inline-block';
        error.style.display = 'none';

        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const apiKey = document.querySelector('meta[name="api-key"]').content;
        const apiOrigin = document.querySelector('meta[name="api-origin"]').content;

        try {
            const response = await fetch('/api/login', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-API-KEY': apiKey,
                    'X-ORIGIN': apiOrigin
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    email: document.getElementById('email').value,
                    password: document.getElementById('password').value
                })
            });

            const data = await response.json();

            if (!response.ok) {
                if (data.errors) {
                    const messages = Object.values(data.errors).flat();
                    throw new Error(messages.join(' '));
                }

                throw new Error(data.message || 'Login failed.');
            }

            window.location.href = data.redirect;
        } catch (err) {
            error.textContent = err.message;
            error.style.display = 'block';
            button.disabled = false;
            spinner.style.display = 'none';
        }
    });
</script>
</body>
</html>