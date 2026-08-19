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
    document.getElementById('loginForm').addEventListener('submit', async function (event) {
        event.preventDefault();

        const loginButton = document.getElementById('loginButton');
        const loginSpinner = document.getElementById('loginSpinner');
        const errorElement = document.getElementById('errorElement');

        errorElement.style.display = 'none';
        errorElement.textContent = '';

        loginButton.disabled = true;
        loginSpinner.style.display = 'inline-block';

        const formData = new FormData(this);

        try {
            const response = await fetch("{{ route('login') }}", {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                },
                body: formData,
                credentials: 'same-origin'
            });

            const result = await response.json();

            if (!response.ok) {
                if (result.errors) {
                    if (result.errors.email) {
                        errorElement.textContent = result.errors.email[0];
                    } else if (result.errors.password) {
                        errorElement.textContent = result.errors.password[0];
                    } else {
                        errorElement.textContent = Object.values(result.errors)[0][0];
                    }
                } else {
                    errorElement.textContent = result.message || 'Login failed.';
                }

                errorElement.style.display = 'block';
                return;
            }

            sessionStorage.setItem('success_message', result.message);

            const role = result.user.role;

            if (role === 'admin') {
                window.location.href = '/admin/dashboard';
            } else if (role === 'tasker') {
                window.location.href = '/tasker/dashboard';
            } else {
                window.location.href = '/client/dashboard';
            }

        } catch (error) {
            console.error(error);

            errorElement.textContent = 'Something went wrong. Please try again.';
            errorElement.style.display = 'block';

        } finally {
            loginButton.disabled = false;
            loginSpinner.style.display = 'none';
        }
    });
</script>
</body>
</html>