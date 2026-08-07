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
                        <input type="email" id="email" required>
                    </div>

                    <div class="login-input-box">
                        <label for="password">Password</label>
                        <input type="password" id="password" required>
                    </div>

                    <button type="submit" class="btn">
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
document.addEventListener('DOMContentLoaded', async () => {

    const loginForm = document.getElementById('loginForm');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const errorElement = document.getElementById('errorElement');
    const spinner = document.getElementById('loginSpinner');


    const token = localStorage.getItem('token');

    if (token) {

        try {

            const response = await fetch('/api/me', {
                headers: {
                    'Accept': 'application/json',
                    'Authorization': `Bearer ${token}`
                }
            });

            if (response.ok) {

                const data = await response.json();

                switch (data.user.role) {

                    case 'admin':
                        window.location.href = '/admin/dashboard';
                        return;

                    case 'tasker':
                        window.location.href = '/tasker/dashboard';
                        return;

                    case 'client':
                        window.location.href = '/client/dashboard';
                        return;
                }

            } else {

                localStorage.removeItem('token');
                localStorage.removeItem('user');

            }

        } catch (error) {

            localStorage.removeItem('token');
            localStorage.removeItem('user');

        }

    }

    loginForm.addEventListener('submit', async function (e) {

        e.preventDefault();

        spinner.style.display = 'inline-block';
        errorElement.style.display = 'none';
        errorElement.textContent = '';

        try {

            const response = await fetch('/api/login', {

                method: 'POST',

                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },

                body: JSON.stringify({
                    email: emailInput.value.trim(),
                    password: passwordInput.value
                })

            });

            const data = await response.json();

            if (!response.ok) {
                throw data;
            }

            localStorage.removeItem('token');
            localStorage.removeItem('user');


            localStorage.setItem('token', data.token);
            localStorage.setItem('user', JSON.stringify(data.user));

            switch (data.user.role) {

                case 'admin':
                    window.location.href = '/admin/dashboard';
                    break;

                case 'tasker':
                    window.location.href = '/tasker/dashboard';
                    break;

                case 'client':
                    window.location.href = '/client/dashboard';
                    break;

                default:

                    localStorage.clear();

                    errorElement.textContent = 'Unknown user role.';
                    errorElement.style.display = 'block';

            }

        }
        catch (error) {

            let message = 'Login failed.';

            if (error.error) {
                message = error.error;
            }

            if (error.message) {
                message = error.message;
            }

            if (error.errors) {

                const firstKey = Object.keys(error.errors)[0];

                message = error.errors[firstKey][0];

            }

            passwordInput.value = '';

            errorElement.textContent = message;
            errorElement.style.display = 'block';

            passwordInput.focus();

        }
        finally {

            spinner.style.display = 'none';

        }

    });

});
</script>

</body>
</html>