<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ asset('img/ProHand(Adapt).png') }}">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('style/style.css') }}">
    <title>Register for ProHand</title>
    <script>
        const savedTheme = localStorage.getItem('theme');
        document.documentElement.setAttribute('data-theme', savedTheme === 'dark' ? 'dark' : 'light');
    </script>
</head>
<body class="register-page">

    <button type="button" id="themeToggle" class="auth-theme-toggle">
        <i class="fa-solid fa-moon"></i>
        <span>Dark Mode</span>
    </button>

    <a href="{{ url('/') }}" class="btn-back-auth">
        <i class="fa-solid fa-arrow-left"></i>
        Back
    </a>

    <div class="register-container">
        <h1>Register Here</h1>

        <div class="error-success-message">
            <p id="errorElement" class="error" style="display:none;"></p>
        </div>

        <form id="registerForm">
            @csrf

            <div class="register-input-box">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required>
            </div>

            <div class="register-input-box">
                <label for="last_name">Last Name</label>
                <input type="text" id="last_name" name="last_name" value="{{ old('last_name') }}" required>
            </div>

            <div class="register-input-box">
                <label for="first_name">First Name</label>
                <input type="text" id="first_name" name="first_name" value="{{ old('first_name') }}" required>
            </div>

            <div class="register-input-box">
                <label for="middle_name">Middle Name (optional)</label>
                <input type="text" id="middle_name" name="middle_name" value="{{ old('middle_name') }}">
            </div>

            <div class="register-input-box">
                <label for="contact_number">Contact Number</label>
                <input type="text" id="contact_number" name="contact_number" value="{{ old('contact_number') }}" required>
            </div>

            <div class="register-input-box">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <div class="register-input-box">
                <label for="password_confirmation">Confirm Password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required>
            </div>

            <button type="submit" class="btn" id="registerButton">
                Register
                <div class="circle-spinner" id="registerSpinner" style="display:none;"></div>
            </button>
        </form>

        <p class="login-link">
            Already have an account?
            <a href="{{ route('login') }}">Login here</a>
        </p>
    </div>

<script>
    const apiKey = "{{ config('services.my_api.api_key') }}";
    const origin = "{{ config('services.my_api.origin') }}";

    document.getElementById('registerForm').addEventListener('submit', async function (event) {
        event.preventDefault();

        const registerButton = document.getElementById('registerButton');
        const registerSpinner = document.getElementById('registerSpinner');
        const errorElement = document.getElementById('errorElement');

        errorElement.style.display = 'none';
        errorElement.textContent = '';

        registerButton.disabled = true;
        registerSpinner.style.display = 'inline-block';

        const formData = new FormData(this);

        try {
            const response = await fetch("{{ route('register') }}", {
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
                    errorElement.textContent = result.message || 'Registration failed.';
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
            console.error('Registration error:', error);

            errorElement.textContent = 'Something went wrong. Please try again.';
            errorElement.style.display = 'block';

        } finally {
            registerButton.disabled = false;
            registerSpinner.style.display = 'none';
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