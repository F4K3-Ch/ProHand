<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ asset('img/ProHand(Adapt).png') }}">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('style/style.css') }}">
    <title>Register for ProHand</title>
</head>
<body class="register-page">

    <a href="{{ url('/') }}" class="btn-back-auth">
        <i class="fa-solid fa-arrow-left"></i>
        Back
    </a>

    <div class="register-container">
        <h1>Register Here</h1>

        <div class="error-success-message">
            <p id="errorElement" class="error" style="display: none;"></p>
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

            <button type="submit" class="btn">
                Register <div class="circle-spinner" id="registerSpinner" style="display: none;"></div>
            </button>
        </form>

        <p class="login-link">Already have an account?
            <a href="{{ route('login') }}">Login here</a>
        </p>
    </div>

<script>
    document.addEventListener('DOMContentLoaded', async () => {

        const registerForm = document.getElementById('registerForm');
        const errorElement = document.getElementById('errorElement');
        const spinner = document.getElementById('registerSpinner');

        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');
        const passwordConfirmationInput = document.getElementById('password_confirmation');

        const token = localStorage.getItem('token');

        if (token) {
            try {
                const response = await fetch('/api/me', {
                    headers: {
                        Accept: 'application/json',
                        Authorization: `Bearer ${token}`
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
            } catch {
                localStorage.removeItem('token');
                localStorage.removeItem('user');
            }
        }

        registerForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            spinner.style.display = 'inline-block';
            errorElement.style.display = 'none';
            errorElement.textContent = '';

            try {
                const response = await fetch('/api/register', {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json'
                    },

                    body: JSON.stringify({
                        email: emailInput.value.trim(),
                        last_name: document.getElementById('last_name').value.trim(),
                        first_name: document.getElementById('first_name').value.trim(),
                        middle_name: document.getElementById('middle_name').value.trim(),
                        contact_number: document.getElementById('contact_number').value.trim(),
                        password: passwordInput.value,
                        password_confirmation: passwordConfirmationInput.value
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
                let message = 'Registration failed.';
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
                passwordConfirmationInput.value = '';

                errorElement.textContent = message;
                errorElement.style.display = 'block';

                if (message.toLowerCase().includes('email')) {
                    emailInput.focus();
                } else {
                    passwordInput.focus();
                }

            }
            finally {
                spinner.style.display = 'none';
            }
        });
    });
</script>
</body>
</html>