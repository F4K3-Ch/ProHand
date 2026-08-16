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
        const loginForm = document.getElementById('loginForm');
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');
        const errorElement = document.getElementById('errorElement');
        const spinner = document.getElementById('loginSpinner');
        const loginButton = document.getElementById('loginButton');

        function redirectByRole(user) {
            if (user.role === 'admin') {
                window.location.href = '/admin/dashboard';
            } else if (user.role === 'tasker') {
                window.location.href = '/tasker/dashboard';
            } else if (user.role === 'client') {
                window.location.href = '/client/dashboard';
            } else {
                localStorage.removeItem('token');
                localStorage.removeItem('user');
                throw new Error('Unknown user role.');
            }
        }

        const token = localStorage.getItem('token');

        if (token) {
            fetch('/api/me', {
                method: 'GET',
                headers: {
                    'Authorization': 'Bearer ' + token,
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error();
                }

                return response.json();
            })
            .then(data => {
                localStorage.setItem('user', JSON.stringify(data.user));
                redirectByRole(data.user);
            })
            .catch(() => {
                localStorage.removeItem('token');
                localStorage.removeItem('user');
            });
        }

        loginForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const email = emailInput.value.trim();
            const password = passwordInput.value;

            spinner.style.display = 'inline-block';
            loginButton.disabled = true;

            errorElement.textContent = '';
            errorElement.style.display = 'none';

            fetch('/api/login', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    email,
                    password
                })
            })
            .then(response => {
                return response.json().then(data => {
                    if (!response.ok) {
                        throw data;
                    }

                    return data;
                });
            })
            .then(data => {
                localStorage.setItem('token', data.token);
                localStorage.setItem('user', JSON.stringify(data.user));
                sessionStorage.setItem('success_message', data.message);

                redirectByRole(data.user);
            })
            .catch(error => {
                let message = 'Login failed.';

                if (error.error) {
                    message = error.error;
                } else if (error.message) {
                    message = error.message;
                } else if (error.errors) {
                    const firstKey = Object.keys(error.errors)[0];
                    message = error.errors[firstKey][0];
                }

                errorElement.textContent = message;
                errorElement.style.display = 'block';

                if (message.toLowerCase().includes('email')) {
                    emailInput.focus();
                } else {
                    passwordInput.value = '';
                    passwordInput.focus();
                }
            })
            .finally(() => {
                spinner.style.display = 'none';
                loginButton.disabled = false;
            });
        });
    </script>
</body>
</html>