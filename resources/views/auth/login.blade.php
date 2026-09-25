<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ asset('img/ProHand(Adapt).png') }}">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('style/style.css') }}">
    <title>Log into ProHand</title>
    <script>
        const savedTheme = localStorage.getItem('theme');
        document.documentElement.setAttribute('data-theme', savedTheme || 'dark');
    </script>
</head>
<body class="login-page">

    <button type="button" id="themeToggle" class="auth-theme-toggle">
        <i class="fa-solid fa-moon"></i>
        <span>Dark Mode</span>
    </button>

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
                        <p id="successElement" class="success" style="display:none;"></p>
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
    window.addEventListener('pageshow', function (event) {
        if (event.persisted) {
            window.location.reload();
        }
    });

    const successElement = document.getElementById('successElement');
    const errorElement = document.getElementById('errorElement');

    const urlParams = new URLSearchParams(window.location.search);

    if (urlParams.get('verified') === '1') {
        sessionStorage.setItem(
            'success_message',
            'Your email has been verified. You can now log in.'
        );

        window.history.replaceState({}, document.title, "{{ route('login') }}");
    }

    const successMessage = sessionStorage.getItem('success_message');

    if (successMessage) {
        successElement.textContent = successMessage;
        successElement.style.display = 'flex';
        sessionStorage.removeItem('success_message');
    }
    
    const apiKey = "{{ config('services.my_api.api_key') }}";
    const origin = "{{ config('services.my_api.origin') }}";

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
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                    'X-API-KEY': apiKey,
                    'X-MY-ORIGIN' : origin
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

                errorElement.style.display = 'flex';
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

    const themeToggle = document.getElementById('themeToggle');
    const html = document.documentElement;

    function updateThemeButton() {
        const dark = html.getAttribute('data-theme') === 'dark';

        themeToggle.innerHTML = dark
            ? '<i class="fa-solid fa-sun"></i><span>Light Mode</span>'
            : '<i class="fa-solid fa-moon"></i><span>Dark Mode</span>';
    }

    updateThemeButton();

    themeToggle.addEventListener('click', function () {
        const dark = html.getAttribute('data-theme') === 'dark';
        const theme = dark ? 'light' : 'dark';

        html.setAttribute('data-theme', theme);
        localStorage.setItem('theme', theme);
        updateThemeButton();
    });
</script>
</body>
</html>