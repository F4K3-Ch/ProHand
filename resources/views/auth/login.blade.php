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
        const token = localStorage.getItem('token');

        if (token) {
            fetch('/api/me', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Authorization': 'Bearer ' + token
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

                if (data.user.role === 'admin') {
                    window.location.href = '/admin/dashboard';
                } else if (data.user.role === 'tasker') {
                    window.location.href = '/tasker/dashboard';
                } else if (data.user.role === 'client') {
                    window.location.href = '/client/dashboard';
                } else {
                    localStorage.removeItem('token');
                    localStorage.removeItem('user');
                }
            })
            .catch(() => {
                localStorage.removeItem('token');
                localStorage.removeItem('user');
            });
        }

        document.getElementById('loginForm').addEventListener('submit', function (e) {
            e.preventDefault();

            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            const email = emailInput.value;
            const password = passwordInput.value;
            const errorElement = document.getElementById('errorElement');
            const spinner = document.getElementById('loginSpinner');

            spinner.style.display = 'inline-block';

            errorElement.textContent = '';
            errorElement.style.display = 'none';

            fetch('/api/login', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ email, password })
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
                localStorage.removeItem('token');
                localStorage.removeItem('user');
                localStorage.setItem('token', data.token);
                sessionStorage.setItem('success_message', data.message);

                return fetch('/api/me', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'Authorization': 'Bearer ' + data.token
                    }
                });
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Failed to retrieve user information.');
                }
                return response.json();
            })
            .then(data => {
                const user = data.user;

                localStorage.setItem('user', JSON.stringify(user));

                if (user.role === 'admin') {
                    window.location.href = '/admin/dashboard';
                } else if (user.role === 'tasker') {
                    window.location.href = '/tasker/dashboard';
                } else if (user.role === 'client') {
                    window.location.href = '/client/dashboard';
                } else {
                    localStorage.removeItem('token');
                    localStorage.removeItem('user');
                    errorElement.textContent = 'Unknown user role.';
                    errorElement.style.display = 'block';
                }
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
            });
        });
    </script>
</body>
</html>