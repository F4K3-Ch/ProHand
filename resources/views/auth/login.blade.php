<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
                    @if (session('success'))
                        <div class="error-success-message">
                            <p class="success">{{ session('success') }}</p>
                        </div>
                    @endif
                    
                    @if ($errors->any())
                        <div class="error-success-message">
                            <p class="error">{{ $errors->first() }}</p>
                        </div>
                    @endif
                </div>

                <form method="POST" action="{{ route('login') }}">
                    @csrf {{-- CRITICAL: Protects against Cross-Site Request Forgery --}}

                    <div class="login-input-box">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>
                    </div>

                    <div class="login-input-box">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>

                    <div id="auth-status" 
                        data-email-error="{{ $errors->has('email') ? '1' : '0' }}" 
                        data-pass-error="{{ $errors->has('password') ? '1' : '0' }}"
                        style="display: none;">
                    </div>

                    <button type="submit" class="btn">Login</button>
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
        document.addEventListener("DOMContentLoaded", function() {
            const status = document.getElementById("auth-status");
            const emailInput = document.getElementById("email");
            const passwordInput = document.getElementById("password");

            // Check for errors from our data attributes
            if (status.dataset.emailError === '1') {
                emailInput.focus();
                // Move cursor to the end
                const val = emailInput.value;
                emailInput.value = '';
                emailInput.value = val;
            } else if (status.dataset.passError === '1') {
                passwordInput.focus();
            }
        });
    </script>
</body>
</html>